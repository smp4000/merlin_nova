@if(!empty($fieldErrors))
<div class="employee-errors" role="alert"><strong>Bitte prüfen Sie die Angaben.</strong><ul>@foreach($fieldErrors as $messages)@foreach($messages as $message)<li>{{ $message }}</li>@endforeach @endforeach</ul><p>Bitte korrigieren Sie die markierten Angaben. Vertrauliche Eingaben werden nicht in der Sitzung zwischengespeichert.</p></div>
@endif
