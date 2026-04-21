<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MapController;
use App\Http\Controllers\Api\LocationHistoryController;
use App\Http\Controllers\GeofenceController;
use App\Http\Controllers\AuctionController;
use App\Http\Controllers\AnimalGroupController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskLogController;
use App\Http\Controllers\Api\ReportsController;
use App\Http\Controllers\Api\PredefinedTaskController;
use App\Http\Controllers\Api\MedicalRecordController;
use App\Http\Controllers\Api\AdminSettingsController;
use App\Http\Controllers\Api\VaccinationScheduleController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\SpeciesController;
use App\Http\Controllers\Api\AIController;

Route::get('/fix-roles', function() {
    DB::statement("UPDATE users SET role = 'Veterinarian' WHERE role = 'Doctor'");
    DB::statement("UPDATE users SET role = 'Veterinarian' WHERE role = 'Manager'");
    DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('Admin', 'Owner', 'Veterinarian', 'Shepherd') DEFAULT 'Owner'");
    return response()->json(['message' => 'Fixed!']);
});

Route::post('/auth/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::get('/subscription/tiers', [SubscriptionController::class, 'tiers']);
Route::get('/subscription/tiers/{tier}', [SubscriptionController::class, 'showTier']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::get('/auth/features', [AuthController::class, 'features']);
});

Route::get('/ai/status', [AIController::class, 'status']);
Route::post('/ai/chat', [AIController::class, 'chat']);

Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/reports', [ReportsController::class, 'index']);

Route::apiResource('animals', AnimalController::class)->middleware('limits:animals');
Route::get('/animals/{id}/location-history', [LocationHistoryController::class, 'index']);

Route::apiResource('devices', DeviceController::class)->middleware('limits:devices');

Route::apiResource('users', UserController::class)->middleware('limits:users');
Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);

Route::middleware('limits:geofences')->group(function () {
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

Route::get('/geofence-alerts', [GeofenceController::class, 'alerts']);
Route::patch('/geofence-alerts/{alert}/acknowledge', [GeofenceController::class, 'acknowledgeAlert']);
Route::delete('/geofence-alerts/{alert}', [GeofenceController::class, 'deleteAlert']);
Route::post('/geofence-alerts/deactivate-all', [GeofenceController::class, 'deactivateAlerts']);
Route::post('/geofence-alerts/{alert}/send-notification', [GeofenceController::class, 'sendNotification']);
Route::post('/geofence-alerts/send-bulk-notifications', [GeofenceController::class, 'sendBulkNotifications']);

Route::get('/animal-groups', [AnimalGroupController::class, 'index']);
Route::post('/animal-groups', [AnimalGroupController::class, 'store']);
Route::get('/animal-groups/{animalGroup}', [AnimalGroupController::class, 'show']);
Route::put('/animal-groups/{animalGroup}', [AnimalGroupController::class, 'update']);
Route::delete('/animal-groups/{animalGroup}', [AnimalGroupController::class, 'destroy']);
Route::post('/animal-groups/{animalGroup}/add-animals', [AnimalGroupController::class, 'addAnimals']);
Route::post('/animal-groups/{animalGroup}/remove-animals', [AnimalGroupController::class, 'removeAnimals']);
Route::get('/animal-groups/{animalGroup}/available-animals', [AnimalGroupController::class, 'availableAnimals']);

Route::get('/map', [MapController::class, 'index']);
Route::post('/location-history', [LocationHistoryController::class, 'store']);

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

Route::get('/medical-records', [MedicalRecordController::class, 'index']);
Route::get('/medical-records/stats', [MedicalRecordController::class, 'stats']);
Route::post('/medical-records', [MedicalRecordController::class, 'store']);
Route::get('/medical-records/{medicalRecord}', [MedicalRecordController::class, 'show']);
Route::put('/medical-records/{medicalRecord}', [MedicalRecordController::class, 'update']);
Route::delete('/medical-records/{medicalRecord}', [MedicalRecordController::class, 'destroy']);

Route::get('/vaccination-schedules', [VaccinationScheduleController::class, 'index']);
Route::get('/vaccination-schedules/stats', [VaccinationScheduleController::class, 'stats']);
Route::post('/vaccination-schedules', [VaccinationScheduleController::class, 'store']);
Route::get('/vaccination-schedules/{vaccinationSchedule}', [VaccinationScheduleController::class, 'show']);
Route::put('/vaccination-schedules/{vaccinationSchedule}', [VaccinationScheduleController::class, 'update']);
Route::post('/vaccination-schedules/{vaccinationSchedule}/administer', [VaccinationScheduleController::class, 'administer']);
Route::post('/vaccination-schedules/{vaccinationSchedule}/cancel', [VaccinationScheduleController::class, 'cancel']);
Route::delete('/vaccination-schedules/{vaccinationSchedule}', [VaccinationScheduleController::class, 'destroy']);

Route::get('/subscription/current', [SubscriptionController::class, 'userSubscription']);
Route::post('/subscription/subscribe/{tier}', [SubscriptionController::class, 'subscribe']);
Route::post('/subscription/upgrade/{tier}', [SubscriptionController::class, 'upgrade']);
Route::post('/subscription/downgrade/{tier}', [SubscriptionController::class, 'downgrade']);
Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel']);
Route::post('/subscription/process-payment', [SubscriptionController::class, 'processPayment']);
Route::post('/subscription/bank-transfer', [SubscriptionController::class, 'bankTransfer']);
Route::post('/subscription/admin/set-tier/{user}/{tier}', [SubscriptionController::class, 'adminSetTier']);
Route::get('/subscription/admin/subscriptions', [SubscriptionController::class, 'adminListSubscriptions']);
Route::get('/subscription/admin/pending-payments', [SubscriptionController::class, 'adminListPendingPayments']);
Route::post('/subscription/admin/approve-payment/{subscription}', [SubscriptionController::class, 'adminApprovePayment']);
Route::post('/subscription/admin/reject-payment/{subscription}', [SubscriptionController::class, 'adminRejectPayment']);
Route::post('/subscription/admin/tiers', [SubscriptionController::class, 'createTier']);
Route::put('/subscription/admin/tiers/{tier}', [SubscriptionController::class, 'updateTier']);
Route::delete('/subscription/admin/tiers/{tier}', [SubscriptionController::class, 'deleteTier']);

Route::get('/tasks', [TaskController::class, 'index']);
Route::get('/tasks/my', [TaskController::class, 'myTasks']);
Route::get('/tasks/stats', [TaskController::class, 'stats']);
Route::post('/tasks', [TaskController::class, 'store']);
Route::get('/tasks/{task}', [TaskController::class, 'show']);
Route::put('/tasks/{task}', [TaskController::class, 'update']);
Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
Route::post('/tasks/{task}/complete', [TaskController::class, 'complete']);
Route::get('/tasks/{task}/logs', [TaskLogController::class, 'logsForTask']);

Route::get('/task-logs', [TaskLogController::class, 'index']);
Route::get('/task-logs/archive', [TaskLogController::class, 'archive']);
Route::get('/task-logs/my', [TaskLogController::class, 'myLogs']);
Route::post('/task-logs', [TaskLogController::class, 'store']);
Route::get('/task-logs/{taskLog}', [TaskLogController::class, 'show']);
Route::put('/task-logs/{taskLog}', [TaskLogController::class, 'update']);
Route::delete('/task-logs/{taskLog}', [TaskLogController::class, 'destroy']);

Route::get('/predefined-tasks', [PredefinedTaskController::class, 'index']);
Route::post('/predefined-tasks', [PredefinedTaskController::class, 'store']);
Route::get('/predefined-tasks/{predefinedTask}', [PredefinedTaskController::class, 'show']);
Route::put('/predefined-tasks/{predefinedTask}', [PredefinedTaskController::class, 'update']);
Route::delete('/predefined-tasks/{predefinedTask}', [PredefinedTaskController::class, 'destroy']);

Route::get('/admin/settings/general', [AdminSettingsController::class, 'getGeneralSettings']);
Route::post('/admin/settings/general', [AdminSettingsController::class, 'saveGeneralSettings']);
Route::get('/admin/settings/smtp', [AdminSettingsController::class, 'getSmtpSettings']);
Route::post('/admin/settings/smtp', [AdminSettingsController::class, 'saveSmtpSettings']);
Route::post('/admin/settings/smtp/test', [AdminSettingsController::class, 'testSmtpConnection']);
Route::get('/admin/settings/stripe', [AdminSettingsController::class, 'getStripeSettings']);
Route::post('/admin/settings/stripe', [AdminSettingsController::class, 'saveStripeSettings']);
Route::get('/admin/settings/gemini', [AdminSettingsController::class, 'getGeminiSettings']);
Route::post('/admin/settings/gemini', [AdminSettingsController::class, 'saveGeminiSettings']);
Route::get('/admin/settings/whatsapp', [AdminSettingsController::class, 'getWhatsAppSettings']);
Route::post('/admin/settings/whatsapp', [AdminSettingsController::class, 'saveWhatsAppSettings']);
Route::get('/admin/settings/twilio', [AdminSettingsController::class, 'getTwilioSettings']);
Route::post('/admin/settings/twilio', [AdminSettingsController::class, 'saveTwilioSettings']);
Route::get('/admin/settings/notifications', [AdminSettingsController::class, 'getNotificationSettings']);
Route::post('/admin/settings/notifications', [AdminSettingsController::class, 'saveNotificationSettings']);

Route::get('/export/animals', [ExportController::class, 'exportAnimals']);
Route::get('/export/devices', [ExportController::class, 'exportDevices']);
Route::get('/export/geofences', [ExportController::class, 'exportGeofences']);
Route::get('/export/users', [ExportController::class, 'exportUsers']);
Route::get('/export/database', [ExportController::class, 'exportDatabase']);

Route::get('/species', [SpeciesController::class, 'index']);
Route::post('/species', [SpeciesController::class, 'store']);
Route::put('/species/{species}', [SpeciesController::class, 'update']);
Route::delete('/species/{species}', [SpeciesController::class, 'destroy']);
Route::post('/species/{species}/breeds', [SpeciesController::class, 'storeBreed']);
Route::put('/breeds/{breed}', [SpeciesController::class, 'updateBreed']);
Route::delete('/breeds/{breed}', [SpeciesController::class, 'destroyBreed']);
