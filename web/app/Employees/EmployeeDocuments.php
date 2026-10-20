<?php

namespace App\Employees;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeInvitation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Lokale, private Nachweise ohne OCR, Drittanbieter oder Gesundheitsunterlagen.
 * Aufrufer halten Partner-/Aktensperre und prüfen Inhaber bzw. Einladungsgrant.
 * Auch entfernte Dateien zählen zum Kontingent, damit wiederholtes Entfernen
 * keine unbegrenzte Ablage schafft. Physische Löschung braucht ein Löschkonzept.
 */
class EmployeeDocuments
{
    public function visible(Employee $employee, ?EmployeeInvitation $invitation)
    {
        return EmployeeDocument::where('employee_id', $employee->id)
            ->when($invitation, fn ($query) => $query->where('invitation_id', $invitation->id),
                fn ($query) => $query->where(fn ($q) => $q->whereNull('invitation_id')->orWhereNotNull('submitted_at')))
            ->latest()->get();
    }

    public function store(Employee $employee, ?EmployeeInvitation $invitation, UploadedFile $file): void
    {
        $all = EmployeeDocument::withTrashed()->where('employee_id', $employee->id);
        $this->check($all->count() < 20 && $all->sum('bytes') + $file->getSize() <= 40 * 1024 * 1024, 'Das Nachweiskontingent ist ausgeschöpft. Bitte den Arbeitgeber kontaktieren.');
        $this->check($file->isValid() && $file->getSize() <= 2 * 1024 * 1024, 'Bitte eine Datei bis 2 MiB auswählen.');
        $mime = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());
        $this->check(in_array($mime, ['image/jpeg', 'image/png', 'application/pdf'], true)
            && in_array($extension, match ($mime) {
                'image/jpeg' => ['jpg', 'jpeg'], 'image/png' => ['png'], default => ['pdf']
            }, true), 'Nur echte JPEG-/PNG-Bilder oder PDF-Dateien sind erlaubt.');
        $content = file_get_contents($file->getRealPath());
        if ($mime === 'application/pdf') {
            // Konservativer Inhaltsfilter, kein Ersatz für einen Malware-Scanner.
            // PDF-Namensescapes werden vor der Prüfung auf aktive Namen aufgelöst.
            $decoded = preg_replace_callback('/#([0-9a-f]{2})/i', fn ($m) => chr(hexdec($m[1])), $content);
            $this->check(str_starts_with($content, '%PDF-') && str_contains(substr($content, -1024), '%%EOF')
                && ! preg_match('~/(?:JavaScript|JS|Launch|EmbeddedFiles|RichMedia|XFA|OpenAction|AA)\b~i', $decoded), 'Dieses PDF kann nicht angenommen werden. Bitte ein einfaches PDF ohne aktive Inhalte verwenden.');
        } else {
            $size = @getimagesizefromstring($content);
            // Speicher vor GD-Dekodierung begrenzen; Kamerabilder lokal verkleinern.
            $this->check($size && $size[0] > 0 && $size[1] > 0 && $size[0] <= 5000 && $size[1] <= 5000 && $size[0] * $size[1] <= 4000000, 'Bitte das Bild vor dem Upload auf höchstens 4 Megapixel verkleinern.');
            $image = @imagecreatefromstring($content);
            $this->check($image !== false, 'Das Bild konnte nicht gelesen werden.');
            ob_start();
            $mime === 'image/jpeg' ? imagejpeg($image, null, 85) : imagepng($image);
            $content = ob_get_clean();
            imagedestroy($image);
        }
        $this->check(strlen($content) <= 2 * 1024 * 1024 && $all->sum('bytes') + strlen($content) <= 40 * 1024 * 1024, 'Die aufbereitete Datei ist zu groß. Bitte ein kleineres Foto verwenden.');
        $uuid = (string) Str::uuid();
        $path = $employee->partner_id.'/'.$employee->id.'/'.$uuid;
        $disk = $this->disk();
        $disk->put($path, Crypt::encryptString($content));
        try {
            $record = new EmployeeDocument;
            $record->forceFill(['uuid' => $uuid, 'employee_id' => $employee->id, 'invitation_id' => $invitation?->id,
                'actor_id' => $invitation ? null : auth()->id(), 'storage_path' => $path,
                'original_name' => $file->getClientOriginalName(), 'mime' => $mime, 'bytes' => strlen($content)])->save();
        } catch (\Throwable $error) {
            $disk->delete($path);
            throw $error;
        }
    }

    public function disk()
    {
        return Storage::build(['driver' => 'local', 'root' => config('employee_documents.storage_root', storage_path('app/employee-documents')), 'throw' => true, 'serve' => false]);
    }

    private function check(bool $valid, string $message): void
    {
        if (! $valid) {
            throw ValidationException::withMessages(['document' => $message]);
        }
    }
}
