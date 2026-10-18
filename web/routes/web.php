<?php

use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\StationController;
use App\Http\Middleware\RequireActiveUser;
use App\Http\Middleware\ResolvePartner;
use App\Models\PartnerMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response('Merlin Nova – lokale Entwicklungsgrundlage. Partnerregistrierung folgt.', 200)
        ->header('Content-Type', 'text/plain; charset=UTF-8');
});

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:10,1');
});
Route::post('/logout', [SessionController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/email/verify', fn () => response('Bitte zuerst die E-Mail-Adresse bestätigen. Der Bestätigungsversand folgt mit der Partnerregistrierung.', 403))
    ->middleware('auth')->name('verification.notice');

Route::middleware(['auth', RequireActiveUser::class, 'verified'])->group(function () {
    Route::get('/app', function (Request $request) {
        $memberships = PartnerMembership::query()->where('user_id', $request->user()->id)
            ->where('is_active', true)->whereHas('partner', fn ($query) => $query->where('is_active', true))
            ->with('partner')->get();

        return view('workspace', ['memberships' => $memberships]);
    })->name('workspace');

    Route::prefix('/app/partners/{partner}')->middleware(ResolvePartner::class)->group(function () {
        Route::get('/', [PartnerController::class, 'show']);
        Route::patch('/', [PartnerController::class, 'update']);
        Route::get('/members', [PartnerController::class, 'members']);
        Route::get('/stations', [StationController::class, 'index']);
        Route::post('/stations', [StationController::class, 'store']);
        Route::patch('/stations/{station}', [StationController::class, 'update']);
    });
});
