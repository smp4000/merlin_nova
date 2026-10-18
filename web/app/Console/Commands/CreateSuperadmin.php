<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Bewusste lokale Ersteinrichtung ohne Standardkonto oder Klartextpasswort.
 *
 * Das Passwort wird verdeckt zweimal abgefragt und nicht als CLI-Argument
 * akzeptiert. Bestehende Konten werden niemals stillschweigend hochgestuft.
 * Die Bestätigung der Adresse erfolgt hier ausschließlich für den lokalen
 * Bootstrap; sie ersetzt nicht den späteren öffentlichen E-Mail-Nachweis.
 */
class CreateSuperadmin extends Command
{
    protected $signature = 'nova:superadmin {email} {--name=Plattformverwaltung}';

    protected $description = 'Neuen lokalen Superadmin ausdrücklich und ohne Standardpasswort anlegen';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Dieser Einrichtungsbefehl ist ausschließlich lokal freigegeben.');

            return self::FAILURE;
        }

        $email = Str::lower(trim((string) $this->argument('email')));
        $data = ['email' => $email, 'name' => $this->option('name')];
        $validator = Validator::make($data, ['email' => ['required', 'email', 'max:255', 'unique:users,email'], 'name' => ['required', 'string', 'max:255']]);
        if ($validator->fails()) {
            $this->error('Ungültige Angaben oder bereits vorhandenes Konto. Es wurde nichts verändert.');

            return self::FAILURE;
        }

        $password = $this->secret('Neues Passwort (mindestens 14 Zeichen, Groß-/Kleinbuchstaben, Zahl und Sonderzeichen)');
        $confirmation = $this->secret('Passwort wiederholen');
        $validator = Validator::make(['password' => $password, 'password_confirmation' => $confirmation], [
            'password' => ['required', 'confirmed', Password::min(14)->mixedCase()->numbers()->symbols()],
        ]);
        if ($validator->fails()) {
            $this->error('Die Passwortanforderungen sind nicht erfüllt oder die Wiederholung stimmt nicht.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($data, $password): void {
            $user = new User;
            $user->fill([...$data, 'password' => $password]);
            $user->forceFill(['is_super_admin' => true, 'email_verified_at' => now()]);
            $user->save();
        });
        $this->info('Lokaler Superadmin angelegt. Anmeldung unter /superadmin/login. Keine Partnerrechte vergeben.');

        return self::SUCCESS;
    }
}
