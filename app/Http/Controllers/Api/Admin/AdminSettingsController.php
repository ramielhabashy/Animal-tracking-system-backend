<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class AdminSettingsController extends Controller
{
    public function getSmtpSettings(): JsonResponse
    {
        $settings = DB::table('settings')->where('key', 'like', 'smtp_%')->pluck('value', 'key')->toArray();
        
        return response()->json([
            'data' => [
                'host' => $settings['smtp_host'] ?? '',
                'port' => $settings['smtp_port'] ?? '',
                'username' => $settings['smtp_username'] ?? '',
                'password' => $settings['smtp_password'] ?? '',
                'encryption' => $settings['smtp_encryption'] ?? 'tls',
                'from_email' => $settings['smtp_from_email'] ?? '',
                'from_name' => $settings['smtp_from_name'] ?? '',
            ]
        ]);
    }

    public function saveSmtpSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'host' => 'required|string',
            'port' => 'required|numeric',
            'username' => 'required|string',
            'password' => 'required|string',
            'encryption' => 'nullable|in:tls,ssl,none',
            'from_email' => 'required|email',
            'from_name' => 'required|string',
        ]);

        $settings = [
            'smtp_host' => $validated['host'],
            'smtp_port' => $validated['port'],
            'smtp_username' => $validated['username'],
            'smtp_password' => $validated['password'],
            'smtp_encryption' => $validated['encryption'] ?? 'tls',
            'smtp_from_email' => $validated['from_email'],
            'smtp_from_name' => $validated['from_name'],
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        return response()->json(['message' => 'SMTP settings saved successfully']);
    }

    public function getGeneralSettings(): JsonResponse
    {
        $settings = DB::table('settings')->where('key', 'like', 'general_%')->pluck('value', 'key')->toArray();
        
        return response()->json([
            'data' => [
                'platform_name' => $settings['general_platform_name'] ?? 'The Oasis',
                'platform_url' => $settings['general_platform_url'] ?? 'http://localhost:5173',
                'admin_email' => $settings['general_admin_email'] ?? '',
                'timezone' => $settings['general_timezone'] ?? 'Asia/Dubai',
                'date_format' => $settings['general_date_format'] ?? 'Y-m-d',
                'default_language' => $settings['general_default_language'] ?? 'en',
                'logo' => $settings['general_logo'] ?? '',
                'favicon' => $settings['general_favicon'] ?? '',
                'login_background' => $settings['general_login_background'] ?? '',
                'copyright_text' => $settings['general_copyright_text'] ?? 'Digital Majlis.',
            ]
        ]);
    }

    public function saveGeneralSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform_name' => 'nullable|string|max:255',
            'platform_url' => 'nullable|url',
            'admin_email' => 'nullable|email',
            'timezone' => 'nullable|string',
            'date_format' => 'nullable|string',
            'default_language' => 'nullable|in:en,ar',
            'copyright_text' => 'nullable|string|max:255',
        ]);

        $settings = [
            'general_platform_name' => $validated['platform_name'] ?? 'The Oasis',
            'general_platform_url' => $validated['platform_url'] ?? 'http://localhost:5173',
            'general_admin_email' => $validated['admin_email'] ?? '',
            'general_timezone' => $validated['timezone'] ?? 'Asia/Dubai',
            'general_date_format' => $validated['date_format'] ?? 'Y-m-d',
            'general_default_language' => $validated['default_language'] ?? 'en',
            'general_copyright_text' => $validated['copyright_text'] ?? 'Digital Majlis.',
        ];

        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $logoPath = $logo->store('settings', 'public');
            $settings['general_logo'] = '/storage/' . $logoPath;
        } elseif ($request->has('logo') && empty($request->input('logo'))) {
            $settings['general_logo'] = '';
        }

        if ($request->hasFile('favicon')) {
            $favicon = $request->file('favicon');
            $faviconPath = $favicon->store('settings', 'public');
            $settings['general_favicon'] = '/storage/' . $faviconPath;
        } elseif ($request->has('favicon') && empty($request->input('favicon'))) {
            $settings['general_favicon'] = '';
        }

        if ($request->hasFile('login_background')) {
            $bg = $request->file('login_background');
            $bgPath = $bg->store('settings', 'public');
            $settings['general_login_background'] = '/storage/' . $bgPath;
        } elseif ($request->has('login_background') && empty($request->input('login_background'))) {
            $settings['general_login_background'] = '';
        }

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        return response()->json(['message' => 'General settings saved successfully']);
    }

    public function testSmtpConnection(Request $request): JsonResponse
    {
        try {
            $userId = $request->header('X-User-Id');
            $user = \App\Models\User::find($userId);
            
            Mail::raw('This is a test email from The Oasis platform. If you receive this, your SMTP settings are configured correctly!', function ($message) use ($user) {
                $message->to($user->email, $user->name)
                        ->subject('The Oasis - SMTP Test Email');
            });

            return response()->json(['message' => 'Test email sent successfully!']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send test email: ' . $e->getMessage()
            ], 400);
        }
    }

    public function getStripeSettings(): JsonResponse
    {
        $settings = DB::table('settings')->where('key', 'like', 'stripe_%')->pluck('value', 'key')->toArray();
        
        return response()->json([
            'data' => [
                'public_key' => $settings['stripe_public_key'] ?? '',
                'secret_key' => $settings['stripe_secret_key'] ?? '',
                'webhook_secret' => $settings['stripe_webhook_secret'] ?? '',
                'enabled' => filter_var($settings['stripe_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ]
        ]);
    }

    public function saveStripeSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'public_key' => 'required|string',
            'secret_key' => 'required|string',
            'webhook_secret' => 'nullable|string',
            'enabled' => 'boolean',
        ]);

        $settings = [
            'stripe_public_key' => $validated['public_key'],
            'stripe_secret_key' => $validated['secret_key'],
            'stripe_webhook_secret' => $validated['webhook_secret'] ?? '',
            'stripe_enabled' => $validated['enabled'] ?? false,
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        return response()->json(['message' => 'Stripe settings saved successfully']);
    }

    public function getGeminiSettings(): JsonResponse
    {
        $settings = DB::table('settings')->where('key', 'like', 'gemini_%')->pluck('value', 'key')->toArray();
        
        return response()->json([
            'data' => [
                'api_key' => $settings['gemini_api_key'] ?? '',
                'model' => $settings['gemini_model'] ?? 'gemini-pro',
                'enabled' => filter_var($settings['gemini_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ]
        ]);
    }

    public function saveGeminiSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'api_key' => 'required|string',
            'model' => 'required|string',
            'enabled' => 'boolean',
        ]);

        $settings = [
            'gemini_api_key' => $validated['api_key'],
            'gemini_model' => $validated['model'],
            'gemini_enabled' => $validated['enabled'] ?? false,
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        return response()->json(['message' => 'Gemini AI settings saved successfully']);
    }

    public function getAiSettings(): JsonResponse
    {
        $settings = DB::table('settings')
            ->where('key', 'like', 'ai_%')
            ->orWhere('key', 'like', 'gemini_%')
            ->pluck('value', 'key')
            ->toArray();

        return response()->json([
            'data' => [
                'provider' => $settings['ai_provider'] ?? ($settings['gemini_enabled'] ?? false ? 'gemini' : 'disabled'),
                'api_key' => $settings['ai_api_key'] ?? $settings['gemini_api_key'] ?? '',
                'model' => $settings['ai_model'] ?? $settings['gemini_model'] ?? 'llama-3.3-70b-versatile',
            ]
        ]);
    }

    public function saveAiSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'required|in:disabled,groq,gemini,openai',
            'api_key' => 'exclude_if:provider,disabled|required|string',
            'model' => 'required|string',
        ]);

        $settings = [
            'ai_provider' => $validated['provider'],
            'ai_api_key' => $validated['api_key'],
            'ai_model' => $validated['model'],
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        return response()->json(['message' => 'AI settings saved successfully']);
    }

    public function getNotificationSettings(): JsonResponse
    {
        $settings = DB::table('settings')->where('key', 'like', 'notification_%')->pluck('value', 'key')->toArray();
        
        return response()->json([
            'data' => [
                'email_notifications' => $settings['notification_email'] ?? true,
                'push_notifications' => $settings['notification_push'] ?? true,
            ]
        ]);
    }

    public function saveNotificationSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'push_notifications' => 'boolean',
        ]);

        foreach ($validated as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'notification_' . str_replace('notification_', '', $key)],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        return response()->json(['message' => 'Notification settings saved']);
    }

    public function getTranslationSettings(): JsonResponse
    {
        $settings = DB::table('settings')->where('key', 'like', 'translation_%')->pluck('value', 'key')->toArray();

        return response()->json([
            'data' => [
                'deepl_api_key' => $settings['translation_deepl_api_key'] ?? '',
                'google_api_key' => $settings['translation_google_api_key'] ?? '',
            ]
        ]);
    }

    public function saveTranslationSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'deepl_api_key' => 'nullable|string',
            'google_api_key' => 'nullable|string',
        ]);

        $settings = [
            'translation_deepl_api_key' => $validated['deepl_api_key'] ?? '',
            'translation_google_api_key' => $validated['google_api_key'] ?? '',
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        return response()->json(['message' => 'Translation settings saved successfully']);
    }

    public function getCountrySettings(): JsonResponse
    {
        $countries = DB::table('settings')->where('key', 'checkout_countries')->value('value');
        $list = $countries ? json_decode($countries, true) : ['Saudi Arabia'];

        return response()->json(['data' => $list]);
    }

    public function saveCountrySettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'countries' => 'required|array|min:1',
            'countries.*' => 'required|string|max:255',
        ]);

        DB::table('settings')->updateOrInsert(
            ['key' => 'checkout_countries'],
            ['value' => json_encode($validated['countries']), 'updated_at' => now()]
        );

        return response()->json(['message' => 'Countries saved successfully']);
    }

    public function getEmailNotificationPreferences(): JsonResponse
    {
        $prefs = DB::table('settings')->where('key', 'like', 'email_notify_%')->pluck('value', 'key')->toArray();

        return response()->json([
            'data' => [
                'welcome' => filter_var($prefs['email_notify_welcome'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'invitation' => filter_var($prefs['email_notify_invitation'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'subscription' => filter_var($prefs['email_notify_subscription'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'auction_won' => filter_var($prefs['email_notify_auction_won'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'auction_bid' => filter_var($prefs['email_notify_auction_bid'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'auction_payment' => filter_var($prefs['email_notify_auction_payment'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'task_assigned' => filter_var($prefs['email_notify_task_assigned'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'medical' => filter_var($prefs['email_notify_medical'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ]
        ]);
    }

    public function saveEmailNotificationPreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'welcome' => 'boolean',
            'invitation' => 'boolean',
            'subscription' => 'boolean',
            'auction_won' => 'boolean',
            'auction_bid' => 'boolean',
            'auction_payment' => 'boolean',
            'task_assigned' => 'boolean',
            'medical' => 'boolean',
        ]);

        foreach ($validated as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'email_notify_' . $key],
                ['value' => $value ? '1' : '0', 'updated_at' => now()]
            );
        }

        return response()->json(['message' => 'Email notification preferences saved']);
    }

    public function getAuctionSettings(): JsonResponse
    {
        $soldCount = \App\Models\Auction::where('status', 'sold')->count();
        $pendingPayments = \App\Models\Auction::where('status', 'sold')->where('payment_status', 'pending')->count();
        $auctionTransfers = \App\Models\OwnershipTransfer::where('transfer_type', 'auction')->count();

        return response()->json([
            'data' => [
                'auto_approve' => filter_var(DB::table('settings')->where('key', 'auction_auto_approve')->value('value') ?? false, FILTER_VALIDATE_BOOLEAN),
                'payment_expiry_hours' => (int) (DB::table('settings')->where('key', 'auction_payment_expiry_hours')->value('value') ?? 24),
                'second_winner_enabled' => filter_var(DB::table('settings')->where('key', 'auction_second_winner_enabled')->value('value') ?? true, FILTER_VALIDATE_BOOLEAN),
                'stats' => [
                    'total_sold' => $soldCount,
                    'pending_payments' => $pendingPayments,
                    'auction_transfers' => $auctionTransfers,
                ],
            ]
        ]);
    }

    public function saveAuctionSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'auto_approve' => 'boolean',
            'payment_expiry_hours' => 'integer|min:1|max:168',
            'second_winner_enabled' => 'boolean',
        ]);

        $settings = [
            'auction_auto_approve' => $validated['auto_approve'] ?? false,
            'auction_payment_expiry_hours' => $validated['payment_expiry_hours'] ?? 24,
            'auction_second_winner_enabled' => $validated['second_winner_enabled'] ?? true,
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        return response()->json(['message' => 'Auction settings saved successfully']);
    }

    public function getTransferCommissionSettings(): JsonResponse
    {
        $settings = DB::table('settings')->where('key', 'like', 'transfer_commission_%')->pluck('value', 'key')->toArray();

        $totalManual = \App\Models\OwnershipTransfer::where('transfer_type', 'manual')->count();
        $totalAuction = \App\Models\OwnershipTransfer::where('transfer_type', 'auction')->count();
        $totalCommission = \App\Models\OwnershipTransfer::where('status', 'completed')->sum('commission_amount');
        $paidCommission = \App\Models\OwnershipTransfer::where('status', 'completed')->where('commission_paid', true)->sum('commission_amount');

        return response()->json([
            'data' => [
                'enabled' => filter_var($settings['transfer_commission_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'manual' => [
                    'type' => $settings['transfer_commission_manual_type'] ?? ($settings['transfer_commission_type'] ?? 'percentage'),
                    'percentage' => (float) ($settings['transfer_commission_manual_percentage'] ?? $settings['transfer_commission_percentage'] ?? 5.00),
                    'fixed' => (float) ($settings['transfer_commission_manual_fixed'] ?? $settings['transfer_commission_fixed'] ?? 0.00),
                ],
                'auction' => [
                    'type' => $settings['transfer_commission_auction_type'] ?? ($settings['transfer_commission_type'] ?? 'percentage'),
                    'percentage' => (float) ($settings['transfer_commission_auction_percentage'] ?? $settings['transfer_commission_percentage'] ?? 5.00),
                    'fixed' => (float) ($settings['transfer_commission_auction_fixed'] ?? $settings['transfer_commission_fixed'] ?? 0.00),
                ],
                'stats' => [
                    'total_manual_transfers' => $totalManual,
                    'total_auction_transfers' => $totalAuction,
                    'total_commission' => $totalCommission,
                    'paid_commission' => $paidCommission,
                    'pending_commission' => $totalCommission - $paidCommission,
                ],
            ]
        ]);
    }

    public function getDeviceSettings(): JsonResponse
    {
        $settings = DB::table('settings')->where('key', 'like', 'device_%')->pluck('value', 'key')->toArray();

        return response()->json([
            'data' => [
                'device_simulator_enabled' => filter_var($settings['device_simulator_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'device_real_data_enabled' => filter_var($settings['device_real_data_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'device_real_api_endpoint' => $settings['device_real_api_endpoint'] ?? '',
                'device_real_api_key' => $settings['device_real_api_key'] ?? '',
                'device_real_driver' => $settings['device_real_driver'] ?? 'sani',
                'device_mqtt_enabled' => filter_var($settings['device_mqtt_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'device_mqtt_broker_host' => $settings['device_mqtt_broker_host'] ?? '',
                'device_mqtt_broker_port' => (int) ($settings['device_mqtt_broker_port'] ?? 1883),
                'device_mqtt_username' => $settings['device_mqtt_username'] ?? '',
                'device_mqtt_password' => $settings['device_mqtt_password'] ?? '',
                'device_mqtt_topic_prefix' => $settings['device_mqtt_topic_prefix'] ?? 'sani',
            ]
        ]);
    }

    public function saveDeviceSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_simulator_enabled' => 'boolean',
            'device_real_data_enabled' => 'boolean',
            'device_real_api_endpoint' => 'nullable|string|max:500',
            'device_real_api_key' => 'nullable|string|max:500',
            'device_real_driver' => 'nullable|string|in:sani,custom,mqtt',
            'device_mqtt_enabled' => 'boolean',
            'device_mqtt_broker_host' => 'nullable|string|max:500',
            'device_mqtt_broker_port' => 'nullable|integer|min:1|max:65535',
            'device_mqtt_username' => 'nullable|string|max:255',
            'device_mqtt_password' => 'nullable|string|max:500',
            'device_mqtt_topic_prefix' => 'nullable|string|max:100',
        ]);

        $settings = [
            'device_simulator_enabled' => $validated['device_simulator_enabled'] ?? true,
            'device_real_data_enabled' => $validated['device_real_data_enabled'] ?? false,
            'device_real_api_endpoint' => $validated['device_real_api_endpoint'] ?? '',
            'device_real_api_key' => $validated['device_real_api_key'] ?? '',
            'device_real_driver' => $validated['device_real_driver'] ?? 'sani',
            'device_mqtt_enabled' => $validated['device_mqtt_enabled'] ?? false,
            'device_mqtt_broker_host' => $validated['device_mqtt_broker_host'] ?? '',
            'device_mqtt_broker_port' => $validated['device_mqtt_broker_port'] ?? 1883,
            'device_mqtt_username' => $validated['device_mqtt_username'] ?? '',
            'device_mqtt_password' => $validated['device_mqtt_password'] ?? '',
            'device_mqtt_topic_prefix' => $validated['device_mqtt_topic_prefix'] ?? 'sani',
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : $value, 'updated_at' => now()]
            );
        }

        return response()->json(['message' => 'Device integration settings saved successfully']);
    }

    public function testMqttConnection(): JsonResponse
    {
        $host = DB::table('settings')->where('key', 'device_mqtt_broker_host')->value('value') ?? '';
        $port = (int) (DB::table('settings')->where('key', 'device_mqtt_broker_port')->value('value') ?? 1883);
        $username = DB::table('settings')->where('key', 'device_mqtt_username')->value('value') ?? '';
        $password = DB::table('settings')->where('key', 'device_mqtt_password')->value('value') ?? '';

        if (empty($host)) {
            return response()->json(['message' => 'MQTT broker host not configured'], 400);
        }

        try {
            $client = new \PhpMqtt\Client\MqttClient($host, $port, 'oasis-test-' . uniqid());
            if (!empty($username)) {
                $client->connect($username, $password);
            } else {
                $client->connect();
            }
            $client->disconnect();
            return response()->json(['message' => 'MQTT connection successful']);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'MQTT connection failed: ' . $e->getMessage()
            ], 400);
        }
    }

    public function saveTransferCommissionSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'manual.type' => 'nullable|string|in:percentage,fixed',
            'manual.percentage' => 'nullable|numeric|min:0|max:100',
            'manual.fixed' => 'nullable|numeric|min:0',
            'auction.type' => 'nullable|string|in:percentage,fixed',
            'auction.percentage' => 'nullable|numeric|min:0|max:100',
            'auction.fixed' => 'nullable|numeric|min:0',
        ]);

        $settings = [
            'transfer_commission_enabled' => $validated['enabled'] ?? false,
            'transfer_commission_manual_type' => $validated['manual']['type'] ?? 'percentage',
            'transfer_commission_manual_percentage' => $validated['manual']['percentage'] ?? 5.00,
            'transfer_commission_manual_fixed' => $validated['manual']['fixed'] ?? 0.00,
            'transfer_commission_auction_type' => $validated['auction']['type'] ?? 'percentage',
            'transfer_commission_auction_percentage' => $validated['auction']['percentage'] ?? 5.00,
            'transfer_commission_auction_fixed' => $validated['auction']['fixed'] ?? 0.00,
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        return response()->json(['message' => 'Transfer commission settings saved successfully']);
    }
}