{{-- Nur feste Statusmeldungen und Validierungshinweise, keine Eingabewerte spiegeln. --}}
@once
<link rel="stylesheet" href="{{ asset('css/nova-notifications.css') }}">
<script src="{{ asset('js/nova-notifications.js') }}" defer></script>
@endonce
@php
    $notificationType = in_array(session('status_type'), ['success', 'error', 'warning', 'info'], true) ? session('status_type') : 'info';
    $hasValidationErrors = $errors->any() || !empty($fieldErrors);
@endphp
<div class="nova-notifications" aria-label="Benachrichtigungen">
@if(session('status'))
<div class="nova-notification is-{{ $notificationType }}" role="{{ $notificationType === 'error' ? 'alert' : 'status' }}">
<span class="nova-notification-icon" aria-hidden="true">{{ $notificationType === 'success' ? '✓' : ($notificationType === 'error' ? '!' : 'i') }}</span>
<div><strong>{{ ['success'=>'Erfolgreich', 'error'=>'Aktion nicht abgeschlossen', 'warning'=>'Bitte beachten', 'info'=>'Hinweis'][$notificationType] }}</strong><p>{{ session('status') }}</p></div>
<button type="button" data-dismiss-notification aria-label="Benachrichtigung schließen">×</button></div>
@endif
@if($hasValidationErrors)
<div class="nova-notification is-error" role="alert"><span class="nova-notification-icon" aria-hidden="true">!</span><div><strong>Angaben prüfen</strong><p>Die Aktion wurde nicht abgeschlossen. Bitte beachten Sie die Hinweise im Formular.</p></div><button type="button" data-dismiss-notification aria-label="Benachrichtigung schließen">×</button></div>
@endif
@if(session('source_message'))
<div class="nova-notification is-warning" role="status"><span class="nova-notification-icon" aria-hidden="true">i</span><div><strong>Datenquelle</strong><p>{{ session('source_message') }}</p></div><button type="button" data-dismiss-notification aria-label="Benachrichtigung schließen">×</button></div>
@endif
</div>
