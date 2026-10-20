<?php

namespace App\Employees;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeInvitation;
use App\Models\EmployeeReview;
use App\Models\Partner;
use App\Models\Station;
use App\Support\RegistrationMail;
use App\Tenancy\PartnerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Fachaktionen für Mitarbeiteranlage, Einladung und bestätigte Personalprüfung.
 * Alle Änderungen sperren zuerst den Partner, dann die Akte und die Einladung;
 * Teststatus, Rolle, Mandant und Version werden innerhalb der Sperre erneut geprüft.
 * Kein Anlegen von Benutzerkonten, keine Rechtevergabe und keine DATEV-Übertragung.
 * Der öffentliche Fähigkeitszugang ist genau auf eine Einladung beschränkt und
 * eröffnet niemals eine normale Partner-Mitgliedschaft oder einen Kontozugang.
 */
final class EmployeeOnboarding
{
    /** Idempotente Voranlage aus einem sitzungsgebundenen Formularschlüssel. */
    public function create(Partner $partner, array $data): Employee
    {
        return DB::transaction(function () use ($partner, $data): Employee {
            $partner = Partner::lockForUpdate()->findOrFail($partner->id);
            Gate::authorize('create', [Employee::class, $partner]);
            $existing = Employee::withTrashed()->where('creation_key', $data['creation_key'])->first();
            // Ein altes Anlageformular darf keine Papierkorbakten duplizieren
            // oder an der weiterhin gültigen DB-Eindeutigkeit scheitern.
            abort_if($existing?->trashed(), 409, 'Diese Akte liegt bereits im Papierkorb. Bitte dort wiederherstellen.');
            if ($existing) {
                return $existing;
            }
            Station::findOrFail($data['station_id']);
            $employee = new Employee;
            $employee->forceFill([
                'uuid' => (string) Str::uuid(), 'station_id' => $data['station_id'], 'profile' => $data['profile'],
                'creation_key' => $data['creation_key'], 'status' => 'draft',
                'identity' => ['first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'email' => mb_strtolower($data['email'])],
                'employer_data' => [], 'personal_data' => [],
            ])->save();

            return $employee;
        });
    }

    /**
     * Explizite Versandaktion. Ein neuer Link widerruft alle Vorgänger und deren
     * Sitzungsfreigaben atomar. Der verschlüsselte Entwurf bleibt beim selben
     * Empfänger innerhalb dieser Einladungsfolge erhalten. Nach bewusster Inhaberpflege
     * startet eine neue Folge aus Inhaberstammdaten; private Altentwürfe bleiben zurück.
     * Versand außerhalb der Transaktion, bis zur bestätigten Übergabe kein Zutritt.
     * Fehler machen den neuen Link ungültig, ohne den Entwurf zu löschen.
     */
    public function invite(Employee $employee, int $version): bool
    {
        $mail = app(RegistrationMail::class);
        $mail->ensureAvailable();
        $secret = Str::random(64);
        $invitation = DB::transaction(function () use ($employee, $version, $secret): EmployeeInvitation {
            $partner = Partner::lockForUpdate()->findOrFail($employee->partner_id);
            $employee = Employee::lockForUpdate()->findOrFail($employee->id);
            Gate::authorize('update', $employee);
            $this->version($employee, $version);
            abort_if($employee->status === 'approved', 409, 'Die Akte ist bereits geprüft.');
            $previous = EmployeeInvitation::where('partner_id', $partner->id)->where('employee_id', $employee->id)->orderByDesc('id')->lockForUpdate()->first();
            abort_if($previous && $previous->status === 'sending' && $previous->created_at->greaterThan(now()->subMinutes(2)), 409, 'Eine Einladung wird gerade versendet.');
            $managed = $employee->status === 'managed';
            // Nach Inhaberpflege nur dessen aktuelle Stammdaten verwenden. Alte
            // private Entwürfe dürfen insbesondere beim Empfängerwechsel nicht mit.
            $data = $managed ? ($employee->personal_data ?? []) : ($previous?->data ?? []);
            // Neue Einladung darf keine laufende Arbeitgeberprüfung still verwerfen.
            abort_if($employee->status === 'submitted', 409, 'Bitte die eingereichten Angaben zuerst prüfen.');
            foreach (EmployeeInvitation::where('partner_id', $partner->id)->where('employee_id', $employee->id)->lockForUpdate()->get() as $old) {
                $old->forceFill(['status' => 'revoked', 'token_hash' => null, 'grant_hash' => null])->save();
            }
            $invitation = new EmployeeInvitation;
            $invitation->forceFill([
                'uuid' => (string) Str::uuid(), 'partner_id' => $partner->id, 'employee_id' => $employee->id,
                'token_hash' => hash('sha256', $secret), 'expires_at' => now()->addDays(7), 'invited_by' => auth()->id(),
                'status' => 'sending', 'data' => $data, 'submitted_data' => $managed ? null : $previous?->submitted_data, 'question' => $managed ? null : $previous?->question,
            ])->save();
            $employee->forceFill(['status' => 'invited', 'version' => $employee->version + 1])->save();

            return $invitation;
        });
        $success = false;
        $failureCode = 'internal';
        try {
            // Fragment gelangt beim Seitenaufruf weder in die Request-URL noch in
            // Referrer oder Webserver-Zugriffslogs. Keine Personenwerte in der Mail.
            $url = route('employees.invitation', $invitation->uuid).'#'.$secret;
            Mail::mailer($mail->mailer())->to($employee->identity['email'])->send(new EmployeeInvitationMail($url));
            $success = true;
        } catch (TransportExceptionInterface $error) {
            $failureCode = $mail->failureCode($error);
            // Keine Serverantwort oder Adresse in Session/Log spiegeln.
        } finally {
            DB::transaction(function () use ($invitation, $success, $failureCode): void {
                $current = EmployeeInvitation::lockForUpdate()->findOrFail($invitation->id);
                if ($current->status === 'sending') {
                    $current->forceFill(['status' => $success ? 'ready' : 'failed', 'token_hash' => $success ? $current->token_hash : null, 'failure_code' => $success ? null : $failureCode])->save();
                }
            });
        }

        return $success;
    }

    /** Arbeitgeberdaten speichern oder genau die eingereichte Fassung zurückgeben/freigeben. */
    public function review(Employee $employee, array $input): void
    {
        DB::transaction(function () use ($employee, $input): void {
            Partner::lockForUpdate()->findOrFail($employee->partner_id);
            $employee = Employee::lockForUpdate()->findOrFail($employee->id);
            Gate::authorize('update', $employee);
            $this->version($employee, (int) $input['version']);
            abort_if($employee->status === 'approved', 409, 'Die geprüfte Fassung ist abgeschlossen.');
            $action = $input['action'];
            $data = EmployeeFields::validate($input['data'] ?? [], 'employer', $employee->profile, $action === 'approve');
            $number = trim($input['personnel_number'] ?? '');
            if ($number !== '' && Employee::where('personnel_number', $number)->whereKeyNot($employee->id)->exists()) {
                throw ValidationException::withMessages(['personnel_number' => 'Diese Personalnummer ist im Partner bereits vergeben.']);
            }
            $station = Station::findOrFail($input['station_id']);
            $invitation = EmployeeInvitation::where('partner_id', $employee->partner_id)->where('employee_id', $employee->id)->latest('id')->lockForUpdate()->first();
            if (in_array($action, ['approve', 'return'], true)) {
                abort_unless($employee->status === 'submitted' && $invitation?->status === 'submitted', 409, 'Es gibt keine offene Einreichung zur Prüfung.');
                abort_unless((int) $input['submission_version'] === $invitation->version, 409, 'Die Einreichung wurde inzwischen geändert. Bitte neu laden.');
            }
            if ($action === 'approve') {
                // Fehlende Bestätigung ist ein korrigierbarer Formularfehler.
                // Die direkte Antwort erhält die Arbeitgeberangaben im HTML,
                // statt sie durch eine allgemeine Zugangsfehlerseite zu verlieren.
                if (($input['confirmed'] ?? null) !== '1') {
                    throw ValidationException::withMessages(['confirmed' => 'Bitte die Prüfung ausdrücklich bestätigen.']);
                }
                if ($number === '') {
                    throw ValidationException::withMessages(['personnel_number' => 'Vor Freigabe ist eine Personalnummer erforderlich.']);
                }
                $personal = EmployeeFields::validate($invitation->submitted_data ?? [], 'personal', $employee->profile, true);
                if (data_get($personal, 'vwl.has_vwl') === 'yes' && data_get($data, 'payroll.vwl_employer_share') === null) {
                    throw ValidationException::withMessages(['data.payroll.vwl_employer_share' => 'Bitte den vereinbarten VWL-AG-Anteil angeben (0 möglich).']);
                }
                $employee->personal_data = $personal;
                $identity = $employee->identity;
                $identity['first_name'] = data_get($personal, 'person.first_name');
                $identity['last_name'] = data_get($personal, 'person.last_name');
                $employee->identity = $identity;
                $employee->status = 'approved';
                $invitation->forceFill(['status' => 'approved', 'grant_hash' => null, 'token_hash' => null])->save();
            } elseif ($action === 'return') {
                $question = trim($input['question'] ?? '');
                if ($question === '') {
                    throw ValidationException::withMessages(['question' => 'Bitte eine konkrete Rückfrage formulieren.']);
                }
                $employee->status = 'changes_requested';
                $invitation->forceFill(['status' => 'changes_requested', 'question' => $question, 'version' => $invitation->version + 1])->save();
            }
            $employee->forceFill(['employer_data' => $data, 'station_id' => $station->id, 'personnel_number' => $number ?: null, 'version' => $employee->version + 1])->save();
            $review = new EmployeeReview;
            $review->forceFill([
                'employee_id' => $employee->id, 'actor_id' => auth()->id(), 'action' => $action, 'employee_version' => $employee->version,
                'created_at' => now(), 'snapshot' => $action === 'approve'
                    ? ['personal' => $employee->personal_data, 'employer' => $data, 'personnel_number' => $number, 'station_id' => $station->id, 'profile' => $employee->profile]
                    : ($action === 'return' ? ['question' => $input['question'], 'personal' => $invitation->submitted_data, 'submission_version' => $invitation->version] : []),
            ])->save();
        });
    }

    /**
     * Einmaliger Geheimnistausch per POST. Ein GET ist auch für Mail-Linkscanner
     * nebenwirkungsfrei. Nach Annahme gilt nur ein serverseitiger Sitzungsnachweis,
     * der weder Kontorechte noch fremde Mitarbeiterakten öffnen kann.
     */
    public function accept(Request $request, string $uuid, string $token): void
    {
        $this->publicContext($uuid, function (EmployeeInvitation $invitation, Employee $employee, Partner $partner) use ($request, $token, $uuid): void {
            abort_unless($partner->allowsOperationalWrites(), 403, 'Der Partnerbereich ist derzeit nur lesend.');
            abort_unless($invitation->status === 'ready' && $invitation->token_hash && hash_equals($invitation->token_hash, hash('sha256', $token)), 410, 'Dieser Zugang ist nicht verfügbar. Bitte eine neue Einladung anfordern.');
            $grant = Str::random(64);
            $invitation->forceFill(['status' => 'active', 'token_hash' => null, 'grant_hash' => hash('sha256', $grant)])->save();
            $employee->forceFill(['status' => 'in_progress', 'version' => $employee->version + 1])->save();
            $request->session()->regenerate();
            $request->session()->put('employee_access.'.$uuid, $grant);
        });
    }

    /** Liefert nur die eigene Selbstauskunft, niemals Arbeitgeber-/Personalnummernwerte. */
    public function selfView(Request $request, string $uuid): array
    {
        return $this->publicContext($uuid, function (EmployeeInvitation $invitation, Employee $employee, Partner $partner) use ($request): array {
            $this->grant($request, $invitation);

            return ['choices' => EmployeeChoices::forPartner(), 'invitation' => $invitation, 'profile' => $employee->profile, 'data' => $invitation->data ?: [
                'person' => ['first_name' => $employee->identity['first_name'], 'last_name' => $employee->identity['last_name'], 'country_code' => 'DE'],
            ], 'partnerName' => $partner->name, 'writable' => $partner->allowsOperationalWrites() && in_array($invitation->status, ['active', 'changes_requested'], true)];
        });
    }

    /** Verschlüsselt zwischenspeichern oder einreichen; keine Übernahme vor Arbeitgeberfreigabe. */
    public function submit(Request $request, string $uuid, array $input): void
    {
        $this->publicContext($uuid, function (EmployeeInvitation $invitation, Employee $employee, Partner $partner) use ($request, $input): void {
            $this->grant($request, $invitation);
            abort_unless($partner->allowsOperationalWrites(), 403, 'Der Partnerbereich ist derzeit nur lesend.');
            // Wiederholung einer erfolgreichen Einreichung bleibt nebenwirkungsfrei.
            if ($invitation->status === 'submitted' && $input['action'] === 'submit' && $invitation->version === (int) $input['version'] + 1) {
                return;
            }
            abort_unless(in_array($invitation->status, ['active', 'changes_requested'], true), 409, 'Die Angaben sind bereits zur Prüfung eingereicht.');
            abort_unless($invitation->version === (int) $input['version'], 409, 'Es gibt einen neueren Stand. Bitte neu laden.');
            $complete = $input['action'] === 'submit';
            $data = EmployeeFields::validate($input['data'] ?? [], 'personal', $employee->profile, $complete);
            if ($complete && ($input['confirmed'] ?? null) !== '1') {
                throw ValidationException::withMessages(['confirmed' => 'Bitte bestätigen Sie, dass Sie die Angaben geprüft haben.']);
            }
            $invitation->forceFill(['data' => $data, 'version' => $invitation->version + 1,
                'submitted_data' => $complete ? $data : $invitation->submitted_data, 'question' => $complete ? null : $invitation->question, 'status' => $complete ? 'submitted' : $invitation->status, 'submitted_at' => $complete ? now() : null])->save();
            if ($complete) {
                EmployeeDocument::where('employee_id', $employee->id)->where('invitation_id', $invitation->id)->whereNull('submitted_at')->update(['submitted_at' => now()]);
            }
            $employee->forceFill(['status' => $complete ? 'submitted' : ($invitation->status === 'changes_requested' ? 'changes_requested' : 'in_progress'), 'version' => $employee->version + 1])->save();
        });
    }

    /** Sofortiger Widerruf ist auch nach Testende eine erlaubte Sicherheitsmaßnahme. */
    public function revoke(Employee $employee): void
    {
        DB::transaction(function () use ($employee): void {
            $partner = Partner::lockForUpdate()->findOrFail($employee->partner_id);
            $employee = Employee::lockForUpdate()->findOrFail($employee->id);
            Gate::authorize('view', $employee);
            foreach (EmployeeInvitation::where('partner_id', $partner->id)->where('employee_id', $employee->id)->lockForUpdate()->get() as $invitation) {
                $invitation->forceFill(['token_hash' => null, 'grant_hash' => null, 'status' => in_array($invitation->status, ['submitted', 'approved'], true) ? $invitation->status : 'revoked'])->save();
            }
            $employee->forceFill(['version' => $employee->version + 1])->save();
        });
    }

    /** Nachweise verwenden dieselbe gesperrte Zugangsprüfung wie die Selbstauskunft. */
    public function withSelfAccess(Request $request, string $uuid, callable $callback, bool $write = false): mixed
    {
        return $this->publicContext($uuid, function ($invitation, $employee, $partner) use ($request, $callback, $write) {
            $this->grant($request, $invitation);
            if ($write) {
                abort_unless($partner->allowsOperationalWrites() && in_array($invitation->status, ['active', 'changes_requested'], true), 403);
            }

            return $callback($employee, $invitation, $partner);
        });
    }

    private function version(Employee $employee, int $version): void
    {
        abort_unless($employee->version === $version, 409, 'Die Akte wurde inzwischen geändert. Bitte neu laden.');
    }

    private function grant(Request $request, EmployeeInvitation $invitation): void
    {
        $grant = $request->session()->get('employee_access.'.$invitation->uuid);
        abort_unless(is_string($grant) && $invitation->grant_hash && hash_equals($invitation->grant_hash, hash('sha256', $grant)), 410, 'Dieser Zugang ist nicht verfügbar. Bitte eine neue Einladung anfordern.');
    }

    /**
     * Einziger ungescopter UUID-Einstieg: keine fremden Daten aus Formularfeldern.
     * Nach Metadatenauflösung Partner und Ressourcen sperren, Lebensdauer und
     * aktuelle Sperren prüfen, Kontext ausschließlich während des Callbacks halten.
     * Alle fremden/abgelaufenen/widerrufenen Fälle ohne Personenwerte beantworten.
     */
    private function publicContext(string $uuid, callable $callback): mixed
    {
        $context = app(PartnerContext::class);
        try {
            return DB::transaction(function () use ($uuid, $callback, $context) {
                $reference = EmployeeInvitation::where('uuid', $uuid)->first();
                abort_unless($reference, 410, 'Dieser Zugang ist nicht verfügbar. Bitte eine neue Einladung anfordern.');
                $partner = Partner::lockForUpdate()->find($reference->partner_id);
                abort_unless($partner?->is_active, 410, 'Dieser Zugang ist nicht verfügbar.');
                $context->set($partner);
                $employee = Employee::lockForUpdate()->find($reference->employee_id);
                abort_unless($employee, 410, 'Dieser Zugang ist nicht mehr verfügbar.');
                $invitation = EmployeeInvitation::lockForUpdate()->findOrFail($reference->id);
                abort_unless($invitation->expires_at->isFuture() && in_array($invitation->status, ['ready', 'active', 'submitted', 'changes_requested'], true), 410, 'Dieser Zugang ist abgelaufen oder nicht mehr verfügbar.');

                return $callback($invitation, $employee, $partner);
            });
        } finally {
            $context->clear();
        }
    }
}
