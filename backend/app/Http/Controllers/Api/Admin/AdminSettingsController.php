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

    public function getWhatsAppSettings(): JsonResponse
    {
        $settings = DB::table('settings')->where('key', 'like', 'whatsapp_%')->pluck('value', 'key')->toArray();
        
        return response()->json([
            'data' => [
                'api_url' => $settings['whatsapp_api_url'] ?? env('WHATSAPP_API_URL', ''),
                'api_token' => $settings['whatsapp_api_token'] ?? env('WHATSAPP_API_TOKEN', ''),
                'phone_number_id' => $settings['whatsapp_phone_number_id'] ?? env('WHATSAPP_PHONE_NUMBER_ID', ''),
                'business_account_id' => $settings['whatsapp_business_account_id'] ?? env('WHATSAPP_BUSINESS_ACCOUNT_ID', ''),
                'enabled' => filter_var($settings['whatsapp_enabled'] ?? env('WHATSAPP_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
            ]
        ]);
    }

    public function saveWhatsAppSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'api_url' => 'nullable|string',
            'api_token' => 'nullable|string',
            'phone_number_id' => 'nullable|string',
            'business_account_id' => 'nullable|string',
            'enabled' => 'boolean',
        ]);

        $settings = [
            'whatsapp_api_url' => $validated['api_url'] ?? '',
            'whatsapp_api_token' => $validated['api_token'] ?? '',
            'whatsapp_phone_number_id' => $validated['phone_number_id'] ?? '',
            'whatsapp_business_account_id' => $validated['business_account_id'] ?? '',
            'whatsapp_enabled' => $validated['enabled'] ?? false,
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        return response()->json(['message' => 'WhatsApp settings saved successfully']);
    }

    public function getTwilioSettings(): JsonResponse
    {
        $settings = DB::table('settings')->where('key', 'like', 'twilio_%')->pluck('value', 'key')->toArray();
        
        return response()->json([
            'data' => [
                'account_sid' => $settings['twilio_account_sid'] ?? env('TWILIO_ACCOUNT_SID', ''),
                'auth_token' => $settings['twilio_auth_token'] ?? env('TWILIO_AUTH_TOKEN', ''),
                'phone_number' => $settings['twilio_phone_number'] ?? env('SMS_FROM_NUMBER', ''),
                'enabled' => filter_var($settings['twilio_enabled'] ?? env('SMS_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
            ]
        ]);
    }

    public function saveTwilioSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_sid' => 'nullable|string',
            'auth_token' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'enabled' => 'boolean',
        ]);

        $settings = [
            'twilio_account_sid' => $validated['account_sid'] ?? '',
            'twilio_auth_token' => $validated['auth_token'] ?? '',
            'twilio_phone_number' => $validated['phone_number'] ?? '',
            'twilio_enabled' => $validated['enabled'] ?? false,
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        return response()->json(['message' => 'Twilio SMS settings saved successfully']);
    }

    public function getNotificationSettings(): JsonResponse
    {
        $settings = DB::table('settings')->where('key', 'like', 'notification_%')->pluck('value', 'key')->toArray();
        
        return response()->json([
            'data' => [
                'email_notifications' => $settings['notification_email'] ?? true,
                'sms_notifications' => $settings['notification_sms'] ?? false,
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
        return response()->json([
            'data' => [
                'auto_approve' => filter_var(DB::table('settings')->where('key', 'auction_auto_approve')->value('value') ?? false, FILTER_VALIDATE_BOOLEAN),
                'commission_enabled' => filter_var(DB::table('settings')->where('key', 'transfer_commission_enabled')->value('value') ?? false, FILTER_VALIDATE_BOOLEAN),
                'commission_type' => DB::table('settings')->where('key', 'transfer_commission_type')->value('value') ?? 'percentage',
                'commission_percentage' => (float) (DB::table('settings')->where('key', 'transfer_commission_percentage')->value('value') ?? 5),
                'commission_fixed' => (float) (DB::table('settings')->where('key', 'transfer_commission_fixed')->value('value') ?? 0),
            ]
        ]);
    }

    public function saveAuctionSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'auto_approve' => 'boolean',
        ]);

        DB::table('settings')->updateOrInsert(
            ['key' => 'auction_auto_approve'],
            ['value' => $validated['auto_approve'] ?? false, 'updated_at' => now()]
        );

        return response()->json(['message' => 'Auction settings saved successfully']);
    }

    public function getTransferCommissionSettings(): JsonResponse
    {
        $settings = DB::table('settings')->where('key', 'like', 'transfer_commission_%')->pluck('value', 'key')->toArray();

        return response()->json([
            'data' => [
                'enabled' => filter_var($settings['transfer_commission_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'type' => $settings['transfer_commission_type'] ?? 'percentage',
                'percentage' => (float) ($settings['transfer_commission_percentage'] ?? 5.00),
                'fixed' => (float) ($settings['transfer_commission_fixed'] ?? 0.00),
            ]
        ]);
    }

    public function saveTransferCommissionSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => 'boolean',
            'type' => 'in:percentage,fixed',
            'percentage' => 'numeric|min:0|max:100',
            'fixed' => 'numeric|min:0',
        ]);

        $settings = [
            'transfer_commission_enabled' => $validated['enabled'] ?? false,
            'transfer_commission_type' => $validated['type'] ?? 'percentage',
            'transfer_commission_percentage' => $validated['percentage'] ?? 5.00,
            'transfer_commission_fixed' => $validated['fixed'] ?? 0.00,
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