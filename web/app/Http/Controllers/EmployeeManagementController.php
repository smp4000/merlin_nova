<?php

namespace App\Http\Controllers;

use App\Employees\EmployeeChoices;
use App\Employees\EmployeeFields;
use App\Employees\EmployeeManagement;
use App\Models\Employee;
use App\Models\EmployeeInvitation;
use App\Models\Partner;
use App\Models\Station;
use App\Tenancy\PartnerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Ausschließlich Inhaber im geprüften Partnerkontext. Papierkorbzugriff erweitert
 * nur den Soft-Delete-Scope, niemals den Mandantenscope. Fehlgeschlagene Formulare
 * rendern bekannte Felder direkt, ohne vertrauliche old-input-Kopien in Sitzungen.
 */
class EmployeeManagementController extends Controller
{
    /** Formale Pflege mit explizitem Änderungsgrund und Bestätigung der Zugangssperre. */
    public function edit(Request $request, PartnerContext $context)
    {
        [$partner, $employee] = $this->record($request, $context);
        Gate::authorize('update', $employee);
        $profile = $employee->profile;
        if ($request->isMethod('get') && $request->has('profile')) {
            $profile = Validator::make($request->query(), ['profile' => ['required', Rule::in(array_keys(EmployeeFields::PROFILES))]])->validate()['profile'];
        }
        if ($request->isMethod('post')) {
            try {
                $input = Validator::make($request->all(), [
                    'version' => ['required', 'integer'], 'profile' => ['required', Rule::in(array_keys(EmployeeFields::PROFILES))],
                    'email' => ['required', 'email:rfc', 'max:160'], 'station_id' => ['required', 'integer'],
                    'personnel_number' => ['nullable', 'string', 'max:20'], 'personal_data' => ['present', 'array'], 'employer_data' => ['present', 'array'],
                    'reason' => ['required', 'string', 'max:500'], 'confirmed' => ['required', 'accepted'],
                    'partner_id' => ['prohibited'], 'role' => ['prohibited'], 'user_id' => ['prohibited'], 'data' => ['prohibited'],
                ], ['required' => ':attribute fehlt noch.', 'accepted' => 'Bitte die Inhaberpflege und Sperrung bisheriger Zugänge bestätigen.',
                    'email' => 'Bitte eine gültige Einladungsadresse eingeben.', 'string' => ':attribute muss ein Text sein.',
                    'array' => ':attribute muss eine gültige Feldgruppe sein.', 'integer' => ':attribute muss eine ganze Zahl sein.',
                    'in' => ':attribute enthält eine unbekannte Auswahl.', 'max' => ':attribute überschreitet die zulässige Länge.',
                    'prohibited' => 'Dieses zusätzliche Feld ist hier nicht zulässig.'],
                    ['reason' => 'Änderungsgrund', 'personal_data.person.first_name' => 'Vorname', 'personal_data.person.last_name' => 'Familienname', 'email' => 'E-Mail-Adresse'])->validate();
                app(EmployeeManagement::class)->save($employee, $input);

                return redirect()->route('employees.show', [$partner->id, $employee->uuid])->with('status_type', 'success')
                    ->with('status', 'Mitarbeiterdaten gespeichert. Bisherige Einladungslinks und Sitzungen sind gesperrt. Eine neue Einladung senden Sie bei Bedarf gesondert.');
            } catch (ValidationException $error) {
                $values = $this->values($partner, $employee, $profile);
                foreach (['personal_data', 'employer_data'] as $root) {
                    if (is_array($request->input($root))) {
                        $values[$root] = $request->input($root);
                    }
                }
                if (filter_var($request->input('station_id'), FILTER_VALIDATE_INT) !== false) {
                    $values['station_id'] = (int) $request->input('station_id');
                }
                foreach (['email', 'personnel_number', 'reason'] as $key) {
                    if (is_string($request->input($key))) {
                        $values[$key] = $request->input($key);
                    }
                }
                if (is_string($request->input('profile')) && array_key_exists($request->input('profile'), EmployeeFields::PROFILES)) {
                    $values['profile'] = $request->input('profile');
                }

                return response()->view('employees.edit', $values + ['fieldErrors' => $error->errors()], 422);
            }
        }

        return view('employees.edit', $this->values($partner, $employee, $profile));
    }

    /** Nur eigene Papierkorbakten anzeigen; nach Testende weiterhin lesend. */
    public function trashIndex(PartnerContext $context)
    {
        $partner = Partner::findOrFail($context->id());
        Gate::authorize('viewAny', [Employee::class, $partner]);

        return view('employees.trash', ['partner' => $partner, 'employees' => Employee::onlyTrashed()->latest('deleted_at')->paginate(20)]);
    }

    /** Erst nach expliziter Bestätigung in den rückgängig machbaren Papierkorb. */
    public function trash(Request $request, PartnerContext $context)
    {
        [$partner, $employee] = $this->record($request, $context);
        Gate::authorize('delete', $employee);
        $validator = Validator::make($request->all(), ['version' => ['required', 'integer'], 'confirmed' => ['required', 'accepted']]);
        if ($validator->fails()) {
            return redirect()->route('employees.edit', [$partner->id, $employee->uuid])->with('status_type', 'error')->with('status', 'Bitte das Verschieben in den Papierkorb ausdrücklich bestätigen.');
        }
        app(EmployeeManagement::class)->trash($employee, (int) $validator->validated()['version']);

        return redirect()->route('employees.index', $partner->id)->with('status_type', 'success')->with('status', 'Mitarbeiter in den Papierkorb verschoben. Bisherige Einladungszugänge sind gesperrt.');
    }

    /** Neue Inhaberaktion mit Fassungskontrolle; keine alten Zugangsnachweise aktivieren. */
    public function restore(Request $request, PartnerContext $context)
    {
        [$partner, $employee] = $this->record($request, $context, true);
        Gate::authorize('restore', $employee);
        $input = $request->validate(['version' => ['required', 'integer']]);
        app(EmployeeManagement::class)->restore($employee, (int) $input['version']);

        return redirect()->route('employees.show', [$partner->id, $employee->uuid])->with('status_type', 'success')->with('status', 'Mitarbeiter wiederhergestellt. Alte Einladungszugänge bleiben gesperrt.');
    }

    private function record(Request $request, PartnerContext $context, bool $trashed = false): array
    {
        $partner = Partner::findOrFail($context->id());
        Gate::authorize('viewAny', [Employee::class, $partner]);
        $query = $trashed ? Employee::onlyTrashed() : Employee::query();
        $employee = $query->where('uuid', (string) $request->route('employee'))->firstOrFail();
        Gate::authorize('view', $employee);

        return [$partner, $employee];
    }

    /** Private Einladungsentwürfe sind keine Quelle für die Inhaberpflege. */
    private function values(Partner $partner, Employee $employee, string $profile): array
    {
        $invitation = EmployeeInvitation::where('partner_id', $partner->id)->where('employee_id', $employee->id)->latest('id')->first();
        $personal = $employee->personal_data ?: ($invitation?->submitted_data ?? []);
        if ($employee->status === 'submitted') {
            $personal = $invitation?->submitted_data ?? $personal;
        }
        $personal['person'] = array_merge(['first_name' => $employee->identity['first_name'], 'last_name' => $employee->identity['last_name'], 'country_code' => 'DE'], $personal['person'] ?? []);

        return ['choices' => EmployeeChoices::forPartner(), 'partner' => $partner, 'employee' => $employee, 'profile' => $profile, 'stations' => Station::get(),
            'station_id' => $employee->station_id, 'email' => $employee->identity['email'], 'personnel_number' => $employee->personnel_number, 'personal_data' => $personal, 'employer_data' => $employee->employer_data ?? [], 'reason' => ''];
    }
}
