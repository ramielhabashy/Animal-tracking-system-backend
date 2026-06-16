<?php

use App\Http\Controllers\Api\Admin\AdminSettingsController;
use App\Http\Controllers\Api\Admin\ExportController;
use App\Http\Controllers\Api\Admin\LanguageController;
use App\Http\Controllers\Api\Admin\MedicalRecordTypeController;
use App\Http\Controllers\Api\Admin\ReportsController;
use App\Http\Controllers\Api\Admin\SimulatorController;
use App\Http\Controllers\Api\Admin\TaskTypeController;
use App\Http\Controllers\Api\Ai\AIController;
use App\Http\Controllers\Api\Ai\AiQuickActionController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Business\AuctionController;
use App\Http\Controllers\Api\Business\SubscriptionController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\WorkflowTestController;
use App\Http\Controllers\Api\Communication\ConversationController;
use App\Http\Controllers\Api\Communication\MessageController;
use App\Http\Controllers\Api\Admin\AiTranslationController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\OwnershipTransferController;
use App\Http\Controllers\Api\Admin\MenuItemController;
use App\Http\Controllers\Api\Health\HealthCheckController;
use App\Http\Controllers\Api\Health\MedicalRecordController;
use App\Http\Controllers\Api\Health\VaccinationScheduleController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\Location\GeofenceController;
use App\Http\Controllers\Api\Location\LocationHistoryController;
use App\Http\Controllers\Api\Location\MapController;
use App\Http\Controllers\Api\Notifications\NotificationController;
use App\Http\Controllers\Api\PublicSettingsController;
use App\Http\Controllers\Api\Resources\AnimalController;
use App\Http\Controllers\Api\Resources\AnimalGroupController;
use App\Http\Controllers\Api\Resources\DeviceController;
use App\Http\Controllers\Api\Resources\SpeciesController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\Tasks\PredefinedTaskController;
use App\Http\Controllers\Api\Tasks\TaskController;
use App\Http\Controllers\Api\Tasks\TaskLogController;
use App\Http\Controllers\Api\TranslationController;
use App\Http\Controllers\Api\Users\RoleManagementController;
use App\Http\Controllers\Api\Users\UserController;
use App\Http\Controllers\Api\Webhook\StripeWebhookController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Embed public routes
Route::get('/embed/auctions', [\App\Http\Controllers\Api\EmbedController::class, 'auctions']);
Route::get('/embed/animals', [\App\Http\Controllers\Api\EmbedController::class, 'animals']);

// Invitation routes (public)
Route::get('/invitations/{token}', [InvitationController::class, 'show']);
Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept']);

// Public routes
Route::post('/auth/login', [AuthController::class, 'login'])->name('login')->middleware('throttle:30,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:30,1');
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:30,1');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:30,1');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:30,1');
Route::get('/subscription/tiers', [SubscriptionController::class, 'tiers']);
Route::get('/subscription/tiers/{tier}', [SubscriptionController::class, 'showTier']);

Route::get('/ai/status', [AIController::class, 'status'])->middleware('auth:sanctum');
Route::get('/ai/quick-actions', [AIController::class, 'quickActions'])->middleware('auth:sanctum');

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

// Public settings (no auth — used by guest checkout)
Route::get('/settings/countries', function () {
    $countries = DB::table('settings')->where('key', 'checkout_countries')->value('value');
    return response()->json(['data' => $countries ? json_decode($countries) : ['Saudi Arabia']]);
})->middleware('throttle:60,1');

Route::get('/settings/stripe-status', function () {
    $enabled = \App\Models\Setting::getBoolean('stripe_enabled', false);
    return response()->json(['data' => ['enabled' => $enabled]]);
})->middleware('throttle:60,1');

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
    Route::get('/dashboard/owners', [DashboardController::class, 'ownerStats'])->middleware('throttle:60,1');

    // Roles (read-only, available to all authenticated users for dropdowns)
    Route::get('/admin/roles', [RoleManagementController::class, 'index'])->middleware('throttle:60,1');

    // Reports
    Route::get('/reports', [ReportsController::class, 'index'])->middleware(['feature:advanced_reports', 'throttle:60,1']);
    Route::get('/reports/export', [ReportsController::class, 'export'])->middleware(['feature:advanced_reports', 'throttle:60,1']);

    // Animals
    Route::get('/animals/stats', [AnimalController::class, 'stats'])->middleware(['limits:animals', 'throttle:60,1']);
    Route::apiResource('animals', AnimalController::class)->middleware(['limits:animals', 'throttle:60,1']);
    Route::get('/animals/{id}/location-history', [LocationHistoryController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/animals/{id}/gps', [AnimalController::class, 'gps'])->middleware('throttle:60,1');
    // Ownership Transfers
    Route::get('/transfers', [OwnershipTransferController::class, 'index']);
    Route::post('/transfers', [OwnershipTransferController::class, 'store']);
    Route::get('/transfers/history', [OwnershipTransferController::class, 'history']);
    Route::get('/transfers/stats', [OwnershipTransferController::class, 'stats']);
    Route::get('/transfers/{transfer}', [OwnershipTransferController::class, 'show']);
    Route::post('/transfers/{transfer}/accept', [OwnershipTransferController::class, 'accept']);
    Route::post('/transfers/{transfer}/reject', [OwnershipTransferController::class, 'reject']);
    Route::post('/transfers/{transfer}/cancel', [OwnershipTransferController::class, 'cancel']);
    Route::post('/animals/{animal}/transfer-ownership', [OwnershipTransferController::class, 'legacyTransfer'])->middleware('throttle:60,1');

    // Devices
    Route::apiResource('devices', DeviceController::class)->middleware(['limits:devices', 'throttle:60,1']);
    Route::post('/devices/provision', [DeviceController::class, 'provision'])->middleware(['limits:devices', 'throttle:60,1']);
    Route::post('/devices/batch', [DeviceController::class, 'batchStore'])->middleware(['limits:devices', 'throttle:60,1']);

    // Invitations
    Route::get('/invitations', [InvitationController::class, 'index']);
    Route::post('/invitations', [InvitationController::class, 'store']);
    Route::post('/invitations/{id}/resend', [InvitationController::class, 'resend']);
    Route::delete('/invitations/{id}', [InvitationController::class, 'cancel']);

    // Users - explicit routes instead of apiResource for debugging
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store'])->middleware(['limits:users', 'throttle:60,1']);
    Route::get('/users/{user}', [UserController::class, 'show'])->middleware('throttle:60,1');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('throttle:60,1');
    Route::patch('/users/{user}', [UserController::class, 'update'])->middleware('throttle:60,1');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('throttle:60,1');
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('throttle:60,1');
    Route::get('/users/doctors/list', [UserController::class, 'doctors'])->middleware('throttle:60,1');
    Route::get('/users/owners/list', [UserController::class, 'owners'])->middleware('throttle:60,1');

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
    Route::get('/alerts/temperature', [GeofenceController::class, 'temperatureAlerts'])->middleware('throttle:60,1');
    Route::get('/temperature-alerts', [GeofenceController::class, 'temperatureAlerts'])->middleware('throttle:60,1');
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
    Route::get('/animal-groups/{animalGroup}/shepherds', [AnimalGroupController::class, 'getShepherds'])->middleware('throttle:60,1');
    Route::post('/animal-groups/{animalGroup}/shepherds', [AnimalGroupController::class, 'assignShepherds'])->middleware('throttle:60,1');
    Route::delete('/animal-groups/{animalGroup}/shepherds/{shepherd}', [AnimalGroupController::class, 'removeShepherd'])->middleware('throttle:60,1');

    // Map and Location
    Route::get('/map', [MapController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/map/filters', [MapController::class, 'filters'])->middleware('throttle:60,1');
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
        Route::get('/auctions/stats', [AuctionController::class, 'stats'])->middleware('throttle:60,1');
        Route::get('/auctions/{auction}/bids', [AuctionController::class, 'bids'])->middleware('throttle:60,1');
        Route::post('/auctions/{auction}/bid', [AuctionController::class, 'placeBid']);
        Route::post('/auctions/{auction}/cancel', [AuctionController::class, 'cancel']);
        Route::post('/auctions/{auction}/end', [AuctionController::class, 'endAuction']);
        Route::delete('/auctions/{auction}/bids/{bid}/disqualify', [AuctionController::class, 'disqualifyBidder']);
        Route::post('/auctions/{auction}/payment-proof', [AuctionController::class, 'uploadPaymentProof']);
        Route::post('/auctions/{auction}/verify-payment/{status}', [AuctionController::class, 'verifyPayment']);
        Route::post('/auctions/process-expired-payments', [AuctionController::class, 'processExpiredPayments']);
    });

    // Medical Records
    Route::middleware('feature:medical_records')->group(function () {
        Route::get('/medical-records', [MedicalRecordController::class, 'index'])->middleware('throttle:60,1');
        Route::get('/medical-records/stats', [MedicalRecordController::class, 'stats'])->middleware('throttle:60,1');
        Route::post('/medical-records', [MedicalRecordController::class, 'store'])->middleware('throttle:60,1');
        Route::get('/medical-records/{medicalRecord}', [MedicalRecordController::class, 'show'])->middleware('throttle:60,1');
        Route::put('/medical-records/{medicalRecord}', [MedicalRecordController::class, 'update'])->middleware('throttle:60,1');
        Route::delete('/medical-records/{medicalRecord}', [MedicalRecordController::class, 'destroy'])->middleware('throttle:60,1');
    });

    // Vaccination Schedules
    Route::middleware('feature:medical_records')->group(function () {
        Route::get('/vaccination-schedules', [VaccinationScheduleController::class, 'index'])->middleware('throttle:60,1');
        Route::get('/vaccination-schedules/stats', [VaccinationScheduleController::class, 'stats'])->middleware('throttle:60,1');
        Route::post('/vaccination-schedules', [VaccinationScheduleController::class, 'store'])->middleware('throttle:60,1');
        Route::get('/vaccination-schedules/{vaccinationSchedule}', [VaccinationScheduleController::class, 'show'])->middleware('throttle:60,1');
        Route::put('/vaccination-schedules/{vaccinationSchedule}', [VaccinationScheduleController::class, 'update'])->middleware('throttle:60,1');
        Route::post('/vaccination-schedules/{vaccinationSchedule}/administer', [VaccinationScheduleController::class, 'administer'])->middleware('throttle:60,1');
        Route::post('/vaccination-schedules/{vaccinationSchedule}/cancel', [VaccinationScheduleController::class, 'cancel'])->middleware('throttle:60,1');
        Route::delete('/vaccination-schedules/{vaccinationSchedule}', [VaccinationScheduleController::class, 'destroy'])->middleware('throttle:60,1');
        // Flutter compatibility aliases
        Route::get('/vaccinations', [VaccinationScheduleController::class, 'index'])->middleware('throttle:60,1');
        Route::get('/vaccinations/stats', [VaccinationScheduleController::class, 'stats'])->middleware('throttle:60,1');
        Route::post('/vaccinations', [VaccinationScheduleController::class, 'store'])->middleware('throttle:60,1');
        Route::get('/vaccinations/{id}', [VaccinationScheduleController::class, 'show'])->middleware('throttle:60,1');
        Route::put('/vaccinations/{id}', [VaccinationScheduleController::class, 'update'])->middleware('throttle:60,1');
        Route::post('/vaccinations/{id}/administer', [VaccinationScheduleController::class, 'administer'])->middleware('throttle:60,1');
        Route::post('/vaccinations/{id}/cancel', [VaccinationScheduleController::class, 'cancel'])->middleware('throttle:60,1');
        Route::delete('/vaccinations/{id}', [VaccinationScheduleController::class, 'destroy'])->middleware('throttle:60,1');
    });

    // Payment methods (dynamic list for frontend)
    Route::get('/payment-methods', function () {
        return response()->json(['data' => \App\Services\PaymentMethodService::getAvailableMethods()]);
    })->middleware('throttle:60,1');

    // Subscription
    Route::get('/subscription/current', [SubscriptionController::class, 'userSubscription'])->middleware('throttle:60,1');
    Route::get('/subscription/history', [SubscriptionController::class, 'userHistory'])->middleware('throttle:60,1');
    Route::post('/subscription/subscribe/{tier}', [SubscriptionController::class, 'subscribe'])->middleware('throttle:60,1');
    Route::post('/subscription/upgrade/{tier}', [SubscriptionController::class, 'upgrade'])->middleware('throttle:60,1');
    Route::post('/subscription/downgrade/{tier}', [SubscriptionController::class, 'downgrade'])->middleware('throttle:60,1');
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])->middleware('throttle:60,1');
    Route::post('/subscription/renew', [SubscriptionController::class, 'renew'])->middleware('throttle:60,1');
    Route::post('/subscription/reactivate', [SubscriptionController::class, 'reactivate'])->middleware('throttle:60,1');
    // DEPRECATED: raw card data path removed for PCI compliance.
    // All payments use Stripe Checkout sessions via CheckoutController or renew().
    // Route::post('/subscription/process-payment', [SubscriptionController::class, 'processPayment'])->middleware('throttle:60,1');
    Route::post('/subscription/bank-transfer', [SubscriptionController::class, 'bankTransfer'])->middleware('throttle:60,1');

    // Checkout
    Route::post('/checkout/init', [CheckoutController::class, 'init'])->middleware('throttle:60,1');
    Route::post('/checkout/confirm', [CheckoutController::class, 'confirm'])->middleware('throttle:60,1');
    Route::post('/checkout/bank-transfer', [CheckoutController::class, 'bankTransfer'])->middleware('throttle:60,1');
    Route::get('/checkout/orders', [CheckoutController::class, 'myOrders'])->middleware('throttle:60,1');
    Route::get('/checkout/orders/{order}', [CheckoutController::class, 'showOrder'])->middleware('throttle:60,1');
    Route::post('/checkout/activate-device', [CheckoutController::class, 'activateDevice'])->middleware('throttle:60,1');

    // Menu (authenticated)
    Route::get('/menu-items', [MenuController::class, 'index'])->middleware('throttle:60,1');

    // Tasks
    Route::middleware('feature:tasks')->group(function () {
        Route::get('/tasks', [TaskController::class, 'index'])->middleware('throttle:60,1');
        Route::get('/tasks/my', [TaskController::class, 'myTasks'])->middleware('throttle:60,1');
        Route::get('/tasks/stats', [TaskController::class, 'stats'])->middleware('throttle:60,1');
        Route::post('/tasks', [TaskController::class, 'store'])->middleware('throttle:60,1');
        Route::get('/tasks/{task}', [TaskController::class, 'show'])->middleware('throttle:60,1');
        Route::put('/tasks/{task}', [TaskController::class, 'update'])->middleware('throttle:60,1');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->middleware('throttle:60,1');
        Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->middleware('throttle:60,1');
        Route::post('/tasks/{task}/deliver', [TaskController::class, 'deliver'])->middleware('throttle:60,1');
        Route::post('/tasks/{task}/approve', [TaskController::class, 'approve'])->middleware('throttle:60,1');
        Route::post('/tasks/{task}/reject', [TaskController::class, 'reject'])->middleware('throttle:60,1');
        Route::post('/tasks/{task}/reassign', [TaskController::class, 'reassign'])->middleware('throttle:60,1');
        Route::get('/tasks/{task}/logs', [TaskLogController::class, 'logsForTask'])->middleware('throttle:60,1');
        Route::get('/tasks/calendar/data', [TaskController::class, 'calendar'])->middleware('throttle:60,1');
        Route::get('/tasks/types/list', [TaskController::class, 'taskTypes'])->middleware('throttle:60,1');

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
    });

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
    Route::post('/admin/translations/ai-fill', [AiTranslationController::class, 'fillUi'])->middleware('throttle:30,1');
    Route::post('/admin/translations/ai-fill-models', [AiTranslationController::class, 'fillModels'])->middleware('throttle:30,1');
    Route::get('/search', [SearchController::class, 'search'])->middleware('throttle:60,1');

    // Task Types (public list for all auth users)
    Route::get('/task-types', [TaskTypeController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/task-log-types', [TaskLogController::class, 'logTypes'])->middleware('throttle:60,1');

    // Medical Record Types (public list for all auth users)
    Route::get('/medical-record-types', [MedicalRecordTypeController::class, 'index'])->middleware('throttle:60,1');

    // Vaccination Types (public list for all auth users)
    Route::get('/vaccination-types', [MedicalRecordTypeController::class, 'vaccinationTypes'])->middleware('throttle:60,1');

    // Notifications (accessible by all authenticated users)
    Route::get('/notifications', [NotificationController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->middleware('throttle:60,1');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->middleware('throttle:60,1');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->middleware('throttle:60,1');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->middleware('throttle:60,1');

    // Conversations & Messages
    Route::get('/conversations/unread-count', [ConversationController::class, 'unreadCount'])->middleware('throttle:60,1');
    Route::get('/conversations', [ConversationController::class, 'index'])->middleware('throttle:60,1');
    Route::post('/conversations', [ConversationController::class, 'store'])->middleware('throttle:60,1');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->middleware('throttle:60,1');
    Route::put('/conversations/{conversation}', [ConversationController::class, 'update'])->middleware('throttle:60,1');
    Route::post('/conversations/{conversation}', [ConversationController::class, 'update'])->middleware('throttle:60,1');
    Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy'])->middleware('throttle:60,1');
    Route::post('/conversations/{conversation}/read', [ConversationController::class, 'markRead'])->middleware('throttle:60,1');

    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index'])->middleware('throttle:60,1');
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])->middleware('throttle:60,1');
    Route::put('/messages/{message}', [MessageController::class, 'update'])->middleware('throttle:60,1');
    Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->middleware('throttle:60,1');
    Route::post('/messages/{message}/attachments', [MessageController::class, 'uploadAttachment'])->middleware('throttle:60,1');
    Route::delete('/attachments/{attachment}', [MessageController::class, 'deleteAttachment'])->middleware('throttle:60,1');

    // AI Assistant
    Route::post('/ai/chat', [AIController::class, 'chat'])->middleware(['feature:ai_assistant', 'throttle:60,1']);
    Route::post('/ai/generate-report', [AIController::class, 'generateReport'])->middleware(['feature:ai_assistant', 'throttle:30,1']);
    Route::get('/ai/conversations', [AIController::class, 'listConversations'])->middleware('throttle:60,1');
    Route::get('/ai/conversations/{conversation}', [AIController::class, 'getConversation'])->middleware('throttle:60,1');
    Route::post('/ai/conversations', [AIController::class, 'saveConversation'])->middleware('throttle:60,1');
    Route::put('/ai/conversations/{conversation}', [AIController::class, 'updateConversation'])->middleware('throttle:60,1');
    Route::delete('/ai/conversations/{conversation}', [AIController::class, 'deleteConversation'])->middleware('throttle:60,1');

    // Translation
    Route::post('/translate', [TranslationController::class, 'translate'])->middleware('throttle:30,1');
    Route::post('/translate/batch', [TranslationController::class, 'translateBatch'])->middleware('throttle:10,1');
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
    // Flutter compatibility aliases
    Route::get('/payments/pending', [SubscriptionController::class, 'adminListPendingPayments'])->middleware('throttle:60,1');
    Route::post('/payments/{subscription}/approve', [SubscriptionController::class, 'adminApprovePayment'])->middleware('throttle:60,1');
    Route::post('/payments/{subscription}/reject', [SubscriptionController::class, 'adminRejectPayment'])->middleware('throttle:60,1');
    Route::post('/subscription/admin/tiers', [SubscriptionController::class, 'createTier'])->middleware('throttle:60,1');
    Route::put('/subscription/admin/tiers/{tier}', [SubscriptionController::class, 'updateTier'])->middleware('throttle:60,1');
    Route::delete('/subscription/admin/tiers/{tier}', [SubscriptionController::class, 'deleteTier'])->middleware('throttle:60,1');
    Route::post('/subscription/admin/pause/{user}', [SubscriptionController::class, 'adminPauseSubscription'])->middleware('throttle:60,1');
    Route::post('/subscription/admin/reactivate/{user}', [SubscriptionController::class, 'adminReactivateSubscription'])->middleware('throttle:60,1');
    Route::post('/subscription/admin/cancel/{user}', [SubscriptionController::class, 'adminCancelSubscription'])->middleware('throttle:60,1');
    Route::put('/subscription/admin/billing-cycle/{subscription}', [SubscriptionController::class, 'adminChangeBillingCycle'])->middleware('throttle:60,1');
    // Flutter compatibility aliases (different parameter ordering)
    Route::post('/subscription/admin/{userId}/pause', [SubscriptionController::class, 'adminPauseSubscription'])->middleware('throttle:60,1');
    Route::post('/subscription/admin/{userId}/reactivate', [SubscriptionController::class, 'adminReactivateSubscription'])->middleware('throttle:60,1');
    Route::post('/subscription/admin/{userId}/cancel', [SubscriptionController::class, 'adminCancelSubscription'])->middleware('throttle:60,1');
    Route::post('/subscription/admin/{userId}/billing-cycle', [SubscriptionController::class, 'adminChangeBillingCycle'])->middleware('throttle:60,1');
    Route::put('/subscription/admin/update/{subscription}', [SubscriptionController::class, 'adminUpdateSubscription'])->middleware('throttle:60,1');

    // Task Type Admin
    Route::post('/admin/task-types', [TaskTypeController::class, 'store'])->middleware('throttle:60,1');
    Route::put('/admin/task-types/{taskType}', [TaskTypeController::class, 'update'])->middleware('throttle:60,1');
    Route::delete('/admin/task-types/{taskType}', [TaskTypeController::class, 'destroy'])->middleware('throttle:60,1');
    Route::post('/admin/task-log-types', [TaskTypeController::class, 'storeLogType'])->middleware('throttle:60,1');
    Route::put('/admin/task-log-types/{taskLogType}', [TaskTypeController::class, 'updateLogType'])->middleware('throttle:60,1');
    Route::delete('/admin/task-log-types/{taskLogType}', [TaskTypeController::class, 'destroyLogType'])->middleware('throttle:60,1');

    // Medical Record Type Admin
    Route::post('/admin/medical-record-types', [MedicalRecordTypeController::class, 'store'])->middleware('throttle:60,1');
    Route::put('/admin/medical-record-types/{medicalRecordType}', [MedicalRecordTypeController::class, 'update'])->middleware('throttle:60,1');
    Route::delete('/admin/medical-record-types/{medicalRecordType}', [MedicalRecordTypeController::class, 'destroy'])->middleware('throttle:60,1');

    // Vaccination Type Admin
    Route::get('/admin/vaccination-types', [MedicalRecordTypeController::class, 'allVaccinationTypes'])->middleware('throttle:60,1');
    Route::post('/admin/vaccination-types', [MedicalRecordTypeController::class, 'storeVaccinationType'])->middleware('throttle:60,1');
    Route::put('/admin/vaccination-types/{vaccinationType}', [MedicalRecordTypeController::class, 'updateVaccinationType'])->middleware('throttle:60,1');
    Route::delete('/admin/vaccination-types/{vaccinationType}', [MedicalRecordTypeController::class, 'destroyVaccinationType'])->middleware('throttle:60,1');

    // Admin Settings
    Route::get('/admin/settings/general', [AdminSettingsController::class, 'getGeneralSettings'])->middleware('throttle:60,1');
    Route::post('/admin/settings/general', [AdminSettingsController::class, 'saveGeneralSettings'])->middleware('throttle:60,1');
    Route::get('/admin/settings/smtp', [AdminSettingsController::class, 'getSmtpSettings'])->middleware('throttle:60,1');
    Route::post('/admin/settings/smtp', [AdminSettingsController::class, 'saveSmtpSettings'])->middleware('throttle:60,1');
    Route::post('/admin/settings/smtp/test', [AdminSettingsController::class, 'testSmtpConnection'])->middleware('throttle:60,1');
    Route::get('/admin/settings/smtp/logs', [AdminSettingsController::class, 'getEmailLogs'])->middleware('throttle:60,1');
    Route::get('/admin/settings/stripe', [AdminSettingsController::class, 'getStripeSettings'])->middleware('throttle:60,1');
    Route::post('/admin/settings/stripe', [AdminSettingsController::class, 'saveStripeSettings'])->middleware('throttle:60,1');
    Route::get('/admin/settings/ai', [AdminSettingsController::class, 'getAiSettings'])->middleware('throttle:60,1');
    Route::post('/admin/settings/ai', [AdminSettingsController::class, 'saveAiSettings'])->middleware('throttle:60,1');
    Route::get('/admin/settings/gemini', [AdminSettingsController::class, 'getGeminiSettings'])->middleware('throttle:60,1');
    Route::post('/admin/settings/gemini', [AdminSettingsController::class, 'saveGeminiSettings'])->middleware('throttle:60,1');

    Route::post('/admin/ai/quick-actions/reorder', [AiQuickActionController::class, 'reorder'])->middleware('throttle:60,1');
    Route::apiResource('/admin/ai/quick-actions', AiQuickActionController::class)->parameters(['quick-actions' => 'ai_quick_action'])->middleware('throttle:60,1');
    Route::get('/admin/settings/notifications', [AdminSettingsController::class, 'getNotificationSettings'])->middleware('throttle:60,1');
    Route::post('/admin/settings/notifications', [AdminSettingsController::class, 'saveNotificationSettings'])->middleware('throttle:60,1');

    // Transfer Commission Settings
    Route::get('/admin/settings/transfer-commission', [AdminSettingsController::class, 'getTransferCommissionSettings'])->middleware('throttle:60,1');
    Route::post('/admin/settings/transfer-commission', [AdminSettingsController::class, 'saveTransferCommissionSettings'])->middleware('throttle:60,1');

    // Device Integration Settings
    Route::get('/admin/settings/device-integration', [AdminSettingsController::class, 'getDeviceSettings'])->middleware('throttle:60,1');
    Route::post('/admin/settings/device-integration', [AdminSettingsController::class, 'saveDeviceSettings'])->middleware('throttle:60,1');
    Route::post('/admin/settings/device-integration/test-mqtt', [AdminSettingsController::class, 'testMqttConnection'])->middleware('throttle:60,1');
    Route::post('/devices/poll-real', [\App\Http\Controllers\Api\Resources\DeviceController::class, 'pollRealData'])->middleware('throttle:60,1');

    // Translation Settings
    Route::get('/admin/settings/translation', [AdminSettingsController::class, 'getTranslationSettings'])->middleware('throttle:60,1');
    Route::post('/admin/settings/translation', [AdminSettingsController::class, 'saveTranslationSettings'])->middleware('throttle:60,1');

    // Email Notification Preferences
    Route::get('/admin/settings/email-preferences', [AdminSettingsController::class, 'getEmailNotificationPreferences'])->middleware('throttle:60,1');
    Route::post('/admin/settings/email-preferences', [AdminSettingsController::class, 'saveEmailNotificationPreferences'])->middleware('throttle:60,1');

    // Country Settings
    Route::get('/admin/settings/countries', [AdminSettingsController::class, 'getCountrySettings'])->middleware('throttle:60,1');
    Route::post('/admin/settings/countries', [AdminSettingsController::class, 'saveCountrySettings'])->middleware('throttle:60,1');

    // Checkout Admin
    Route::get('/checkout/admin/orders', [CheckoutController::class, 'adminOrders'])->middleware('throttle:60,1');
    Route::put('/checkout/admin/orders/{order}', [CheckoutController::class, 'adminUpdateOrder'])->middleware('throttle:60,1');
    Route::post('/checkout/admin/orders/{order}/approve-payment', [CheckoutController::class, 'adminApprovePayment'])->middleware('throttle:60,1');
    Route::post('/checkout/admin/orders/{order}/reject-payment', [CheckoutController::class, 'adminRejectPayment'])->middleware('throttle:60,1');
    Route::get('/checkout/admin/stats', [CheckoutController::class, 'adminStats'])->middleware('throttle:60,1');

    // Menu Admin
    Route::get('/admin/menu-items', [MenuItemController::class, 'index'])->middleware('throttle:60,1');
    Route::post('/admin/menu-items', [MenuItemController::class, 'store'])->middleware('throttle:60,1');
    Route::put('/admin/menu-items/{menuItem}', [MenuItemController::class, 'update'])->middleware('throttle:60,1');
    Route::delete('/admin/menu-items/{menuItem}', [MenuItemController::class, 'destroy'])->middleware('throttle:60,1');
    Route::post('/admin/menu-items/reorder', [MenuItemController::class, 'reorder'])->middleware('throttle:60,1');

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

    // Workflow Test
    Route::post('/system/workflow-test/run', [WorkflowTestController::class, 'run']);
    Route::get('/system/workflow-test/latest', [WorkflowTestController::class, 'latest']);
    Route::get('/system/workflow-test/runs', [WorkflowTestController::class, 'index']);

    // Banners Admin
    Route::get('/admin/banners', [\App\Http\Controllers\Api\BannerController::class, 'index']);
    Route::post('/admin/banners', [\App\Http\Controllers\Api\BannerController::class, 'store']);
    Route::put('/admin/banners/{banner}', [\App\Http\Controllers\Api\BannerController::class, 'update']);
    Route::delete('/admin/banners/{banner}', [\App\Http\Controllers\Api\BannerController::class, 'destroy']);

    // Transfer management
    Route::get('/admin/transfers', [OwnershipTransferController::class, 'adminIndex']);
    Route::put('/admin/transfers/{transfer}/commission', [OwnershipTransferController::class, 'adminUpdateCommission']);
    Route::get('/admin/transfers/commission-stats', [OwnershipTransferController::class, 'adminCommissionStats']);

    // Auction Admin
    Route::get('/admin/auctions/pending-approval', [AuctionController::class, 'adminPendingApproval']);
    Route::post('/admin/auctions/{auction}/approve', [AuctionController::class, 'adminApprove']);
    Route::post('/admin/auctions/{auction}/reject', [AuctionController::class, 'adminReject']);
    Route::get('/admin/auctions/payments', [AuctionController::class, 'adminPayments']);

    // Auction Settings
    Route::get('/admin/settings/auction', [AdminSettingsController::class, 'getAuctionSettings']);
    Route::post('/admin/settings/auction', [AdminSettingsController::class, 'saveAuctionSettings']);

    // Subscription Settings
    Route::get('/admin/settings/subscription', [AdminSettingsController::class, 'getSubscriptionSettings']);
    Route::post('/admin/settings/subscription', [AdminSettingsController::class, 'saveSubscriptionSettings']);
});

// Simulator (admin only, gated by device_simulator_enabled setting in controller)
Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    Route::get('/simulator/devices', [SimulatorController::class, 'devices'])->middleware('throttle:60,1');
    Route::post('/simulator/move', [SimulatorController::class, 'move'])->middleware('throttle:60,1');
    Route::post('/simulator/batch', [SimulatorController::class, 'batch'])->middleware('throttle:60,1');
    Route::post('/simulator/recharge', [SimulatorController::class, 'recharge'])->middleware('throttle:60,1');
    Route::post('/simulator/teleport', [SimulatorController::class, 'teleport'])->middleware('throttle:60,1');
    Route::post('/simulator/demo-seed', [SimulatorController::class, 'demoSeed'])->middleware('throttle:60,1');
    Route::post('/simulator/demo-reset', [SimulatorController::class, 'demoReset'])->middleware('throttle:60,1');
    Route::post('/simulator/update', [SimulatorController::class, 'update'])->middleware('throttle:60,1');
    Route::post('/simulator/toggle-lost', [SimulatorController::class, 'toggleLost'])->middleware('throttle:60,1');
    Route::post('/simulator/set-temperature', [SimulatorController::class, 'setTemperature'])->middleware('throttle:60,1');
    Route::post('/simulator/batch-settings', [SimulatorController::class, 'batchSettings'])->middleware('throttle:60,1');
});

// Public settings (no auth required — used by login page, favicon, etc.)
Route::get('/settings/public', [PublicSettingsController::class, 'index']);

// Public banners (no auth)
Route::get('/banners/active', [\App\Http\Controllers\Api\BannerController::class, 'active']);

// Public pages (no auth)
Route::get('/pages/{slug}', [PageController::class, 'show']);

// Admin page management
Route::middleware(['auth:sanctum', 'role:Admin'])->prefix('admin')->group(function () {
    Route::get('/pages', [PageController::class, 'adminIndex']);
    Route::post('/pages', [PageController::class, 'store']);
    Route::put('/pages/{page}', [PageController::class, 'update']);
    Route::delete('/pages/{page}', [PageController::class, 'destroy']);
});
