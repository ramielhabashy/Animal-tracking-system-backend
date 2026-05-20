<?php

namespace App\Http\Controllers\Api\Ai;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\AiQuickAction;
use App\Models\Animal;
use App\Models\Device;
use App\Models\GeofenceAlert;
use App\Models\Task;
use App\Models\Auction;
use App\Models\MedicalRecord;
use App\Models\VaccinationSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class AIController extends Controller
{
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
            'conversation' => 'nullable|array',
            'page' => 'nullable|string',
            'pageName' => 'nullable|string',
        ]);

        $message = $request->input('message');
        $conversation = $request->input('conversation', []);
        $page = $request->input('page');
        $pageName = $request->input('pageName');

        $settings = $this->getAiSettings();
        $provider = $settings['provider'] ?? 'disabled';
        $apiKey = $settings['api_key'] ?? null;
        $model = $settings['model'] ?? null;

        if ($provider === 'disabled' || !$apiKey) {
            return response()->json([
                'error' => 'AI assistant is not configured. Please ask an admin to configure AI in Settings.',
            ], 400);
        }

        $user = $request->user();
        $context = $this->getUserContext($user);
        $systemPrompt = $this->buildSystemPrompt($user, $context, $page, $pageName);

        try {
            $reply = match ($provider) {
                'groq' => $this->callGroq($apiKey, $model, $systemPrompt, $message, $conversation),
                'gemini' => $this->callGemini($apiKey, $model, $systemPrompt, $message, $conversation),
                'openai' => $this->callOpenAI($apiKey, $model, $systemPrompt, $message, $conversation),
                default => throw new \Exception('Unknown AI provider: ' . $provider),
            };

            return response()->json([
                'reply' => $reply,
                'model' => $model,
                'provider' => $provider,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'AI service error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function quickActions(Request $request): JsonResponse
    {
        $user = $request->user();
        $primaryRole = $user?->getPrimaryRoleName();

        $actions = AiQuickAction::active()
            ->forRole($primaryRole)
            ->ordered()
            ->get(['id', 'type', 'icon', 'label', 'prompt']);

        return response()->json(['data' => $actions]);
    }

    public function status(Request $request): JsonResponse
    {
        $settings = $this->getAiSettings();
        $user = $request->user();

        $roleType = $user ? ($user->roles()->first()?->type ?? 'user') : 'user';
        $hasAccess = $roleType === 'admin' || $this->hasFeatureAccess($user);

        return response()->json([
            'available' => $settings['provider'] !== 'disabled' && !empty($settings['api_key']),
            'provider' => $settings['provider'],
            'model' => $settings['model'],
            'hasAccess' => $hasAccess,
        ]);
    }

    public function generateReport(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string',
            'format' => 'required|in:pdf,csv',
            'filename' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
        ]);

        $content = $request->input('content');
        $format = $request->input('format');
        $filename = $request->input('filename', 'report-' . now()->format('Y-m-d-His'));
        $title = $request->input('title', 'AI Generated Report');

        try {
            if ($format === 'csv') {
                return $this->generateCsvReport($content, $filename);
            }

            return $this->generatePdfReport($content, $filename, $title);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Report generation failed: ' . $e->getMessage()], 500);
        }
    }

    public function listConversations(Request $request): JsonResponse
    {
        $user = $request->user();
        $conversations = AiConversation::where('user_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->get(['id', 'title', 'page_name', 'updated_at', 'created_at']);

        return response()->json(['data' => $conversations]);
    }

    public function getConversation(Request $request, AiConversation $conversation): JsonResponse
    {
        if ($conversation->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return response()->json(['data' => $conversation]);
    }

    public function saveConversation(Request $request): JsonResponse
    {
        $request->validate([
            'messages' => 'required|array',
            'title' => 'nullable|string|max:255',
            'page' => 'nullable|string',
            'pageName' => 'nullable|string',
        ]);

        $user = $request->user();
        $messages = $request->input('messages');

        $firstMsg = collect($messages)->firstWhere('role', 'user');
        $title = $request->input('title');
        if (!$title && $firstMsg) {
            $title = mb_substr($firstMsg['content'] ?? 'Conversation', 0, 100);
        }

        $conversation = AiConversation::create([
            'user_id' => $user->id,
            'title' => $title ?: 'Conversation',
            'messages' => $messages,
            'page' => $request->input('page'),
            'page_name' => $request->input('pageName'),
        ]);

        return response()->json(['data' => $conversation], 201);
    }

    public function updateConversation(Request $request, AiConversation $conversation): JsonResponse
    {
        if ($conversation->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'messages' => 'required|array',
            'title' => 'nullable|string|max:255',
        ]);

        $conversation->update([
            'messages' => $request->input('messages'),
            'title' => $request->input('title', $conversation->title),
        ]);

        return response()->json(['data' => $conversation]);
    }

    public function deleteConversation(Request $request, AiConversation $conversation): JsonResponse
    {
        if ($conversation->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $conversation->delete();

        return response()->json(['message' => 'Conversation deleted']);
    }

    protected function getAiSettings(): array
    {
        $settings = DB::table('settings')
            ->where('key', 'like', 'ai_%')
            ->orWhere('key', 'like', 'gemini_%')
            ->pluck('value', 'key')
            ->toArray();

        $provider = $settings['ai_provider'] ?? ($settings['gemini_enabled'] ?? false ? 'gemini' : 'disabled');
        $apiKey = $settings['ai_api_key'] ?? $settings['gemini_api_key'] ?? null;

        if ($provider !== 'disabled' && empty($apiKey)) {
            $apiKey = config('services.groq.api_key');
        }

        $defaultModels = [
            'groq' => 'llama-3.3-70b-versatile',
            'gemini' => 'gemini-2.0-flash',
            'openai' => 'gpt-4o',
        ];

        $model = $settings['ai_model'] ?? $settings['gemini_model'] ?? ($defaultModels[$provider] ?? 'llama-3.3-70b-versatile');

        return [
            'provider' => $provider,
            'api_key' => $apiKey ?? '',
            'model' => $model,
        ];
    }

    protected function callGroq(string $apiKey, string $model, string $systemPrompt, string $message, array $conversation): string
    {
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        foreach ($conversation as $msg) {
            $messages[] = [
                'role' => ($msg['role'] ?? 'user') === 'assistant' ? 'assistant' : 'user',
                'content' => $msg['content'] ?? '',
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        $response = Http::timeout(120)->withoutVerifying()->withToken($apiKey)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 8192,
        ]);

        if ($response->failed()) {
            $error = $response->json();
            throw new \Exception($error['error']['message'] ?? 'Groq API error');
        }

        return $response->json()['choices'][0]['message']['content'] ?? 'No response generated.';
    }

    protected function callGemini(string $apiKey, string $model, string $systemPrompt, string $message, array $conversation): string
    {
        $contents = [['role' => 'user', 'parts' => [['text' => $systemPrompt]]]];

        foreach ($conversation as $msg) {
            $contents[] = [
                'role' => ($msg['role'] ?? 'user') === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $msg['content'] ?? '']],
            ];
        }

        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

        $response = Http::timeout(120)->withoutVerifying()->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
            ['contents' => $contents, 'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 8192]]
        );

        if ($response->failed()) {
            throw new \Exception('Gemini API error: ' . ($response->json()['error']['message'] ?? 'Unknown'));
        }

        return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? 'No response generated.';
    }

    protected function callOpenAI(string $apiKey, string $model, string $systemPrompt, string $message, array $conversation): string
    {
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        foreach ($conversation as $msg) {
            $messages[] = [
                'role' => ($msg['role'] ?? 'user') === 'assistant' ? 'assistant' : 'user',
                'content' => $msg['content'] ?? '',
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        $response = Http::timeout(120)->withoutVerifying()->withToken($apiKey)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 8192,
        ]);

        if ($response->failed()) {
            $error = $response->json();
            throw new \Exception($error['error']['message'] ?? 'OpenAI API error');
        }

        return $response->json()['choices'][0]['message']['content'] ?? 'No response generated.';
    }

    protected function getUserContext($user): array
    {
        if (!$user) {
            return ['user' => ['name' => 'Guest', 'role' => 'Guest']];
        }

        $role = $user->getPrimaryRoleName();
        $roleType = $user->roles->first()?->type ?? 'user';
        $context = [
            'user' => [
                'name' => $user->name,
                'role' => $role,
                'role_type' => $roleType,
            ],
        ];

        $isAdmin = in_array($role, ['Admin', 'Support', 'Accountant', 'Customer Service']) || $roleType === 'admin';

        if ($isAdmin) {
            $context['users'] = ['total' => User::count()];
            $context['animals'] = [
                'total' => Animal::count(),
                'species_breakdown' => Animal::select('species', DB::raw('count(*) as count'))
                    ->groupBy('species')->pluck('count', 'species')->toArray(),
            ];
            $context['devices'] = [
                'total' => Device::count(),
                'online' => Device::where('status', 'online')->count(),
                'offline' => Device::where('status', '!=', 'online')->count(),
            ];
            $context['alerts'] = [
                'active' => GeofenceAlert::where('is_acknowledged', false)->count(),
                'recent' => GeofenceAlert::where('created_at', '>=', now()->subDays(7))
                    ->take(5)->get(['id', 'type', 'animal_id', 'is_acknowledged'])->toArray(),
            ];
            $context['tasks'] = [
                'pending' => Task::where('status', 'pending')->count(),
                'overdue' => Task::where('status', 'pending')->where('due_date', '<', now())->count(),
            ];
            $context['auctions'] = [
                'active' => Auction::where('status', 'active')->count(),
                'pending_approval' => Auction::where('status', 'draft')->count(),
            ];
            $context['insights'] = $this->getAdminInsights();
        } else {
            $userId = $user->id;
            $context['animals'] = [
                'total' => Animal::where('owner_id', $userId)->count(),
                'recent' => Animal::where('owner_id', $userId)
                    ->latest()->take(5)->get(['id', 'name', 'species'])->toArray(),
                'species_breakdown' => Animal::where('owner_id', $userId)
                    ->select('species', DB::raw('count(*) as count'))
                    ->groupBy('species')->pluck('count', 'species')->toArray(),
            ];
            $context['devices'] = [
                'total' => Device::whereHas('animal', fn($q) => $q->where('owner_id', $userId))->count(),
            ];
            $context['alerts'] = [
                'active' => GeofenceAlert::whereIn('animal_id', fn($q) => $q->select('id')->from('animals')->where('owner_id', $userId))
                    ->where('is_acknowledged', false)->count(),
            ];
            $context['tasks'] = [
                'pending' => Task::where('owner_id', $userId)->where('status', 'pending')->count(),
                'overdue' => Task::where('owner_id', $userId)->where('status', 'pending')
                    ->where('due_date', '<', now())->count(),
                'assigned_to_me' => Task::where('assigned_to', $userId)->where('status', 'pending')->count(),
            ];
            $context['insights'] = $this->getOwnerInsights($userId);
        }

        return $context;
    }

    protected function getAdminInsights(): array
    {
        return [
            'low_battery_devices' => Device::where('battery_level', '<', 20)->count(),
            'devices_no_ping_24h' => Device::where('last_ping', '<', now()->subDay())->count(),
            'overdue_vaccinations' => VaccinationSchedule::whereIn('status', ['scheduled', 'overdue'])
                ->where('scheduled_date', '<', now())->count(),
            'recent_medical_by_type' => MedicalRecord::where('created_at', '>=', now()->subDays(30))
                ->select('record_type', DB::raw('count(*) as count'))
                ->groupBy('record_type')->orderByDesc('count')->get()->toArray(),
            'repeat_alerts_7d' => GeofenceAlert::where('created_at', '>=', now()->subDays(7))->count(),
            'unresolved_alerts' => GeofenceAlert::where('is_acknowledged', false)
                ->distinct('animal_id')->count('animal_id'),
            'signal_issues' => Device::whereNotNull('signal_strength')
                ->where('signal_strength', '<', 30)->count(),
        ];
    }

    protected function getOwnerInsights(int $userId): array
    {
        return [
            'low_battery_devices' => Device::whereHas('animal', fn($q) => $q->where('owner_id', $userId))
                ->where('battery_level', '<', 20)->count(),
            'devices_no_ping_24h' => Device::whereHas('animal', fn($q) => $q->where('owner_id', $userId))
                ->where('last_ping', '<', now()->subDay())->count(),
            'overdue_vaccinations' => VaccinationSchedule::where('owner_id', $userId)
                ->whereIn('status', ['scheduled', 'overdue'])
                ->where('scheduled_date', '<', now())->count(),
            'recent_medical_by_type' => MedicalRecord::where('created_at', '>=', now()->subDays(30))
                ->whereHas('animal', fn($q) => $q->where('owner_id', $userId))
                ->select('record_type', DB::raw('count(*) as count'))
                ->groupBy('record_type')->orderByDesc('count')->get()->toArray(),
            'repeat_alerts_7d' => GeofenceAlert::where('created_at', '>=', now()->subDays(7))
                ->whereIn('animal_id', fn($q) => $q->select('id')->from('animals')->where('owner_id', $userId))
                ->count(),
            'unresolved_alerts' => GeofenceAlert::where('is_acknowledged', false)
                ->whereIn('animal_id', fn($q) => $q->select('id')->from('animals')->where('owner_id', $userId))
                ->distinct('animal_id')->count('animal_id'),
            'signal_issues' => Device::whereHas('animal', fn($q) => $q->where('owner_id', $userId))
                ->whereNotNull('signal_strength')->where('signal_strength', '<', 30)->count(),
        ];
    }

    protected function buildSystemPrompt($user, array $context, ?string $page = null, ?string $pageName = null): string
    {
        $json = json_encode($context, JSON_PRETTY_PRINT);

        $pageContext = '';
        if ($page && $pageName) {
            $pageContext = "\n\nThe user is currently viewing: **{$pageName}** ({$page}). Keep this context in mind when answering — if they're on a specific animal page, focus on that animal; if on the map, answer about locations; etc.";
        } elseif ($page) {
            $pageContext = "\n\nThe user is currently on page: {$page}.";
        }

        $routeTable = "
AVAILABLE ROUTES (use these for Markdown links in your responses):
- Dashboard: [/dashboard](/dashboard)
- Animals List: [/animals](/animals)
- Animal Details: [/animals/{id}](/animals/5)
- Devices List: [/devices](/devices)
- Live Map: [/map](/map)
- Auctions: [/auctions](/auctions)
- Tasks: [/tasks](/tasks)
- Medical Records: [/medical-records](/medical-records)
- Vaccination Schedule: [/vaccination-schedule](/vaccination-schedule)
- Alerts: [/alerts](/alerts)
- Geofences: [/geofences](/geofences)
- Reports: [/reports](/reports)
- Transfers: [/transfers](/transfers)
- Messages: [/messages](/messages)
- Profile: [/profile](/profile)
";

        return <<<EOT
You are an AI assistant for The Oasis — a livestock tracking and management platform.
You help users manage their herds, devices, tasks, and other operations.

CURRENT USER CONTEXT (JSON):
{$json}{$pageContext}

{$routeTable}
CAPABILITIES:
- Answer questions about animals, devices, alerts, tasks, auctions, users, and other data
- Provide livestock management advice (health, nutrition, breeding)
- Generate reports and summaries
- Detect recurring issues (low battery devices, overdue vaccinations, repeat medical visits)
- Help find specific items and navigate the platform
- Support both English and Arabic

RULES:
- Be concise, helpful, and professional
- Use the user's actual data from the JSON context — do not make up information
- CRITICAL: Use Markdown links when referencing entities:
  * Animals: [Animal Name](/animals/{id})
  * Tasks: [Task Title](/tasks/{id})
  * Devices: [Device Name](/devices/{id})
  * Auctions: [Auction Title](/auctions/{id})
  * Pages: [Dashboard](/dashboard), [Map](/map), [Tasks](/tasks), etc.
- Format lists and tables using Markdown for readability
- If you detect issues (e.g., low battery, overdue vaccinations), highlight them prominently
- For Arabic responses, use proper Arabic livestock terminology
- If data is insufficient, say so honestly

User message follows. Respond based on the context above.
EOT;
    }

    protected function generatePdfReport(string $content, string $filename, string $title): JsonResponse
    {
        $html = view('reports.ai', [
            'title' => $title,
            'content' => $content,
            'direction' => 'ltr',
        ])->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4');

        $tempPath = storage_path('app/temp/' . $filename . '.pdf');
        $dir = dirname($tempPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pdf->save($tempPath);

        return response()->json([
            'url' => url('storage/temp/' . $filename . '.pdf'),
            'filename' => $filename . '.pdf',
        ]);
    }

    protected function generateCsvReport(string $content, string $filename): JsonResponse
    {
        $rows = $this->parseContentToCsvRows($content);

        $tempPath = storage_path('app/temp/' . $filename . '.csv');
        $dir = dirname($tempPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $handle = fopen($tempPath, 'w');
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        return response()->json([
            'url' => url('storage/temp/' . $filename . '.csv'),
            'filename' => $filename . '.csv',
        ]);
    }

    protected function parseContentToCsvRows(string $content): array
    {
        $rows = [];
        $lines = explode("\n", $content);
        $inTable = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (str_starts_with($trimmed, '|') && str_ends_with($trimmed, '|')) {
                $inTable = true;
                $cells = array_map('trim', explode('|', trim($trimmed, '|')));
                if (preg_match('/^[-| ]+$/', $trimmed)) continue;
                $rows[] = $cells;
            } elseif ($inTable) {
                $inTable = false;
            }
        }

        if (empty($rows)) {
            $rows[] = ['content', $content];
        }

        return $rows;
    }

    protected function hasFeatureAccess($user): bool
    {
        return \App\Services\FeatureGate::canAccessFeature($user, 'ai_assistant')['allowed'] ?? false;
    }
}
