<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserManagmentController;
use App\Http\Controllers\DossierController;
use App\Http\Controllers\SecureFileController;
use App\Http\Controllers\ClientRequestController;
use App\Http\Controllers\ContactMessageController;
use Illuminate\Support\Facades\Mail;


// Public Routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/contact', [ContactMessageController::class, 'store']);
Route::post('/password-reset-request', [ContactMessageController::class, 'passwordResetRequest']);

// Authenticated Routes: Both client and admin
Route::middleware('auth:admin,client')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::middleware(['auth:client,admin'])->post('/change-password', [AuthController::class, 'changePassword']);
});

// Admin Only Routes
Route::middleware(['auth:admin'])->group(function () {
    // Dossier-related custom routes first to avoid conflict with apiResource
    Route::get('dossiers/seenadmin', [DossierController::class, 'seenAdmin']);     // GET
    Route::post('dossiers/seenadmin', [DossierController::class, 'seenAdmin']);    // POST
    

    // Other admin dossier routes
    Route::post('dossiers/{dossier}/admin-docs', [SecureFileController::class, 'uploadAdminDocs']);
    Route::patch('dossiers/{dossier}/status', [DossierController::class, 'updateStatus']);
    Route::post('dossiers/{dossier}/comments', [DossierController::class, 'addComment']);
    Route::post('dossiers/{dossier}/comments', [DossierController::class, 'addMontant']);
    Route::get('/stats/clients', [UserManagmentController::class, 'getClientStats']);
});

// Super Admin Routes
Route::middleware(['auth:admin', 'superadmin'])->group(function () {
    // User Management
    Route::prefix('users')->group(function () {
        Route::post('/admin', [UserManagmentController::class, 'createAdmin']);
        Route::post('/client', [UserManagmentController::class, 'createClient']);
        Route::post('/delete', [UserManagmentController::class, 'deleteUser']);
        Route::get('/admins', [UserManagmentController::class, 'getAdmins']);
        Route::get('/clients', [UserManagmentController::class, 'getClients']);
    });

    // Dossier extended routes
    Route::patch('dossiers/{dossier}/assign-expert', [DossierController::class, 'assignExpert']);
    Route::get('dossiers/with-experts', [DossierController::class, 'getDossiersWithExperts']);
    Route::get('dossiers/statistics', [DossierController::class, 'getStatistics']);
    Route::get('/system/stats', [UserManagmentController::class, 'getSystemStats']);
    Route::get('/contact', [ContactMessageController::class, 'index']);
    Route::get('/client-requests', [ClientRequestController::class, 'index']);
    Route::get('/client-requests/{id}', [ClientRequestController::class, 'show']);
    Route::patch('/client-requests/{id}/status', [ClientRequestController::class, 'updateStatus']);
    Route::post('/force-password-reset', [UserManagmentController::class, 'forcePasswordReset']);
    Route::delete('/contact-messages/{id}', [ContactMessageController::class, 'destroy']);

   
});



Route::match(['get', 'post'], 'dossiers/adminchange', [DossierController::class, 'adminChange']);


Route::middleware(['auth:client,admin'])->group(function () {
    Route::apiResource('dossiers', DossierController::class);

    Route::get('admins', [DossierController::class, 'getAdmins']);
    Route::get('dossiers/adminchange', [DossierController::class, 'adminChange']);
    Route::post('dossiers/adminchange', [DossierController::class, 'adminChange']);



    Route::get('dossiers/{dossier}/files/{type}', [SecureFileController::class, 'show'])
        ->where('type', 'pv|note|carte_grise|declaration_recto|declaration_verso');
    Route::get('dossiers/{dossier}/accident-photos/{index}', [SecureFileController::class, 'showAccidentPhoto']);
});


Route::post('/send-credentials', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    $toEmail = $request->input('email');
    $plainPassword = $request->input('password');

    try {
        Mail::raw("Here are your credentials:\n\nEmail: $toEmail\nPassword: $plainPassword", function ($message) use ($toEmail) {
            $message->to($toEmail)
                    ->subject('Your Credentials');
        });

        return response()->json(['message' => 'Email sent successfully.']);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to send email.', 'details' => $e->getMessage()], 500);
    }
});