@extends('layouts.partner')
@section('content')
<h1>Ihre Partnerzugänge</h1><p class="registration-intro">Wählen Sie Ihre Firma, um Stationen und Teststatus zu sehen.</p>
@forelse($memberships as $membership)<section class="registration-card"><h2><a href="{{ route('partner.overview', $membership->partner_id) }}">{{ $membership->partner->name }}</a></h2><p>{{ $membership->role->label() }}</p>
@if($membership->partner->registration_owner_id)<p>{{ $membership->partner->allowsOperationalWrites() ? 'Kostenloser Test aktiv bis '.$membership->partner->trial_ends_at->timezone('Europe/Berlin')->format('d.m.Y H:i').' Uhr (Europe/Berlin)' : 'Test noch nicht gestartet oder abgelaufen – nur lesender Zugriff.' }}</p>@endif
</section>@empty<p>Für Ihren Zugang ist noch kein Partner freigegeben.</p>@endforelse
@if(auth()->user()->is_super_admin)<p><a href="{{ url('/superadmin') }}">Plattformverwaltung öffnen</a></p>@endif
@endsection
