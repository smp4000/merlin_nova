<?php

namespace App\Http\Controllers;

use App\Employees\EmployeeDocuments;
use App\Employees\EmployeeOnboarding;
use App\Models\Employee;
use App\Models\EmployeeInvitation;
use App\Models\Partner;
use App\Tenancy\PartnerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Zwei ausdrücklich getrennte Zugänge: Inhaber im Partnerkontext oder bereits
 * aktivierte Selbstauskunft. Niemals reichen UUID oder QR allein zum Dateizugriff.
 * Alle Aktionen laufen unter derselben Partner-/Aktensperre wie das Einreichen;
 * Dateizugriff wird vor Inhaltsprüfung autorisiert. Fehler flashen keine Dateien.
 */
class EmployeeDocumentController extends Controller
{
    public function owner(Request $request, PartnerContext $context)
    {
        $employee = $request->route('employee');
        $document = $request->route('document');

        return DB::transaction(function () use ($request, $context, $employee, $document) {
            $partner = Partner::lockForUpdate()->findOrFail($context->id());
            $record = Employee::where('uuid', $employee)->lockForUpdate()->firstOrFail();
            Gate::authorize($request->isMethod('get') ? 'view' : 'update', $record);

            return $this->handle($request, $record, null, $partner, $document);
        });
    }

    public function self(Request $request, string $invitation, ?string $document = null)
    {
        return app(EmployeeOnboarding::class)->withSelfAccess($request, $invitation,
            fn ($employee, $invitation, $partner) => $this->handle($request, $employee, $invitation, $partner, $document), ! $request->isMethod('get'));
    }

    private function handle(Request $request, Employee $employee, ?EmployeeInvitation $invitation, Partner $partner, ?string $document)
    {
        $service = app(EmployeeDocuments::class);
        $base = $invitation ? route('employees.self.documents', $invitation->uuid) : route('employees.documents', [$partner->id, $employee->uuid]);
        $back = $invitation ? route('employees.invitation', $invitation->uuid) : route('employees.show', [$partner->id, $employee->uuid]);
        $documents = $service->visible($employee, $invitation);
        if ($document) {
            $record = $documents->firstWhere('uuid', $document);
            abort_unless($record, 404);
            if ($request->isMethod('get')) {
                $extension = match ($record->mime) {
                    'image/png' => 'png', 'image/jpeg' => 'jpg', default => 'pdf'
                };

                return response(Crypt::decryptString($service->disk()->get($record->storage_path)), 200, ['Content-Type' => $record->mime,
                    'Content-Disposition' => 'attachment; filename="Beschaeftigungsnachweis.'.$extension.'"', 'X-Content-Type-Options' => 'nosniff']);
            }
            abort_unless(! $invitation || ! $record->submitted_at, 403);
            Validator::make($request->all(), ['confirmed' => ['accepted']])->validate();
            $record->delete();

            return redirect($base)->with('status', 'Nachweis entfernt. Die gespeicherte Fassung bleibt bis zur geregelten Löschung geschützt.');
        }
        $errors = null;
        if ($request->isMethod('post')) {
            try {
                Validator::make($request->all(), ['document' => ['required', 'file', 'max:2048'], 'confirmed' => ['accepted']])->validate();
                $service->store($employee, $invitation, $request->file('document'));

                return redirect($base)->with('status', $invitation ? 'Nachweis gespeichert. Erst mit der Selbstauskunft wird er zur Prüfung eingereicht.' : 'Nachweis in der Personalakte gespeichert.');
            } catch (ValidationException $error) {
                $errors = $error->errors();
            }
        }

        $writable = $partner->allowsOperationalWrites() && ($invitation ? in_array($invitation->status, ['active', 'changes_requested'], true) : Gate::allows('update', $employee));

        return response()->view('employees.documents', compact('partner', 'employee', 'invitation', 'documents', 'base', 'back', 'writable') + ['documentErrors' => $errors], $errors ? 422 : 200);
    }
}
