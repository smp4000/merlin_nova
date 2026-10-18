<?php

use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\StationController;
use App\Http\Middleware\RequireActiveUser;
use App\Http\Middleware\ResolvePartner;
use App\Models\PartnerMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/register', [RegistrationController::class, 'show'])->name('register');

Route::middleware('guest')->group(function () {
    Route::post('/register', [RegistrationController::class, 'store'])->middleware('throttle:5,1');
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:10,1');
});
Route::post('/logout', [SessionController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/email/verify', [RegistrationController::class, 'notice'])
    ->middleware(['auth', RequireActiveUser::class])->name('verification.notice');
Route::post('/email/verification-notification', [RegistrationController::class, 'resend'])
    ->middleware(['auth', RequireActiveUser::class, 'throttle:3,1'])->name('verification.send');
Route::get('/email/verify/{id}/{hash}', [RegistrationController::class, 'verify'])
    ->middleware(['auth', RequireActiveUser::class, 'signed', 'throttle:6,1'])->name('verification.verify');

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
