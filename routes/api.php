<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Resources\AnimalController;
use App\Http\Controllers\Api\Resources\DeviceController;
use App\Http\Controllers\Api\Users\UserController;
use App\Http\Controllers\Api\Location\MapController;
use App\Http\Controllers\Api\Location\LocationHistoryController;
use App\Http\Controllers\Api\Location\GeofenceController;
use App\Http\Controllers\Api\Business\AuctionController;
use App\Http\Controllers\Api\Resources\AnimalGroupController;
use App\Http\Controllers\Api\Business\SubscriptionController;
use App\Http\Controllers\Api\Tasks\TaskController;
use App\Http\Controllers\Api\Tasks\TaskLogController;
use App\Http\Controllers\Api\Admin\ReportsController;
use App\Http\Controllers\Api\Tasks\PredefinedTaskController;
use App\Http\Controllers\Api\Health\MedicalRecordController;
use App\Http\Controllers\Api\Admin\AdminSettingsController;
use App\Http\Controllers\Api\Health\VaccinationScheduleController;
use App\Http\Controllers\Api\Admin\ExportController;
use App\Http\Controllers\Api\Ai\AIController;
use App\Http\Controllers\Api\Admin\LanguageController;
use App\Http\Controllers\Api\Users\RoleManagementController;
use App\Http\Controllers\Api\PublicSettingsController;
use App\Http\Controllers\Api\Resources\SpeciesController;
use App\Http\Controllers\Api\Health\HealthCheckController;
use App\Http\Controllers\Api\Webhook\StripeWebhookController;
use App\Http\Controllers\DashboardController;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login'])->name('login')->middleware('throttle:30,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:30,1');
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:30,1');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:30,1');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:30,1');

Route::get('/subscription/tiers', [SubscriptionController::class, 'tiers']);
Route::get('/subscription/tiers/{tier}', [SubscriptionController::class, 'showTier']);

Route::get('/ai/status', [AIController::class, 'status']);
Route::post('/ai/chat', [AIController::class, 'chat']);

Route::get('/species', [SpeciesController::class, 'index']);
Route::get('/languages', [LanguageController::class, 'index']);
Route::get('/translations', [LanguageController::class, 'translations']);
Route::get('/translations-all', [LanguageController::class, 'translations']);
Route::get('/translations/{group}', [LanguageController::class, 'getTranslationsByGroup']);
Route::get('/languages/{code}', [LanguageController::class, 'show']);

Route::get('/health', [HealthCheckController::class, 'index']);
Route::get('/health/database', [HealthCheckController::class, 'database']);
Route::get('/health/redis', [HealthCheckController::class, 'redis']);

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::get('/auth/features', [AuthController::class, 'features']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('throttle:60,1');

    // Roles (read-only, available to all authenticated users for dropdowns)
    Route::get('/admin/roles', [RoleManagementController::class, 'index'])->middleware('throttle:60,1');

    // Reports
    Route::get('/reports', [ReportsController::class, 'index'])->middleware('throttle:60,1');

    // Animals
    Route::apiResource('animals', AnimalController::class)->middleware(['limits:animals', 'throttle:60,1']);
    Route::get('/animals/{id}/location-history', [LocationHistoryController::class, 'index'])->middleware('throttle:60,1');
    Route::post('/animals/{animal}/transfer-ownership', [AnimalController::class, 'transferOwnership'])->middleware('throttle:60,1');

    // Devices
    Route::apiResource('devices', DeviceController::class)->middleware(['limits:devices', 'throttle:60,1']);

    // Users - explicit routes instead of apiResource for debugging
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store'])->middleware(['limits:users', 'throttle:60,1']);
    Route::get('/users/{user}', [UserController::class, 'show'])->middleware(['limits:users', 'throttle:60,1']);
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware(['limits:users', 'throttle:60,1']);
    Route::patch('/users/{user}', [UserController::class, 'update'])->middleware(['limits:users', 'throttle:60,1']);
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware(['limits:users', 'throttle:60,1']);
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('throttle:60,1');
    Route::get('/users/doctors/list', [UserController::class, 'doctors'])->middleware('throttle:60,1');

    // Geofences
    Route::middleware(['limits:geofences', 'throttle:60,1'])->group(function () {
        Route::apiResource('geofences', GeofenceController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::get('/geofences/{geofence}/animals', [GeofenceController::class, 'geofenceAnimals']);
        Route::post('/geofences/{geofence}/animals', [GeofenceController::class, 'assignAnimals']);
        Route::delete('/geofences/{geofence}/animals', [GeofenceController::class, 'removeAnimals']);
        Route::get('/geofences/{geofence}/available-animals', [GeofenceController::class, 'availableAnimals']);
        Route::get('/geofences/{geofence}/groups', [GeofenceController::class, 'geofenceGroups']);
        Route::post('/geofences/{geofence}/groups', [GeofenceController::class, 'assignGroups']);
        Route::delete('/geofences/{geofence}/groups', [GeofenceController::class, 'removeGroups']);
        Route::get('/geofences/{geofence}/available-groups', [GeofenceController::class, 'availableGroups']);
    });

    // Geofence Alerts
    Route::get('/geofence-alerts', [GeofenceController::class, 'alerts'])->middleware('throttle:60,1');
    Route::patch('/geofence-alerts/{alert}/acknowledge', [GeofenceController::class, 'acknowledgeAlert'])->middleware('throttle:60,1');
    Route::delete('/geofence-alerts/{alert}', [GeofenceController::class, 'deleteAlert'])->middleware('throttle:60,1');
    Route::post('/geofence-alerts/deactivate-all', [GeofenceController::class, 'deactivateAlerts'])->middleware('throttle:60,1');
    Route::post('/geofence-alerts/{alert}/send-notification', [GeofenceController::class, 'sendNotification'])->middleware('throttle:60,1');
    Route::post('/geofence-alerts/send-bulk-notifications', [GeofenceController::class, 'sendBulkNotifications'])->middleware('throttle:60,1');

    // Animal Groups
    Route::get('/animal-groups', [AnimalGroupController::class, 'index'])->middleware('throttle:60,1');
    Route::post('/animal-groups', [AnimalGroupController::class, 'store'])->middleware('throttle:60,1');
    Route::get('/animal-groups/{animalGroup}', [AnimalGroupController::class, 'show'])->middleware('throttle:60,1');
    Route::put('/animal-groups/{animalGroup}', [AnimalGroupController::class, 'update'])->middleware('throttle:60,1');
    Route::delete('/animal-groups/{animalGroup}', [AnimalGroupController::class, 'destroy'])->middleware('throttle:60,1');
    Route::post('/animal-groups/{animalGroup}/add-animals', [AnimalGroupController::class, 'addAnimals'])->middleware('throttle:60,1');
    Route::post('/animal-groups/{animalGroup}/remove-animals', [AnimalGroupController::class, 'removeAnimals'])->middleware('throttle:60,1');
    Route::get('/animal-groups/{animalGroup}/available-animals', [AnimalGroupController::class, 'availableAnimals'])->middleware('throttle:60,1');

    // Map and Location
    Route::get('/map', [MapController::class, 'index'])->middleware('throttle:60,1');
    Route::post('/location-history', [LocationHistoryController::class, 'store'])->middleware('throttle:60,1');

    // Auctions
    Route::middleware('limits:auctions')->group(function () {
        Route::get('/auctions', [AuctionController::class, 'index']);
        Route::get('/auctions/my', [AuctionController::class, 'myAuctions']);
        Route::get('/auctions/my-bids', [AuctionController::class, 'myBids']);
        Route::get('/auctions/won', [AuctionController::class, 'wonAuctions']);
        Route::post('/auctions/{auction}/stripe-payment', [AuctionController::class, 'processStripePayment']);
        Route::post('/auctions', [AuctionController::class, 'store']);
        Route::get('/auctions/{auction}', [AuctionController::class, 'show']);
        Route::put('/auctions/{auction}', [AuctionController::class, 'update']);
        Route::delete('/auctions/{auction}', [AuctionController::class, 'destroy']);
        Route::post('/auctions/{auction}/bid', [AuctionController::class, 'placeBid']);
        Route::post('/auctions/{auction}/cancel', [AuctionController::class, 'cancel']);
        Route::post('/auctions/{auction}/end', [AuctionController::class, 'endAuction']);
        Route::delete('/auctions/{auction}/bids/{bid}/disqualify', [AuctionController::class, 'disqualifyBidder']);
        Route::post('/auctions/{auction}/payment-proof', [AuctionController::class, 'uploadPaymentProof']);
        Route::post('/auctions/{auction}/verify-payment/{status}', [AuctionController::class, 'verifyPayment']);
        Route::post('/auctions/process-expired-payments', [AuctionController::class, 'processExpiredPayments']);
    });

    // Medical Records
    Route::get('/medical-records', [MedicalRecordController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/medical-records/stats', [MedicalRecordController::class, 'stats'])->middleware('throttle:60,1');
    Route::post('/medical-records', [MedicalRecordController::class, 'store'])->middleware('throttle:60,1');
    Route::get('/medical-records/{medicalRecord}', [MedicalRecordController::class, 'show'])->middleware('throttle:60,1');
    Route::put('/medical-records/{medicalRecord}', [MedicalRecordController::class, 'update'])->middleware('throttle:60,1');
    Route::delete('/medical-records/{medicalRecord}', [MedicalRecordController::class, 'destroy'])->middleware('throttle:60,1');

    // Vaccination Schedules
    Route::get('/vaccination-schedules', [VaccinationScheduleController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/vaccination-schedules/stats', [VaccinationScheduleController::class, 'stats'])->middleware('throttle:60,1');
    Route::post('/vaccination-schedules', [VaccinationScheduleController::class, 'store'])->middleware('throttle:60,1');
    Route::get('/vaccination-schedules/{vaccinationSchedule}', [VaccinationScheduleController::class, 'show'])->middleware('throttle:60,1');
    Route::put('/vaccination-schedules/{vaccinationSchedule}', [VaccinationScheduleController::class, 'update'])->middleware('throttle:60,1');
    Route::post('/vaccination-schedules/{vaccinationSchedule}/administer', [VaccinationScheduleController::class, 'administer'])->middleware('throttle:60,1');
    Route::post('/vaccination-schedules/{vaccinationSchedule}/cancel', [VaccinationScheduleController::class, 'cancel'])->middleware('throttle:60,1');
    Route::delete('/vaccination-schedules/{vaccinationSchedule}', [VaccinationScheduleController::class, 'destroy'])->middleware('throttle:60,1');

    // Subscription
    Route::get('/subscription/current', [SubscriptionController::class, 'userSubscription'])->middleware('throttle:60,1');
    Route::post('/subscription/subscribe/{tier}', [SubscriptionController::class, 'subscribe'])->middleware('throttle:60,1');
    Route::post('/subscription/upgrade/{tier}', [SubscriptionController::class, 'upgrade'])->middleware('throttle:60,1');
    Route::post('/subscription/downgrade/{tier}', [SubscriptionController::class, 'downgrade'])->middleware('throttle:60,1');
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])->middleware('throttle:60,1');
    Route::post('/subscription/process-payment', [SubscriptionController::class, 'processPayment'])->middleware('throttle:60,1');
    Route::post('/subscription/bank-transfer', [SubscriptionController::class, 'bankTransfer'])->middleware('throttle:60,1');

    // Tasks
    Route::get('/tasks', [TaskController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/tasks/my', [TaskController::class, 'myTasks'])->middleware('throttle:60,1');
    Route::get('/tasks/stats', [TaskController::class, 'stats'])->middleware('throttle:60,1');
    Route::post('/tasks', [TaskController::class, 'store'])->middleware('throttle:60,1');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->middleware('throttle:60,1');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->middleware('throttle:60,1');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->middleware('throttle:60,1');
    Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->middleware('throttle:60,1');
    Route::get('/tasks/{task}/logs', [TaskLogController::class, 'logsForTask'])->middleware('throttle:60,1');

    // Task Logs
    Route::get('/task-logs', [TaskLogController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/task-logs/archive', [TaskLogController::class, 'archive'])->middleware('throttle:60,1');
    Route::get('/task-logs/my', [TaskLogController::class, 'myLogs'])->middleware('throttle:60,1');
    Route::post('/task-logs', [TaskLogController::class, 'store'])->middleware('throttle:60,1');
    Route::get('/task-logs/{taskLog}', [TaskLogController::class, 'show'])->middleware('throttle:60,1');
    Route::put('/task-logs/{taskLog}', [TaskLogController::class, 'update'])->middleware('throttle:60,1');
    Route::delete('/task-logs/{taskLog}', [TaskLogController::class, 'destroy'])->middleware('throttle:60,1');

    // Predefined Tasks
    Route::get('/predefined-tasks', [PredefinedTaskController::class, 'index'])->middleware('throttle:60,1');
    Route::post('/predefined-tasks', [PredefinedTaskController::class, 'store'])->middleware('throttle:60,1');
    Route::get('/predefined-tasks/{predefinedTask}', [PredefinedTaskController::class, 'show'])->middleware('throttle:60,1');
    Route::put('/predefined-tasks/{predefinedTask}', [PredefinedTaskController::class, 'update'])->middleware('throttle:60,1');
    Route::delete('/predefined-tasks/{predefinedTask}', [PredefinedTaskController::class, 'destroy'])->middleware('throttle:60,1');

    // Species (auth required for modifications)
    Route::post('/species', [SpeciesController::class, 'store'])->middleware('throttle:60,1');
    Route::put('/species/{species}', [SpeciesController::class, 'update'])->middleware('throttle:60,1');
    Route::delete('/species/{species}', [SpeciesController::class, 'destroy'])->middleware('throttle:60,1');
    Route::post('/species/{species}/breeds', [SpeciesController::class, 'storeBreed'])->middleware('throttle:60,1');
    Route::put('/breeds/{breed}', [SpeciesController::class, 'updateBreed'])->middleware('throttle:60,1');
    Route::delete('/breeds/{breed}', [SpeciesController::class, 'destroyBreed'])->middleware('throttle:60,1');

    // Language Management
    Route::get('/admin/languages', [LanguageController::class, 'allLanguages'])->middleware('throttle:60,1');
    Route::post('/admin/languages', [LanguageController::class, 'storeLanguage'])->middleware('throttle:60,1');
    Route::put('/admin/languages/{code}', [LanguageController::class, 'updateLanguage'])->middleware('throttle:60,1');
    Route::delete('/admin/languages/{code}', [LanguageController::class, 'deleteLanguage'])->middleware('throttle:60,1');
    Route::post('/admin/languages/{code}/set-default', [LanguageController::class, 'setDefaultLanguage'])->middleware('throttle:60,1');

    Route::post('/admin/translations', [LanguageController::class, 'storeTranslation'])->middleware('throttle:60,1');
    Route::put('/admin/translations/{id}', [LanguageController::class, 'updateTranslation'])->middleware('throttle:60,1');
    Route::delete('/admin/translations/{id}', [LanguageController::class, 'deleteTranslation'])->middleware('throttle:60,1');
    Route::post('/admin/translations/import', [LanguageController::class, 'importTranslations'])->middleware('throttle:60,1');
    Route::get('/search', [App\Http\Controllers\Api\SearchController::class, 'search'])->middleware('throttle:60,1');
});

// Admin-only routes
Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    // Subscription Admin
    Route::post('/subscription/admin/set-tier/{user}/{tier}', [SubscriptionController::class, 'adminSetTier'])->middleware('throttle:60,1');
    Route::get('/subscription/admin/subscriptions', [SubscriptionController::class, 'adminListSubscriptions'])->middleware('throttle:60,1');
    Route::get('/subscription/admin/stats', [SubscriptionController::class, 'adminSubscriptionStats'])->middleware('throttle:60,1');
    Route::get('/subscription/admin/pending-payments', [SubscriptionController::class, 'adminListPendingPayments'])->middleware('throttle:60,1');
    Route::post('/subscription/admin/approve-payment/{subscription}', [SubscriptionController::class, 'adminApprovePayment'])->middleware('throttle:60,1');
    Route::post('/subscription/admin/reject-payment/{subscription}', [SubscriptionController::class, 'adminRejectPayment'])->middleware('throttle:60,1');
    Route::post('/subscription/admin/tiers', [SubscriptionController::class, 'createTier'])->middleware('throttle:60,1');
    Route::put('/subscription/admin/tiers/{tier}', [SubscriptionController::class, 'updateTier'])->middleware('throttle:60,1');
    Route::delete('/subscription/admin/tiers/{tier}', [SubscriptionController::class, 'deleteTier'])->middleware('throttle:60,1');

    // Admin Settings
    Route::get('/admin/settings/general', [AdminSettingsController::class, 'getGeneralSettings'])->middleware('throttle:60,1');
    Route::post('/admin/settings/general', [AdminSettingsController::class, 'saveGeneralSettings'])->middleware('throttle:60,1');
    Route::get('/admin/settings/smtp', [AdminSettingsController::class, 'getSmtpSettings'])->middleware('throttle:60,1');
    Route::post('/admin/settings/smtp', [AdminSettingsController::class, 'saveSmtpSettings'])->middleware('throttle:60,1');
    Route::post('/admin/settings/smtp/test', [AdminSettingsController::class, 'testSmtpConnection'])->middleware('throttle:60,1');
    Route::get('/admin/settings/stripe', [AdminSettingsController::class, 'getStripeSettings'])->middleware('throttle:60,1');
    Route::post('/admin/settings/stripe', [AdminSettingsController::class, 'saveStripeSettings'])->middleware('throttle:60,1');
    Route::get('/admin/settings/gemini', [AdminSettingsController::class, 'getGeminiSettings'])->middleware('throttle:60,1');
    Route::post('/admin/settings/gemini', [AdminSettingsController::class, 'saveGeminiSettings'])->middleware('throttle:60,1');
    Route::get('/admin/settings/whatsapp', [AdminSettingsController::class, 'getWhatsAppSettings'])->middleware('throttle:60,1');
    Route::post('/admin/settings/whatsapp', [AdminSettingsController::class, 'saveWhatsAppSettings'])->middleware('throttle:60,1');
    Route::get('/admin/settings/twilio', [AdminSettingsController::class, 'getTwilioSettings'])->middleware('throttle:60,1');
    Route::post('/admin/settings/twilio', [AdminSettingsController::class, 'saveTwilioSettings'])->middleware('throttle:60,1');
    Route::get('/admin/settings/notifications', [AdminSettingsController::class, 'getNotificationSettings'])->middleware('throttle:60,1');
    Route::post('/admin/settings/notifications', [AdminSettingsController::class, 'saveNotificationSettings'])->middleware('throttle:60,1');

    // Export
    Route::get('/export/animals', [ExportController::class, 'exportAnimals'])->middleware('throttle:60,1');
    Route::get('/export/devices', [ExportController::class, 'exportDevices'])->middleware('throttle:60,1');
    Route::get('/export/geofences', [ExportController::class, 'exportGeofences'])->middleware('throttle:60,1');
    Route::get('/export/users', [ExportController::class, 'exportUsers'])->middleware('throttle:60,1');
    Route::get('/export/database', [ExportController::class, 'exportDatabase'])->middleware('throttle:60,1');

    // Role Management
    Route::post('/admin/roles', [RoleManagementController::class, 'storeRole'])->middleware('throttle:60,1');
    Route::put('/admin/roles/{role}', [RoleManagementController::class, 'updateRole'])->middleware('throttle:60,1');
    Route::delete('/admin/roles/{role}', [RoleManagementController::class, 'deleteRole'])->middleware('throttle:60,1');
    Route::get('/admin/users/{user}/roles', [RoleManagementController::class, 'getUserRoles'])->middleware('throttle:60,1');
    Route::put('/admin/users/{user}/roles', [RoleManagementController::class, 'updateUserRoles'])->middleware('throttle:60,1');
});

// Public settings (no auth required — used by login page, favicon, etc.)
Route::get('/settings/public', [PublicSettingsController::class, 'index']);
