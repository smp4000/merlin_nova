<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response('Merlin Nova – lokale Entwicklungsgrundlage. Partnerregistrierung folgt.', 200)
        ->header('Content-Type', 'text/plain; charset=UTF-8');
});
