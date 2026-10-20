<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Plattformweiter SMTP-Zugang, ausdrücklich kein Partnerdatensatz.
 * Ausschließlich aktive bestätigte Superadmins dürfen ihn über den Mailservice
 * ändern. Das Singleton mit ID 1 speichert das Passwort verschlüsselt mit dem
 * Anwendungsschlüssel; Backups benötigen deshalb weiterhin Geheimnisschutz.
 * Keine Massenzuweisung und keine Passwortserialisierung in HTML/JSON.
 */
class PlatformMailSetting extends Model
{
    protected $guarded = ['*'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'encrypted', 'enabled' => 'boolean', 'port' => 'integer'];
    }
}
