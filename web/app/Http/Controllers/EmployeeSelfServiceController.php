<?php

namespace App\Http\Controllers;

use App\Employees\EmployeeOnboarding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * E-Mail-Fähigkeitszugang ohne Benutzer-/Partnerrolle. Die Startseite enthält keine
 * Personendaten und löst keinen GET-Seiteneffekt aus. Ein Linkscanner aktiviert
 * nichts. Sitzungszugriff, Ablauf, Widerruf und Teststatus prüft der Fachservice.
 */
class EmployeeSelfServiceController extends Controller
{
    public function show(Request $request)
    {
        $uuid = (string) $request->route('invitation');
        if (! $request->session()->has('employee_access.'.$uuid)) {
            return view('employees.public.start', compact('uuid'));
        }

        return view('employees.public.form', app(EmployeeOnboarding::class)->selfView($request, $uuid));
    }

    public function accept(Request $request)
    {
        $uuid = (string) $request->route('invitation');
        $input = Validator::make($request->all(), ['token' => ['required', 'string', 'size:64', 'regex:/^[A-Za-z0-9]+$/D']])->validate();
        app(EmployeeOnboarding::class)->accept($request, $uuid, $input['token']);

        return redirect()->route('employees.invitation', $uuid);
    }

    public function save(Request $request)
    {
        $uuid = (string) $request->route('invitation');
        $service = app(EmployeeOnboarding::class);
        // Erst den Zugang prüfen, bevor Formularfehler Informationen liefern.
        $viewData = $service->selfView($request, $uuid);
        try {
            $input = Validator::make($request->all(), [
                'wizard_step' => ['nullable', 'integer', 'between:0,5'],
                'version' => ['required', 'integer'], 'data' => ['present', 'array'],
                'action' => ['required', Rule::in(['save', 'submit'])], 'confirmed' => ['nullable', Rule::in(['1'])],
                'partner_id' => ['prohibited'], 'employee_id' => ['prohibited'], 'role' => ['prohibited'], 'employer_data' => ['prohibited'],
            ])->validate();
            $service->submit($request, $uuid, $input);
        } catch (ValidationException $error) {
            // Direktantwort statt Redirect mit vertraulichem old input. Nur bekannte
            // Felder werden von der Maske ausgewertet; HTML wird stets escaped.
            if (is_array($request->input('data'))) {
                $viewData['data'] = $request->input('data');
            }

            return response()->view('employees.public.form', $viewData + ['fieldErrors' => $error->errors()], 422);
        }

        // Nur die geprüfte Schrittnummer merken, niemals Formulardaten in Flash kopieren.
        return redirect()->route('employees.invitation', $uuid)->with('employee_wizard_step', (int) ($input['wizard_step'] ?? 0))->with('status_type', 'success')->with('status', $request->input('action') === 'submit'
            ? 'Ihre Angaben wurden zur Arbeitgeberprüfung eingereicht.' : 'Ihr Entwurf wurde gespeichert.');
    }
}
