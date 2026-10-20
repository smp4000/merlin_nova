<?php

namespace App\Http\Controllers;

use App\Employees\EmployeeChoices;
use App\Employees\EmployeeFields;
use App\Employees\EmployeeOnboarding;
use App\Models\Employee;
use App\Models\EmployeeInvitation;
use App\Models\EmployeeReview;
use App\Models\Partner;
use App\Models\Station;
use App\Support\RegistrationMail;
use App\Tenancy\PartnerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Inhaberoberfläche für Personalakten. Keine Nutzung einer globalen Plattform-
 * freigabe. UUID-Auflösung findet erst im bereits geprüften Partnerkontext statt.
 * Validierungsfehler rendern direkt: vertrauliche Formulare wandern nicht als
 * old input in Flash-/Sitzungsdaten. Die Fachaktionen prüfen erneut unter Sperre.
 */
class EmployeeController extends Controller
{
    public function index(Request $request, PartnerContext $context)
    {
        $partner = Partner::findOrFail($context->id());
        Gate::authorize('viewAny', [Employee::class, $partner]);

        return view('employees.index', ['partner' => $partner, 'employees' => Employee::latest('id')->paginate(20), 'stations' => Station::pluck('name', 'id')]);
    }

    public function create(Request $request, PartnerContext $context)
    {
        $partner = Partner::findOrFail($context->id());
        Gate::authorize('create', [Employee::class, $partner]);
        if ($request->isMethod('post')) {
            try {
                $data = Validator::make($request->all(), [
                    'creation_key' => ['required', 'uuid'], 'first_name' => ['required', 'string', 'max:80'],
                    'last_name' => ['required', 'string', 'max:80'], 'email' => ['required', 'email:rfc', 'max:160'],
                    'profile' => ['required', Rule::in(array_keys(EmployeeFields::PROFILES))], 'station_id' => ['required', 'integer'],
                    'partner_id' => ['prohibited'], 'role' => ['prohibited'], 'user_id' => ['prohibited'],
                ])->validate();
                abort_unless(in_array($data['creation_key'], $request->session()->get('employee_creation.'.$partner->id, []), true), 419);
                $employee = app(EmployeeOnboarding::class)->create($partner, $data);

                return redirect()->route('employees.show', [$partner->id, $employee->uuid])->with('status_type', 'success')->with('status', 'Mitarbeiter vorbereitet. Die Einladung wird erst nach Ihrer Versandaktion gesendet.');
            } catch (ValidationException $error) {
                return response()->view('employees.create', ['partner' => $partner, 'stations' => Station::get(), 'creationKey' => $request->input('creation_key'), 'fieldErrors' => $error->errors()], 422);
            }
        }
        $key = (string) Str::uuid();
        $request->session()->put('employee_creation.'.$partner->id, array_slice([...$request->session()->get('employee_creation.'.$partner->id, []), $key], -10));

        return view('employees.create', ['partner' => $partner, 'stations' => Station::get(), 'creationKey' => $key]);
    }

    public function show(Request $request, PartnerContext $context)
    {
        [$partner, $employee] = $this->record($request, $context);

        return view('employees.show', $this->viewData($partner, $employee));
    }

    public function update(Request $request, PartnerContext $context)
    {
        [$partner, $employee] = $this->record($request, $context);
        Gate::authorize('update', $employee);
        try {
            $input = Validator::make($request->all(), [
                'version' => ['required', 'integer'], 'submission_version' => ['nullable', 'integer'],
                'action' => ['required', Rule::in(['save', 'return', 'approve'])], 'data' => ['present', 'array'],
                'personnel_number' => ['nullable', 'string', 'max:20'], 'station_id' => ['required', 'integer'],
                'question' => ['nullable', 'string', 'max:1500'], 'confirmed' => ['nullable', Rule::in(['1'])],
                'partner_id' => ['prohibited'], 'role' => ['prohibited'], 'personal_data' => ['prohibited'], 'profile' => ['prohibited'],
            ])->validate();
            app(EmployeeOnboarding::class)->review($employee, $input);
        } catch (ValidationException $error) {
            return response()->view('employees.show', $this->viewData($partner, $employee) + ['fieldErrors' => $error->errors(), 'employerValues' => $request->array('data'), 'personnelValue' => is_string($request->input('personnel_number')) ? $request->input('personnel_number') : '', 'questionValue' => is_string($request->input('question')) ? $request->input('question') : ''], 422);
        }

        return redirect()->route('employees.show', [$partner->id, $employee->uuid])->with('status_type', 'success')->with('status', match ($request->input('action')) {
            'approve' => 'Die eingereichte Fassung wurde geprüft und bestätigt. Es wurde nichts an DATEV übermittelt.',
            'return' => 'Rückfrage gespeichert. Der Mitarbeiter kann in seiner bestehenden Sitzung nachbessern; bei abgelaufenem Zugang erneut einladen.',
            default => 'Arbeitgeberangaben gespeichert.',
        });
    }

    public function invite(Request $request, PartnerContext $context)
    {
        [$partner, $employee] = $this->record($request, $context);
        Gate::authorize('update', $employee);
        // Erst nach Mandanten- und Rollenprüfung zum eigenen Datensatz zurückführen.
        // Keine vertraulichen Eingaben als old input in die Sitzung übernehmen.
        try {
            $input = $request->validate(['version' => ['required', 'integer'], 'confirmed' => ['required', 'accepted']]);
            $sent = app(EmployeeOnboarding::class)->invite($employee, (int) $input['version']);
        } catch (ValidationException) {
            return redirect()->route('employees.show', [$partner->id, $employee->uuid])
                ->with('status_type', 'error')->with('status', 'Bitte den Empfänger ausdrücklich bestätigen und die Einladung erneut auslösen.');
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 503], true)) {
                throw $error;
            }

            return redirect()->route('employees.show', [$partner->id, $employee->uuid])->with('status_type', 'error')
                ->with('status', $error->getStatusCode() === 503
                    ? 'Der E-Mail-Versand ist nicht verfügbar. Bitte die SMTP-Einstellungen im Superadmin vervollständigen.'
                    : 'Die Akte wurde inzwischen geändert oder eine Einladung läuft bereits. Bitte den aktuellen Status prüfen und erneut versuchen.');
        }
        $mail = app(RegistrationMail::class);
        $invitation = EmployeeInvitation::where('partner_id', $partner->id)->where('employee_id', $employee->id)->latest('id')->first();

        return redirect()->route('employees.show', [$partner->id, $employee->uuid])
            ->with('status_type', $sent ? ($mail->usesSmtp() ? 'success' : 'info') : 'error')
            ->with('status', $sent
                ? ($mail->usesSmtp() ? 'Einladung an den Mailserver übergeben. Der Empfang ist damit noch nicht bestätigt.' : 'Einladung im lokalen Mailprotokoll abgelegt. Es wurde keine echte E-Mail gesendet.')
                : $mail->deliveryFailureMessage($invitation?->failure_code).' Die Akte und der Entwurf bleiben gespeichert.');
    }

    public function revoke(Request $request, PartnerContext $context)
    {
        [$partner, $employee] = $this->record($request, $context);
        app(EmployeeOnboarding::class)->revoke($employee);

        return redirect()->route('employees.show', [$partner->id, $employee->uuid])->with('status_type', 'success')->with('status', 'Einladungslink und zugehörige Sitzung widerrufen.');
    }

    private function record(Request $request, PartnerContext $context): array
    {
        $partner = Partner::findOrFail($context->id());
        Gate::authorize('viewAny', [Employee::class, $partner]);
        $employee = Employee::where('uuid', (string) $request->route('employee'))->firstOrFail();
        Gate::authorize('view', $employee);

        return [$partner, $employee];
    }

    private function viewData(Partner $partner, Employee $employee): array
    {
        return ['choices' => EmployeeChoices::forPartner(), 'partner' => $partner, 'employee' => $employee, 'stations' => Station::get(),
            'invitation' => EmployeeInvitation::where('partner_id', $partner->id)->where('employee_id', $employee->id)->latest('id')->first(),
            'reviews' => EmployeeReview::where('employee_id', $employee->id)->latest('id')->limit(20)->get()];
    }
}
