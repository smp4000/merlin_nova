<?php

namespace App\Http\Controllers;

use App\Employees\EmployeeChoices;
use App\Models\Employee;
use App\Models\EmployeeChoice;
use App\Models\Partner;
use App\Tenancy\PartnerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Nur Inhaber erweitern gemeinsame Auswahltexte; Mitarbeiter behalten freie Eingabe. */
class EmployeeChoiceController extends Controller
{
    public function index(PartnerContext $context)
    {
        $partner = Partner::findOrFail($context->id());
        Gate::authorize('viewAny', [Employee::class, $partner]);

        return view('employees.choices', ['partner' => $partner, 'options' => EmployeeChoice::orderBy('category')->orderBy('label')->get()]);
    }

    public function store(Request $request, PartnerContext $context)
    {
        $partner = Partner::findOrFail($context->id());
        Gate::authorize('create', [Employee::class, $partner]);
        $input = $request->validate(['category' => ['required', Rule::in(array_keys(EmployeeChoices::CATEGORIES))],
            'label' => ['required', 'string', 'max:160', 'not_regex:/[<>\x00-\x1f]/u']]);
        $label = trim(preg_replace('/\s+/u', ' ', $input['label']));
        abort_if($label === '', 422, 'Bitte einen Auswahltext angeben.');
        DB::transaction(function () use ($partner, $input, $label): void {
            $partner = Partner::lockForUpdate()->findOrFail($partner->id);
            Gate::authorize('create', [Employee::class, $partner]);
            $hash = hash('sha256', mb_strtolower($label));
            if (! EmployeeChoice::where('category', $input['category'])->where('identity_hash', $hash)->exists()) {
                abort_if(EmployeeChoice::count() >= 500, 422, 'Die Partnerliste ist voll. Bitte die vorhandenen Vorschläge verwenden.');
                EmployeeChoice::forceCreate(['category' => $input['category'], 'identity_hash' => $hash, 'label' => $label]);
            }
        });

        return redirect()->route('employees.choices', $partner->id)->with('status_type', 'success')->with('status', 'Auswahl ergänzt. Bestehende Mitarbeiterangaben bleiben erhalten.');
    }
}
