<?php

namespace App\Stations;

use App\Models\Station;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Private Stationsbilder: echte Rasterdaten prüfen, neu kodieren und getrennt speichern.
 *
 * Benutzerdateinamen und EXIF-Daten werden nicht übernommen. Der erzeugte Pfad
 * gehört genau zum autorisierten Partner und Standort; er wird nie als öffentliche
 * URL ausgegeben. SVG, HTML, fremde URLs und frei eingereichte Speicherpfade sind
 * ausgeschlossen. Bei fehlgeschlagener Transaktion werden nur neu erzeugte eigene
 * Dateien entfernt, keine vorhandenen Benutzerdateien.
 */
final class StationMedia
{
    /** Prüft auch die dekodierte Pixelzahl, bevor GD große Bildflächen anlegt. */
    public function prepare(Station $station, ?UploadedFile $logo, array $photos): array
    {
        $prepared = [];
        try {
            foreach (array_filter(['logo' => $logo, ...$photos]) as $key => $file) {
                $bytes = $file->get();
                $info = @getimagesizefromstring($bytes);
                if (! $info || ! in_array($info['mime'], ['image/jpeg', 'image/png'], true) || $info[0] * $info[1] > 20000000) {
                    throw ValidationException::withMessages(['photos' => 'Bitte verwenden Sie echte JPG-/PNG-Bilder mit höchstens 20 Megapixeln.']);
                }
                $image = @imagecreatefromstring($bytes);
                if ($image === false) {
                    throw ValidationException::withMessages(['photos' => 'Das Bild konnte nicht sicher verarbeitet werden.']);
                }
                $extension = $info['mime'] === 'image/png' ? 'png' : 'jpg';
                ob_start();
                try {
                    if ($extension === 'png') {
                        imagesavealpha($image, true);
                        $encoded = imagepng($image, null, 6);
                    } else {
                        $encoded = imagejpeg($image, null, 90);
                    }
                    $output = ob_get_contents();
                } finally {
                    ob_end_clean();
                    imagedestroy($image);
                }
                if (! $encoded) {
                    throw ValidationException::withMessages(['photos' => 'Das Bild konnte nicht gespeichert werden.']);
                }
                if (strlen($output) > ($key === 'logo' ? 2 : 5) * 1024 * 1024) {
                    throw ValidationException::withMessages(['photos' => 'Das neu verarbeitete Bild ist zu groß. Bitte verkleinern Sie es vor dem Hochladen.']);
                }
                $id = (string) Str::uuid();
                $path = 'station-media/'.$station->partner_id.'/'.$station->id.'/'.$id.'.'.$extension;
                if (! Storage::disk('local')->put($path, $output)) {
                    throw ValidationException::withMessages(['photos' => 'Das Bild konnte nicht gespeichert werden.']);
                }
                $prepared[] = ['id' => $id, 'path' => $path, 'kind' => $key === 'logo' ? 'logo' : 'photo', 'mime' => $info['mime']];
            }
        } catch (\Throwable $error) {
            self::discard($prepared);
            throw $error;
        }

        return $prepared;
    }

    /** Nur transaktionslos neu angelegte Dateien aus diesem Aufruf bereinigen. */
    public static function discard(array $prepared): void
    {
        foreach ($prepared as $item) {
            Storage::disk('local')->delete($item['path']);
        }
    }
}
