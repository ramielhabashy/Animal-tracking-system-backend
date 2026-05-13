<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PublicSettingsController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = DB::table('settings')->where(function ($q) {
            $q->where('key', 'general_platform_name')
              ->orWhere('key', 'general_logo')
              ->orWhere('key', 'general_favicon')
              ->orWhere('key', 'general_login_background')
              ->orWhere('key', 'general_copyright_text')
              ->orWhere('key', 'general_platform_url');
        })->pluck('value', 'key')->toArray();

        return response()->json([
            'data' => [
                'platform_name' => $settings['general_platform_name'] ?? 'The Oasis',
                'platform_url' => $settings['general_platform_url'] ?? '',
                'logo_url' => $settings['general_logo'] ?? '',
                'favicon_url' => $settings['general_favicon'] ?? '',
                'login_background_url' => $settings['general_login_background'] ?? '',
                'copyright_text' => $settings['general_copyright_text'] ?? 'Digital Majlis.',
            ]
        ]);
    }
}
