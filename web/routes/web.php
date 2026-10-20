<?php

use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\BankDirectoryController;
use App\Http\Controllers\EmployeeChoiceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeeManagementController;
use App\Http\Controllers\EmployeeSelfServiceController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MailSettingsController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PostalPlaceController;
use App\Http\Controllers\StationCompetitionController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\StationDetailsController;
use App\Http\Controllers\StationSourceController;
use App\Http\Controllers\StationWizardController;
use App\Http\Middleware\ProtectEmployeePages;
use App\Http\Middleware\RequireActiveUser;
use App\Http\Middleware\ResolvePartner;
use App\Models\PartnerMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware([ProtectEmployeePages::class, 'throttle:30,1,employee-public'])->prefix('/employee-invitations/{invitation}')->whereUuid('invitation')->group(function () {
    Route::match(['get', 'post'], '/documents', [EmployeeDocumentController::class, 'self'])->name('employees.self.documents');
    Route::match(['get', 'post'], '/documents/{document}', [EmployeeDocumentController::class, 'self'])->whereUuid('document');
    Route::get('/', [EmployeeSelfServiceController::class, 'show'])->name('employees.invitation');
    Route::post('/accept', [EmployeeSelfServiceController::class, 'accept'])->middleware('throttle:5,1,employee-accept')->name('employees.accept');
    Route::post('/', [EmployeeSelfServiceController::class, 'save'])->name('employees.self.save');
});

Route::get('/reference/banks', [BankDirectoryController::class, 'search'])->middleware('throttle:60,1,bank-reference');
Route::get('/reference/banks/{blz}', [BankDirectoryController::class, 'lookup'])->where('blz', '[0-9]{8}')->middleware('throttle:60,1,bank-reference');

Route::get('/', LandingController::class)->name('home');
Route::post('/superadmin/email/settings', [MailSettingsController::class, 'save'])
    ->middleware(['auth', RequireActiveUser::class, 'verified', 'can:manage-mail', 'throttle:10,1'])->name('mail-settings.save');
Route::post('/superadmin/email/test', [MailSettingsController::class, 'test'])
    ->middleware(['auth', RequireActiveUser::class, 'verified', 'can:manage-mail', 'throttle:3,1'])->name('mail-settings.test');
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
        Route::middleware(ProtectEmployeePages::class)->group(function () {
            Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
            Route::match(['get', 'post'], '/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
            Route::get('/employees/choices', [EmployeeChoiceController::class, 'index'])->name('employees.choices');
            Route::post('/employees/choices', [EmployeeChoiceController::class, 'store'])->name('employees.choices.store');
            Route::get('/employees/trash', [EmployeeManagementController::class, 'trashIndex'])->name('employees.trash.index');
            Route::match(['get', 'post'], '/employees/{employee}/edit', [EmployeeManagementController::class, 'edit'])->name('employees.edit');
            Route::post('/employees/{employee}/trash', [EmployeeManagementController::class, 'trash'])->name('employees.trash');
            Route::post('/employees/{employee}/restore', [EmployeeManagementController::class, 'restore'])->name('employees.restore');
            Route::match(['get', 'post'], '/employees/{employee}/documents', [EmployeeDocumentController::class, 'owner'])->name('employees.documents');
            Route::match(['get', 'post'], '/employees/{employee}/documents/{document}', [EmployeeDocumentController::class, 'owner'])->whereUuid('document');
            Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
            Route::post('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
            Route::post('/employees/{employee}/invite', [EmployeeController::class, 'invite'])->middleware('throttle:3,1,employee-invite')->name('employees.invite');
            Route::post('/employees/{employee}/revoke', [EmployeeController::class, 'revoke'])->name('employees.revoke');
        });
        Route::get('/overview', [PartnerController::class, 'overview'])->name('partner.overview');
        Route::post('/postal-place', [PostalPlaceController::class, 'lookup'])->middleware('throttle:10,1')->name('partner.postal-place');
        Route::get('/stations/create', [StationWizardController::class, 'show'])->name('stations.create');
        Route::post('/stations/create', [StationWizardController::class, 'advance']);
        Route::post('/stations/{station}/source', [StationSourceController::class, 'refresh'])->middleware('throttle:10,1')->name('stations.source.refresh');
        Route::get('/stations/{station}/source/setup', [StationSourceController::class, 'configure'])->name('stations.source.configure');
        Route::post('/stations/{station}/source/setup', [StationSourceController::class, 'configure'])->middleware('throttle:10,1');
        Route::get('/stations/{station}/source/import', [StationSourceController::class, 'import'])->name('stations.source.import');
        Route::post('/stations/{station}/source/import', [StationSourceController::class, 'import'])->middleware('throttle:10,1');
        Route::match(['get', 'post'], '/stations/{station}/competitors', [StationCompetitionController::class, 'handle'])->middleware('throttle:30,1,station-competition')->name('stations.competition');
        Route::post('/stations/{station}/prices/refresh-all', [\App\Http\Controllers\StationPriceRefreshController::class, 'handle'])->middleware('throttle:30,1,station-prices')->block(20, 20)->name('stations.prices.refresh-all');
        Route::get('/stations/{station}/details', [StationDetailsController::class, 'show'])->name('stations.details');
        Route::patch('/stations/{station}/details', [StationDetailsController::class, 'update']);
        Route::get('/stations/{station}/images/{image}', [StationDetailsController::class, 'image'])->whereUuid('image')->name('stations.image');
        Route::get('/', [PartnerController::class, 'show']);
        Route::patch('/', [PartnerController::class, 'update']);
        Route::get('/members', [PartnerController::class, 'members']);
        Route::get('/stations', [StationController::class, 'index']);
        Route::post('/stations', [StationController::class, 'store']);
        Route::patch('/stations/{station}', [StationController::class, 'update']);
    });
});
