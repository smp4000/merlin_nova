<!doctype html>
<html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Arbeitsbereich · Merlin Nova</title>
<style>body{margin:0;background:#f3f6f8;color:#172b3a;font:16px/1.6 system-ui,sans-serif}main{padding:32px}header{display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #dbe3ea;margin-bottom:24px}section{background:white;padding:24px;border:1px solid #dbe3ea;border-radius:12px}a{color:#1266c4}button{padding:10px 18px;background:white;border:1px solid #cbd5e1;border-radius:8px;cursor:pointer}li{padding:8px}</style>
</head><body><main><header><h1>Merlin Nova</h1><form method="post" action="{{ route('logout') }}">@csrf<button>Abmelden</button></form></header>
<section><h2>Ihre Partnerzugänge</h2><p>Diese technische Übersicht zeigt nur ausdrücklich freigegebene Partner. Die eigentliche Partneroberfläche folgt.</p>
<ul>@forelse ($memberships as $membership)<li><a href="{{ url('/app/partners/'.$membership->partner_id) }}">{{ $membership->partner->name }}</a> · {{ $membership->role->label() }}</li>@empty<li>Für Ihren Zugang ist noch kein Partner freigegeben.</li>@endforelse</ul>
@if (auth()->user()->is_super_admin)<p><a href="{{ url('/superadmin') }}">Plattformverwaltung öffnen</a></p>@endif
</section></main></body></html>
