<x-filament-panels::page>
@include('shared.notifications')
    <style>.nova-mail{max-width:760px;background:#fff;color:#152d3b;border:1px solid #dbe6ed;border-radius:12px;padding:24px}.nova-mail label{display:block;margin:18px 0 6px;font-weight:600}.nova-mail input:not([type=checkbox]){width:100%;min-width:0;padding:12px;border:1px solid #b8ccd5;border-radius:6px;font:inherit}.nova-mail button{background:#008f9c;color:white;padding:12px 18px;border-radius:6px;margin-top:20px;white-space:normal;cursor:pointer}.nova-mail p{margin:12px 0;line-height:1.6}.nova-mail :focus-visible{outline:3px solid #83d5dc;outline-offset:2px}</style>
    <section class="nova-mail">
        <h2>STRATO · Registrierung und E-Mail-Bestätigung</h2>
        <p>Server: smtp.strato.de · {{ $settings['port'] === 587 ? 'Port 587 · verpflichtendes STARTTLS' : 'Port 465 · direktes TLS' }} · Zertifikatsprüfung aktiv.</p>
        <p>Aktuell: <strong>{{ $settings['enabled'] ? 'Echter SMTP-Versand aktiviert' : 'Lokales Mailprotokoll' }}</strong>. Passwort {{ $settings['has_password'] ? 'hinterlegt' : 'noch nicht hinterlegt' }}.</p>
        
        @if($errors->any())<div role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="post" action="{{ route('mail-settings.save') }}" autocomplete="off">
            @csrf
            <label for="smtp-port">Verschlüsselte Verbindung</label>
            <select id="smtp-port" name="port"><option value="465" @selected($settings['port'] === 465)>Port 465 · direktes TLS</option><option value="587" @selected($settings['port'] === 587)>Port 587 · verpflichtendes STARTTLS</option></select>
            <label for="smtp-user">SMTP-Benutzername (Postfachadresse)</label><input id="smtp-user" name="username" type="email" required maxlength="255" value="{{ $settings['username'] }}">
            <label for="smtp-from">Absenderadresse (gleiches Postfach)</label><input id="smtp-from" name="from_address" type="email" required maxlength="255" value="{{ $settings['from_address'] }}">
            <label for="smtp-name">Absendername</label><input id="smtp-name" name="from_name" required maxlength="100" value="{{ $settings['from_name'] }}">
            <label for="smtp-password">SMTP-Passwort</label><input id="smtp-password" name="smtp_password" type="password" autocomplete="new-password" maxlength="1024" aria-describedby="password-help">
            <p id="password-help">Leer lassen, um das vorhandene Passwort zu behalten. Es wird verschlüsselt gespeichert und niemals zurück ins Formular geladen.</p>
            <label><input type="checkbox" name="enabled" value="1" @checked($settings['enabled'])> Echten SMTP-Versand aktivieren</label>
            <p>Registrierungsnachrichten gehen dann an die jeweils registrierte E-Mail-Adresse. Bis zur Aktivierung bleiben sie im lokalen Protokoll. Änderungen an diesem Formular zuerst speichern.</p>
            <button type="submit">Einstellungen speichern</button>
        </form>
        @if($settings['enabled'])<form method="post" action="{{ route('mail-settings.test') }}">@csrf<button type="submit">Testmail an gespeicherte Absenderadresse senden</button></form>@endif
        <p>Bestätigungslinks führen weiterhin auf diese lokale Nova-Kopie. Öffnen Sie diese auf demselben Rechner mit dem zugehörigen angemeldeten Konto.</p>
    </section>
</x-filament-panels::page>
