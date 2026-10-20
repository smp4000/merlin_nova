{{-- Nur feste freigegebene Vorschläge, keine Werte aus anderen Partnern. --}}
@foreach(\App\Stations\StationCatalogs::suggestions() as $catalogKey=>$suggestions)
<datalist id="station-options-{{ $catalogKey }}">@foreach($suggestions as $suggestion)<option value="{{ $suggestion }}"></option>@endforeach</datalist>
@endforeach
