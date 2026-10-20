<?php

namespace App\Employees;

use App\Models\Employee;
use App\Models\EmployeeInvitation;
use App\Models\EmployeeReview;
use App\Models\Partner;
use App\Models\Station;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Bewusste Inhaberpflege unabhängig von der Mitarbeiter-Selbstauskunft.
 * Reihenfolge aller Sperren: Partner, Akte, Einladungen. Versionen verhindern,
 * dass zwischenzeitliche Mitarbeitereinreichungen oder andere Inhaber übergangen
 * werden. Jede Pflege beendet alte Zugänge, insbesondere beim Empfängerwechsel.
 * Private, nicht eingereichte Mitarbeiterentwürfe werden dabei niemals angezeigt
 * oder an einen neuen Empfänger kopiert. Prüffassungen bleiben verschlüsselt.
 */
final class EmployeeManagement
{
    /**
     * Speichert kataloggeprüfte, gegebenenfalls noch unvollständige Inhaberangaben.
     * Namenspflicht und Personalnummerneindeutigkeit bleiben erhalten. Die vorherige
     * Fassung und der Änderungsgrund liegen verschlüsselt in der Prüfhistorie.
     * Keine Benachrichtigung, kein Benutzerkonto, keine behauptete DATEV-Freigabe.
     */
    public function save(Employee $reference, array $input): void
    {
        DB::transaction(function () use ($reference, $input): void {
            Partner::lockForUpdate()->findOrFail($reference->partner_id);
            $employee = Employee::lockForUpdate()->findOrFail($reference->id);
            Gate::authorize('update', $employee);
            $this->version($employee, (int) $input['version']);
            $profile = $input['profile'];
            $personal = $this->validate($input['personal_data'], 'personal', $profile, 'personal_data');
            // Namenspflicht separat prüfen, ohne das Ergebnis als Ersatz für
            // das vollständige Katalogarray zu verwenden. Laravel würde bei
            // nur zwei verschachtelten Envelope-Regeln andere Personalfelder
            // aus validated() entfernen und Änderungen unbemerkt verlieren.
            Validator::make(['personal_data' => $personal], [
                'personal_data.person.first_name' => ['required', 'string', 'max:80'],
                'personal_data.person.last_name' => ['required', 'string', 'max:80'],
            ], ['required' => ':attribute fehlt noch.', 'max' => ':attribute darf höchstens 80 Zeichen enthalten.'], ['personal_data.person.first_name' => 'Vorname', 'personal_data.person.last_name' => 'Familienname'])->validate();
            $employer = $this->validate($input['employer_data'], 'employer', $profile, 'employer_data');
            $number = trim($input['personnel_number'] ?? '');
            // Auch Papierkorbakten reservieren ihre Personalnummer: eine spätere
            // Wiederherstellung darf keine doppelte Abrechnungsidentität erzeugen.
            if ($number !== '' && Employee::withTrashed()->where('personnel_number', $number)->whereKeyNot($employee->id)->exists()) {
                throw ValidationException::withMessages(['personnel_number' => 'Diese Personalnummer ist bereits vergeben, gegebenenfalls im Papierkorb.']);
            }
            Station::findOrFail($input['station_id']);
            $before = ['identity' => $employee->identity, 'personal' => $employee->personal_data, 'employer' => $employee->employer_data,
                'profile' => $employee->profile, 'personnel_number' => $employee->personnel_number, 'station_id' => $employee->station_id, 'status' => $employee->status];
            $this->closeInvitations($employee);
            $employee->forceFill([
                'identity' => ['first_name' => $personal['person']['first_name'], 'last_name' => $personal['person']['last_name'], 'email' => mb_strtolower(trim($input['email']))],
                'personal_data' => $personal, 'employer_data' => $employer, 'profile' => $profile,
                'personnel_number' => $number ?: null, 'station_id' => $input['station_id'],
                // Inhaberpflege ist keine neue Bestätigung durch den Mitarbeiter
                // und keine DATEV-Freigabe. Eine Einladung bleibt separat möglich.
                'status' => 'managed', 'version' => $employee->version + 1,
            ])->save();
            $this->history($employee, 'edit', ['before' => $before, 'reason' => $input['reason']]);
        });
    }

    /** Papierkorb ist rückgängig machbar; auch sämtliche bisherigen Zugänge schließen. */
    public function trash(Employee $reference, int $version): void
    {
        DB::transaction(function () use ($reference, $version): void {
            Partner::lockForUpdate()->findOrFail($reference->partner_id);
            $employee = Employee::lockForUpdate()->findOrFail($reference->id);
            Gate::authorize('delete', $employee);
            $this->version($employee, $version);
            $this->closeInvitations($employee);
            $employee->forceFill(['version' => $employee->version + 1])->save();
            $this->history($employee, 'trash', []);
            $employee->delete();
        });
    }

    /**
     * Wiederherstellen öffnet niemals alte Links/Sitzungen. Eine ehemals offene
     * Einreichung wird nicht als weiterhin aktive Prüfung dargestellt; dafür
     * muss der Inhaber neu einladen oder die Stammdaten bewusst selbst pflegen.
     */
    public function restore(Employee $reference, int $version): void
    {
        DB::transaction(function () use ($reference, $version): void {
            Partner::lockForUpdate()->findOrFail($reference->partner_id);
            $employee = Employee::withTrashed()->lockForUpdate()->findOrFail($reference->id);
            Gate::authorize('restore', $employee);
            $this->version($employee, $version);
            abort_unless($employee->trashed(), 409, 'Die Akte wurde bereits wiederhergestellt.');
            $employee->restore();
            $employee->forceFill(['version' => $employee->version + 1, 'status' => $employee->status === 'approved' ? 'approved' : ($employee->personal_data ? 'managed' : 'draft')])->save();
            $this->history($employee, 'restore', []);
        });
    }

    private function version(Employee $employee, int $version): void
    {
        abort_unless($employee->version === $version, 409, 'Es gibt einen neueren Stand. Bitte neu laden.');
    }

    private function closeInvitations(Employee $employee): void
    {
        foreach (EmployeeInvitation::where('partner_id', $employee->partner_id)->where('employee_id', $employee->id)->lockForUpdate()->get() as $invitation) {
            $invitation->forceFill(['status' => 'revoked', 'token_hash' => null, 'grant_hash' => null])->save();
        }
    }

    /** Gleiche Feldregeln, aber getrennte Fehlerpfade für zwei unabhängige Formbereiche. */
    private function validate(array $data, string $side, string $profile, string $root): array
    {
        try {
            return EmployeeFields::validate($data, $side, $profile, false);
        } catch (ValidationException $error) {
            $messages = [];
            foreach ($error->errors() as $key => $errors) {
                $messages[preg_replace('/^data(?=\.|$)/', $root, $key)] = $errors;
            }
            throw ValidationException::withMessages($messages);
        }
    }

    private function history(Employee $employee, string $action, array $snapshot): void
    {
        EmployeeReview::forceCreate(['employee_id' => $employee->id, 'actor_id' => auth()->id(), 'action' => $action,
            'employee_version' => $employee->version, 'snapshot' => $snapshot, 'created_at' => now()]);
    }
}
