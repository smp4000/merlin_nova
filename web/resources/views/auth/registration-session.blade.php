<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Partnerregistrierung · Merlin Nova</title>
    <link rel="stylesheet" href="{{ asset('css/nova-landing.css') }}">
    <link rel="stylesheet" href="{{ asset('css/nova-registration.css') }}">
</head>
<body class="registration-body">
<header class="site-header"><div class="wrap navigation"><a class="brand" href="{{ route('home') }}"><span class="brand-mark">M</span><strong>Merlin Nova</strong></a></div></header>
<main class="wrap registration-main">
    <p class="eyebrow">Kostenlos testen</p>
    <h1>Sie sind bereits angemeldet.</h1>
    <section class="registration-card">
        <p>Die Partnerregistrierung erstellt einen neuen, getrennten Zugang. Ihr bestehendes Konto bleibt unverändert.</p>
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <input type="hidden" name="next" value="register">
            <button class="button primary" type="submit">Abmelden und Partner registrieren</button>
        </form>
        <p>Sie werden abgemeldet und gelangen direkt zum Registrierungsformular. Ihr vorhandener Partnerbereich bleibt erhalten.</p>
        <h2>Alternativ parallel registrieren</h2>
        <p>Öffnen Sie ein privates Browserfenster und rufen Sie dort diese Adresse auf:</p>
        <p><a href="{{ route('register') }}">{{ route('register') }}</a></p>
        <p>So bleiben Sie hier angemeldet und können dort einen neuen Partner registrieren.</p>
        <a class="button primary" href="{{ route('workspace') }}">Zum bestehenden Zugang</a>
        <a href="{{ route('home') }}">Zur Landingpage</a>
    </section>
</main>
</body>
</html>
