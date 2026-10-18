<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Anmelden · Merlin Nova</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f3f6f8;color:#172b3a;font:16px/1.6 system-ui,sans-serif}main{max-width:460px;margin:10vh auto;padding:32px;background:white;border:1px solid #e2e8ef;border-radius:16px}h1{font-size:26px;margin:0 0 8px}p{color:#526575}label{display:block;margin-top:20px;font-weight:600}input,button{width:100%;padding:12px;font:inherit;border-radius:8px;border:1px solid #cbd5e1}button{margin-top:24px;background:#1266c4;color:white;border:0;cursor:pointer}a{color:#1266c4}:focus-visible{outline:3px solid #80bfff;outline-offset:3px}.error{color:#a52b25}@media(max-width:500px){main{margin:24px 12px;padding:24px}}
    </style>
</head>
<body><main>
    <h1>Merlin Nova</h1><p>Willkommen zurück. Melden Sie sich mit Ihrem persönlichen Zugang an.</p>
    @if ($errors->any())<p class="error" role="alert">{{ $errors->first() }}</p>@endif
    <form method="post" action="{{ route('login') }}">
        @csrf
        <label for="email">E-Mail-Adresse</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" maxlength="255">
        <label for="password">Passwort</label><input id="password" name="password" type="password" required autocomplete="current-password" maxlength="1024">
        <button type="submit">Anmelden</button>
    </form>
    <p>Lokale Entwicklungsumgebung. Die öffentliche Registrierung folgt.</p>
</main></body></html>
