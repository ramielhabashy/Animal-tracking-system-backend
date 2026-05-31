<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class WorkflowTestService
{
    protected string $baseUrl;
    protected array $state;
    protected array $results;
    protected bool $hasFailure;
    protected bool $skipCleanup;

    public function __construct(bool $skipCleanup = false)
    {
        $this->baseUrl = rtrim(env('APP_URL', 'http://localhost:8050'), '/');
        $this->state = [];
        $this->results = [];
        $this->hasFailure = false;
        $this->skipCleanup = $skipCleanup;
    }

    public function run(): array
    {
        $this->results = [];
        $this->hasFailure = false;
        $startTime = microtime(true);

        $steps = [
            'step_01_create_owner',
            'step_02_fetch_tiers',
            'step_03_login_owner',
            'step_04_init_checkout',
            'step_05_upload_proof',
            'step_06_login_admin',
            'step_07_approve_payment',
            'step_08_login_owner_again',
            'step_09_create_shepherd',
            'step_10_create_group',
            'step_11_assign_shepherd',
            'step_12_register_device',
            'step_13_create_animal_and_activate',
            'step_14_simulate_move',
            'step_15_verify_location',
            'step_16_create_medical_record',
            'step_17_create_task',
            'step_18_create_conversation',
            'step_19_login_shepherd',
            'step_20_complete_task',
            'step_21_reply_conversation',
            'step_22_create_geofence',
            'step_23_move_outside',
            'step_24_move_inside_entry',
            'step_25_set_temperature',
            'step_26_toggle_lost',
            'step_27_move_outside_exit',
            'step_28_verify_alerts',
            'step_29_acknowledge_alert',
            'step_30_unmark_lost',
        ];

        $this->state['created_user_ids'] = [];
        $this->state['created_entity_ids'] = [];

        try {
            foreach ($steps as $method) {
                if ($this->hasFailure) {
                    $this->results[] = [
                        'step' => count($this->results) + 1,
                        'name' => $this->stepName($method),
                        'method' => '',
                        'endpoint' => '',
                        'request_body' => null,
                        'response_status' => null,
                        'response_body' => null,
                        'status' => 'skipped',
                        'assertions' => [],
                        'duration_ms' => 0,
                        'error' => 'Dependency failed',
                    ];
                    continue;
                }

                $stepStart = microtime(true);
                try {
                    $this->{$method}();
                    $duration = round((microtime(true) - $stepStart) * 1000);
                    $last = &$this->results[count($this->results) - 1];
                    $last['duration_ms'] = $duration;
                    $last['status'] = 'passed';
                } catch (\Exception $e) {
                    $duration = round((microtime(true) - $stepStart) * 1000);
                    $this->hasFailure = true;
                    $last = &$this->results[count($this->results) - 1];
                    $last['duration_ms'] = $duration;
                    $last['status'] = 'failed';
                    $last['error'] = $e->getMessage();
                }
            }
        } finally {
            $this->cleanup();
        }

        $totalTime = round((microtime(true) - $startTime) * 1000);

        $passed = count(array_filter($this->results, fn($r) => $r['status'] === 'passed'));
        $failed = count(array_filter($this->results, fn($r) => $r['status'] === 'failed'));
        $skipped = count(array_filter($this->results, fn($r) => $r['status'] === 'skipped'));

        return [
            'results' => $this->results,
            'summary' => [
                'total' => count($this->results),
                'passed' => $passed,
                'failed' => $failed,
                'skipped' => $skipped,
                'duration_ms' => $totalTime,
            ],
        ];
    }

    protected function cleanup(): void
    {
        if ($this->skipCleanup) {
            return;
        }

        // Delete workflow-created data in reverse dependency order
        try {
            $animalId = $this->state['animal_id'] ?? null;
            $deviceId = $this->state['device_id'] ?? null;
            $groupId = $this->state['group_id'] ?? null;
            $geofenceId = $this->state['geofence_id'] ?? null;
            $conversationId = $this->state['conversation_id'] ?? null;
            $taskId = $this->state['task_id'] ?? null;
            $medicalRecordId = $this->state['medical_record_id'] ?? null;
            $orderId = $this->state['order_id'] ?? null;
            $ownerId = $this->state['owner_id'] ?? null;
            $shepherdId = $this->state['shepherd_id'] ?? null;

            // Geofence alerts
            if ($geofenceId) {
                \App\Models\GeofenceAlert::where('geofence_id', $geofenceId)->delete();
                \App\Models\Geofence::where('id', $geofenceId)->delete();
            }

            // Conversation messages + conversation
            if ($conversationId) {
                \App\Models\Message::where('conversation_id', $conversationId)->delete();
                \App\Models\Conversation::where('id', $conversationId)->delete();
            }

            // Task logs + task
            if ($taskId) {
                \App\Models\TaskLog::where('task_id', $taskId)->delete();
                \App\Models\Task::where('id', $taskId)->delete();
            }

            // Medical record
            if ($medicalRecordId) {
                \App\Models\MedicalRecord::where('id', $medicalRecordId)->delete();
            }

            // Location history
            if ($animalId) {
                \App\Models\LocationHistory::where('animal_id', $animalId)->delete();
                \App\Models\Animal::where('id', $animalId)->delete();
            }

            // Group shepherd assignments
            if ($groupId) {
                \Illuminate\Support\Facades\DB::table('group_shepherd')->where('group_id', $groupId)->delete();
                \App\Models\AnimalGroup::where('id', $groupId)->delete();
            }

            // Device
            if ($deviceId) {
                \App\Models\Device::where('id', $deviceId)->update(['animal_id' => null]);
                \App\Models\Device::where('id', $deviceId)->delete();
            }

            // Subscription order + subscription
            if ($orderId) {
                $order = \App\Models\SubscriptionOrder::find($orderId);
                if ($order && $order->user_subscription_id) {
                    \App\Models\UserSubscription::where('id', $order->user_subscription_id)->delete();
                }
                $order?->delete();
            }

            // Created users
            $userIds = array_filter([$shepherdId, $ownerId]);
            foreach ($userIds as $uid) {
                if ($uid && $uid != 1) {
                    \App\Models\User::where('id', $uid)->delete();
                }
            }

            $this->recordStep([
                'name' => 'Data Cleanup',
                'method' => 'DELETE',
                'endpoint' => '(internal)',
                'request_body' => null,
                'response_status' => 200,
                'response_body' => ['cleaned' => true],
                'assertions' => [],
            ]);
        } catch (\Exception $e) {
            // Cleanup failures should not break the overall test result
            $this->recordStep([
                'name' => 'Data Cleanup (partial)',
                'method' => 'DELETE',
                'endpoint' => '(internal)',
                'request_body' => null,
                'response_status' => 500,
                'response_body' => ['error' => $e->getMessage()],
                'assertions' => [],
            ]);
        }
    }

    protected function recordStep(array $data): void
    {
        $this->results[] = array_merge([
            'step' => count($this->results) + 1,
            'name' => '',
            'method' => '',
            'endpoint' => '',
            'request_body' => null,
            'response_status' => null,
            'response_body' => null,
            'status' => 'running',
            'assertions' => [],
            'duration_ms' => 0,
            'error' => null,
        ], $data);
    }

    protected function stepName(string $method): string
    {
        $names = [
            'step_01_create_owner' => 'Create Temp Owner',
            'step_02_fetch_tiers' => 'Fetch Subscription Tiers',
            'step_03_login_owner' => 'Login as Test Owner',
            'step_04_init_checkout' => 'Init Checkout (Bank Transfer)',
            'step_05_upload_proof' => 'Upload Payment Proof',
            'step_06_login_admin' => 'Login as Admin',
            'step_07_approve_payment' => 'Approve Payment',
            'step_08_login_owner_again' => 'Login as Test Owner (again)',
            'step_09_create_shepherd' => 'Create Shepherd User',
            'step_10_create_group' => 'Create Animal Group',
            'step_11_assign_shepherd' => 'Assign Shepherd to Group',
            'step_12_register_device' => 'Register Device',
            'step_13_create_animal_and_activate' => 'Create Animal & Activate Device',
            'step_14_simulate_move' => 'Simulate Animal Movement',
            'step_15_verify_location' => 'Verify GPS Location Updated',
            'step_16_create_medical_record' => 'Create Medical Record',
            'step_17_create_task' => 'Create Task for Shepherd',
            'step_18_create_conversation' => 'Create Conversation & Send Message',
            'step_19_login_shepherd' => 'Login as Shepherd',
            'step_20_complete_task' => 'Shepherd Completes Task',
            'step_21_reply_conversation' => 'Shepherd Replies in Conversation',
            'step_22_create_geofence' => 'Create Geofence',
            'step_23_move_outside' => 'Move Outside Geofence (init cache)',
            'step_24_move_inside_entry' => 'Move Inside Geofence (entry alert)',
            'step_25_set_temperature' => 'Set Temperature (41°C)',
            'step_26_toggle_lost' => 'Toggle Lost Animal',
            'step_27_move_outside_exit' => 'Move Outside Geofence (exit alert)',
            'step_28_verify_alerts' => 'Verify All Alerts & Status',
            'step_29_acknowledge_alert' => 'Acknowledge Entry Alert',
            'step_30_unmark_lost' => 'Unmark Lost Animal',
        ];
        return $names[$method] ?? $method;
    }

    protected function request(string $method, string $path, array $options = []): array
    {
        $url = $this->baseUrl . '/api' . $path;

        $body = $options['body'] ?? null;
        $multipart = $options['multipart'] ?? null;
        $token = $options['token'] ?? ($this->state['token'] ?? null);
        $isPublic = $options['public'] ?? false;

        $http = Http::timeout(60)->withHeaders([
            'Accept' => 'application/json',
        ]);

        if ($token) {
            $http = $http->withToken($token);
        }

        $http = $http->withOptions(['verify' => false]);

        $reqBody = null;
        $logBody = null;

        try {
            if ($multipart) {
                $http = $http->asMultipart();
                $reqBody = '(multipart form data)';
                $logBody = '(multipart)';
                $response = $http->send($method, $url, ['multipart' => $multipart]);
            } elseif ($body !== null) {
                $reqBody = $body;
                $logBody = $this->sanitize($body, ['password', 'password_confirmation', 'token']);
                $response = $http->send($method, $url, ['json' => $body]);
            } else {
                $response = $http->send($method, $url);
            }

            $status = $response->status();
            $respBody = $response->json() ?? $response->body();
        } catch (\Exception $e) {
            throw new \RuntimeException("HTTP request failed: " . $e->getMessage());
        }

        return [
            'status' => $status,
            'body' => $respBody,
            'request_body' => $logBody,
        ];
    }

    protected function sanitize(?array $data, array $keys): ?array
    {
        if (!$data) return null;
        $result = $data;
        foreach ($keys as $key) {
            if (isset($result[$key])) {
                $result[$key] = '[REDACTED]';
            }
        }
        return $result;
    }

    // --- Step 1 ---
    protected function step_01_create_owner(): void
    {
        $ts = now()->format('YmdHis');
        $email = "workflow-test-{$ts}@oasis.com";

        $res = $this->request('POST', '/auth/login', [
            'body' => ['email' => 'admin@oasis.com', 'password' => 'password'],
            'public' => true,
        ]);

        if ($res['status'] !== 200) {
            throw new \RuntimeException('Admin login failed: ' . ($res['body']['message'] ?? 'unknown'));
        }

        $adminToken = $res['body']['token'] ?? $res['body']['data']['token'] ?? null;
        if (!$adminToken) throw new \RuntimeException('No admin token received');

        $createRes = $this->request('POST', '/users', [
            'body' => [
                'name' => 'Workflow Test Farm',
                'email' => $email,
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'Owner',
                'is_active' => true,
            ],
            'token' => $adminToken,
        ]);

        $assertions = [
            ['key' => 'status_201', 'label' => 'Returns 201', 'passed' => $createRes['status'] === 201],
        ];

        $ownerId = null;
        if ($createRes['status'] === 201) {
            $ownerId = $createRes['body']['data']['id'] ?? $createRes['body']['data']['user']['id'] ?? null;
            if ($ownerId) {
                $assertions[] = ['key' => 'has_id', 'label' => 'Has user ID', 'passed' => true];
            }
        }

        $this->state['admin_token'] = $adminToken;
        $this->state['owner_email'] = $email;
        $this->state['owner_id'] = $ownerId;
        $this->state['owner_name'] = 'Workflow Test Farm';

        $this->recordStep([
            'name' => 'Create Temp Owner',
            'method' => 'POST',
            'endpoint' => '/api/users',
            'request_body' => $this->sanitize($createRes['request_body'] ?? ['name' => 'Workflow Test Farm', 'email' => $email, 'role' => 'Owner', 'password' => '[REDACTED]'], ['password']),
            'response_status' => $createRes['status'],
            'response_body' => $createRes['body'],
            'assertions' => $assertions,
        ]);

        if (!$ownerId) {
            throw new \RuntimeException('Failed to extract owner ID from response');
        }
    }

    // --- Step 2 ---
    protected function step_02_fetch_tiers(): void
    {
        $res = $this->request('GET', '/subscription/tiers', ['public' => true]);

        $tiers = $res['body']['data'] ?? [];
        $paidTier = null;
        foreach ($tiers as $t) {
            if (floatval($t['price_monthly'] ?? 0) > 0) {
                $paidTier = $t;
                break;
            }
        }

        $assertions = [
            ['key' => 'status_200', 'label' => 'Returns 200', 'passed' => $res['status'] === 200],
            ['key' => 'has_tiers', 'label' => 'Has tier data', 'passed' => !empty($tiers)],
            ['key' => 'has_paid_tier', 'label' => 'Has paid tier available', 'passed' => $paidTier !== null],
        ];

        $this->state['tier_id'] = $paidTier['id'] ?? null;
        $this->state['tier_name'] = $paidTier['name'] ?? null;
        $this->state['tier_price'] = $paidTier['price_monthly'] ?? null;

        $this->recordStep([
            'name' => 'Fetch Subscription Tiers',
            'method' => 'GET',
            'endpoint' => '/api/subscription/tiers',
            'request_body' => null,
            'response_status' => $res['status'],
            'response_body' => ['count' => count($tiers), 'paid_tier' => $paidTier['name'] ?? 'none'],
            'assertions' => $assertions,
        ]);

        if (!$paidTier) {
            throw new \RuntimeException('No paid subscription tier found');
        }
    }

    // --- Step 3 ---
    protected function step_03_login_owner(): void
    {
        $res = $this->request('POST', '/auth/login', [
            'body' => ['email' => $this->state['owner_email'], 'password' => 'password'],
            'public' => true,
        ]);

        $token = $res['body']['token'] ?? $res['body']['data']['token'] ?? null;
        $assertions = [
            ['key' => 'status_200', 'label' => 'Returns 200', 'passed' => $res['status'] === 200],
            ['key' => 'has_token', 'label' => 'Has auth token', 'passed' => $token !== null],
        ];

        $this->state['token'] = $token;

        $this->recordStep([
            'name' => 'Login as Test Owner',
            'method' => 'POST',
            'endpoint' => '/api/auth/login',
            'request_body' => ['email' => $this->state['owner_email'], 'password' => '[REDACTED]'],
            'response_status' => $res['status'],
            'response_body' => ['id' => $res['body']['data']['id'] ?? $res['body']['user']['id'] ?? null, 'role' => $res['body']['data']['role'] ?? $res['body']['user']['role'] ?? null],
            'assertions' => $assertions,
        ]);

        if (!$token) throw new \RuntimeException('Login failed: no token');
    }

    // --- Step 4 ---
    protected function step_04_init_checkout(): void
    {
        $res = $this->request('POST', '/checkout/init', [
            'body' => [
                'tier_id' => $this->state['tier_id'],
                'billing_cycle' => 'monthly',
                'payment_method' => 'bank_transfer',
                'shipping_address' => [
                    'full_name' => 'Workflow Test Farm',
                    'phone' => '+966500000000',
                    'street' => '1 Test Street',
                    'city' => 'Riyadh',
                    'state' => '',
                    'zip' => '12345',
                    'country' => 'Saudi Arabia',
                ],
            ],
        ]);

        $orderId = $res['body']['data']['order_id'] ?? $res['body']['data']['order']['id'] ?? null;
        $assertions = [
            ['key' => 'status_200', 'label' => 'Returns 200/201', 'passed' => in_array($res['status'], [200, 201])],
            ['key' => 'has_order_id', 'label' => 'Has order ID', 'passed' => $orderId !== null],
            ['key' => 'bank_transfer', 'label' => 'Payment method is bank_transfer', 'passed' => ($res['body']['data']['payment_method'] ?? '') === 'bank_transfer'],
        ];

        $this->state['order_id'] = $orderId;

        $this->recordStep([
            'name' => 'Init Checkout (Bank Transfer)',
            'method' => 'POST',
            'endpoint' => '/api/checkout/init',
            'request_body' => $this->sanitize(['tier_id' => $this->state['tier_id'], 'billing_cycle' => 'monthly', 'payment_method' => 'bank_transfer'], []),
            'response_status' => $res['status'],
            'response_body' => $res['body']['data'] ?? $res['body'],
            'assertions' => $assertions,
        ]);

        if (!$orderId) throw new \RuntimeException('No order ID returned');
    }

    // --- Step 5 ---
    protected function step_05_upload_proof(): void
    {
        $tempPath = sys_get_temp_dir() . '/workflow-test-proof.jpg';
        file_put_contents($tempPath, base64_decode(
            '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/xAAUAQEAAAAAAAAAAAAAAAAAAAAA/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAwDAQACEQMRAD8A8AA='
        ));

        $http = Http::timeout(30)
            ->withToken($this->state['token'])
            ->withOptions(['verify' => false])
            ->attach('payment_proof', file_get_contents($tempPath), 'proof.jpg', ['Content-Type' => 'image/jpeg']);

        $url = $this->baseUrl . '/api/checkout/bank-transfer';

        try {
            $response = $http->post($url, [
                'order_id' => $this->state['order_id'],
            ]);
            $status = $response->status();
            $respBody = $response->json() ?? $response->body();
        } catch (\Exception $e) {
            @unlink($tempPath);
            throw new \RuntimeException("Bank transfer upload failed: " . $e->getMessage());
        }

        @unlink($tempPath);

        $assertions = [
            ['key' => 'status_200', 'label' => 'Returns 200/201', 'passed' => in_array($status, [200, 201])],
        ];

        $this->recordStep([
            'name' => 'Upload Payment Proof',
            'method' => 'POST',
            'endpoint' => '/api/checkout/bank-transfer',
            'request_body' => ['order_id' => $this->state['order_id'], 'payment_proof' => '(file: proof.pdf)'],
            'response_status' => $status,
            'response_body' => $respBody,
            'assertions' => $assertions,
        ]);

        if (!in_array($status, [200, 201])) {
            throw new \RuntimeException('Bank transfer upload failed with status ' . $status);
        }
    }

    // --- Step 6 ---
    protected function step_06_login_admin(): void
    {
        $res = $this->request('POST', '/auth/login', [
            'body' => ['email' => 'admin@oasis.com', 'password' => 'password'],
            'public' => true,
        ]);

        $token = $res['body']['token'] ?? $res['body']['data']['token'] ?? null;
        $assertions = [
            ['key' => 'status_200', 'label' => 'Returns 200', 'passed' => $res['status'] === 200],
            ['key' => 'has_token', 'label' => 'Has admin token', 'passed' => $token !== null],
        ];

        $this->state['admin_token'] = $token;

        $this->recordStep([
            'name' => 'Login as Admin',
            'method' => 'POST',
            'endpoint' => '/api/auth/login',
            'request_body' => ['email' => 'admin@oasis.com', 'password' => '[REDACTED]'],
            'response_status' => $res['status'],
            'response_body' => ['id' => $res['body']['data']['id'] ?? null, 'role' => $res['body']['data']['role'] ?? null],
            'assertions' => $assertions,
        ]);

        if (!$token) throw new \RuntimeException('Admin login failed');
    }

    // --- Step 7 ---
    protected function step_07_approve_payment(): void
    {
        $res = $this->request('POST', "/checkout/admin/orders/{$this->state['order_id']}/approve-payment", [
            'token' => $this->state['admin_token'],
        ]);

        $assertions = [
            ['key' => 'status_200', 'label' => 'Returns 200', 'passed' => $res['status'] === 200],
        ];

        $this->recordStep([
            'name' => 'Approve Payment',
            'method' => 'POST',
            'endpoint' => "/api/checkout/admin/orders/{$this->state['order_id']}/approve-payment",
            'request_body' => null,
            'response_status' => $res['status'],
            'response_body' => $res['body'],
            'assertions' => $assertions,
        ]);

        if ($res['status'] !== 200) {
            throw new \RuntimeException('Payment approval failed: ' . ($res['body']['message'] ?? 'unknown'));
        }
    }

    // --- Step 8 ---
    protected function step_08_login_owner_again(): void
    {
        $res = $this->request('POST', '/auth/login', [
            'body' => ['email' => $this->state['owner_email'], 'password' => 'password'],
            'public' => true,
        ]);

        $token = $res['body']['token'] ?? $res['body']['data']['token'] ?? null;
        $assertions = [
            ['key' => 'status_200', 'label' => 'Returns 200', 'passed' => $res['status'] === 200],
            ['key' => 'has_token', 'label' => 'Has auth token', 'passed' => $token !== null],
        ];

        $this->state['token'] = $token;

        $this->recordStep([
            'name' => 'Login as Test Owner (again)',
            'method' => 'POST',
            'endpoint' => '/api/auth/login',
            'request_body' => ['email' => $this->state['owner_email'], 'password' => '[REDACTED]'],
            'response_status' => $res['status'],
            'response_body' => ['role' => $res['body']['data']['role'] ?? $res['body']['user']['role'] ?? null],
            'assertions' => $assertions,
        ]);

        if (!$token) throw new \RuntimeException('Owner re-login failed');
    }

    // --- Step 9 ---
    protected function step_09_create_shepherd(): void
    {
        $ts = now()->format('YmdHis');
        $email = "workflow-shepherd-{$ts}@oasis.com";

        $res = $this->request('POST', '/users', [
            'body' => [
                'name' => 'Workflow Test Shepherd',
                'email' => $email,
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'Shepherd',
            ],
        ]);

        $shepherdId = null;
        $assertions = [
            ['key' => 'status_201', 'label' => 'Returns 201', 'passed' => $res['status'] === 201],
        ];
        if ($res['status'] === 201) {
            $shepherdId = $res['body']['data']['id'] ?? $res['body']['data']['user']['id'] ?? null;
            if ($shepherdId) {
                $assertions[] = ['key' => 'has_id', 'label' => 'Has shepherd ID', 'passed' => true];
            }
        }

        $this->state['shepherd_id'] = $shepherdId;
        $this->state['shepherd_email'] = $email;

        $this->recordStep([
            'name' => 'Create Shepherd User',
            'method' => 'POST',
            'endpoint' => '/api/users',
            'request_body' => ['name' => 'Workflow Test Shepherd', 'email' => $email, 'role' => 'Shepherd', 'password' => '[REDACTED]'],
            'response_status' => $res['status'],
            'response_body' => $res['body'],
            'assertions' => $assertions,
        ]);

        if (!$shepherdId) throw new \RuntimeException('Failed to create shepherd');
    }

    // --- Step 10 ---
    protected function step_10_create_group(): void
    {
        $res = $this->request('POST', '/animal-groups', [
            'body' => [
                'name' => 'Workflow Pasture',
                'description' => 'Auto-created by workflow test',
                'color' => '#D4AF37',
            ],
        ]);

        $groupId = null;
        $assertions = [
            ['key' => 'status_201', 'label' => 'Returns 201', 'passed' => $res['status'] === 201],
        ];
        if (in_array($res['status'], [200, 201])) {
            $groupId = $res['body']['data']['id'] ?? null;
            if ($groupId) {
                $assertions[] = ['key' => 'has_id', 'label' => 'Has group ID', 'passed' => true];
            }
        }

        $this->state['group_id'] = $groupId;

        $this->recordStep([
            'name' => 'Create Animal Group',
            'method' => 'POST',
            'endpoint' => '/api/animal-groups',
            'request_body' => ['name' => 'Workflow Pasture', 'description' => 'Auto-created by workflow test'],
            'response_status' => $res['status'],
            'response_body' => $res['body'],
            'assertions' => $assertions,
        ]);

        if (!$groupId) throw new \RuntimeException('Failed to create animal group');
    }

    // --- Step 11 ---
    protected function step_11_assign_shepherd(): void
    {
        $res = $this->request('POST', "/animal-groups/{$this->state['group_id']}/shepherds", [
            'body' => [
                'shepherd_ids' => [$this->state['shepherd_id']],
            ],
        ]);

        $assertions = [
            ['key' => 'status_200', 'label' => 'Returns 200/201', 'passed' => in_array($res['status'], [200, 201])],
        ];

        $this->recordStep([
            'name' => 'Assign Shepherd to Group',
            'method' => 'POST',
            'endpoint' => "/api/animal-groups/{$this->state['group_id']}/shepherds",
            'request_body' => ['shepherd_ids' => [$this->state['shepherd_id']]],
            'response_status' => $res['status'],
            'response_body' => $res['body'],
            'assertions' => $assertions,
        ]);

        if (!in_array($res['status'], [200, 201])) {
            throw new \RuntimeException('Failed to assign shepherd to group');
        }
    }

    // --- Step 12 ---
    protected function step_12_register_device(): void
    {
        $res = $this->request('POST', '/devices', [
            'body' => [
                'name' => 'Workflow Collar',
                'type' => 'collar',
                'status' => 'offline',
                'battery_level' => 100,
                'firmware_version' => 'v2.4',
                'update_interval' => 15,
            ],
        ]);

        $deviceId = null;
        $deviceIdentifier = null;
        $assertions = [
            ['key' => 'status_201', 'label' => 'Returns 201', 'passed' => $res['status'] === 201],
        ];
        if (in_array($res['status'], [200, 201])) {
            $data = $res['body']['data'] ?? $res['body'];
            $deviceId = $data['id'] ?? null;
            $deviceIdentifier = $data['device_id'] ?? $data['devEui'] ?? null;
            if ($deviceId) {
                $assertions[] = ['key' => 'has_id', 'label' => 'Has device ID', 'passed' => true];
            }
        }

        $this->state['device_id'] = $deviceId;
        $this->state['device_identifier'] = $deviceIdentifier;

        $this->recordStep([
            'name' => 'Register Device',
            'method' => 'POST',
            'endpoint' => '/api/devices',
            'request_body' => ['name' => 'Workflow Collar', 'type' => 'collar'],
            'response_status' => $res['status'],
            'response_body' => $res['body'],
            'assertions' => $assertions,
        ]);

        if (!$deviceId) throw new \RuntimeException('Failed to create device');
    }

    // --- Step 13 ---
    protected function step_13_create_animal_and_activate(): void
    {
        // Create animal with device attached
        $animalRes = $this->request('POST', '/animals', [
            'body' => [
                'name' => 'Workflow Camel',
                'species' => 'Camel',
                'gender' => 'Male',
                'breed' => 'Arabian',
                'date_of_birth' => '2024-01-15',
                'current_weight' => 450,
                'device_id' => (string) $this->state['device_id'],
            ],
        ]);

        $animalId = null;
        $assertions = [
            ['key' => 'create_status', 'label' => 'Animal created (201)', 'passed' => $animalRes['status'] === 201],
        ];
        if ($animalRes['status'] === 201) {
            $animalId = $animalRes['body']['data']['id'] ?? $animalRes['body']['data']['animal']['id'] ?? null;
            if ($animalId) {
                $assertions[] = ['key' => 'has_animal_id', 'label' => 'Has animal ID', 'passed' => true];
                $this->state['animal_id'] = $animalId;
            }
        }

        // Activate device with subscription
        $activateRes = null;
        $activateAssertions = [];
        if ($animalId) {
            $activateRes = $this->request('POST', '/checkout/activate-device', [
                'body' => [
                    'device_id' => $this->state['device_identifier'] ?? $this->state['device_id'],
                ],
            ]);
            $activateAssertions[] = [
                'key' => 'activate_status',
                'label' => 'Device activated (200)',
                'passed' => $activateRes['status'] === 200,
            ];
        }

        $this->recordStep([
            'name' => 'Create Animal & Activate Device',
            'method' => 'POST',
            'endpoint' => '/api/animals + /api/checkout/activate-device',
            'request_body' => ['animal' => ['name' => 'Workflow Camel', 'species' => 'Camel'], 'activate' => ['device_id' => $this->state['device_identifier'] ?? $this->state['device_id']]],
            'response_status' => $animalRes['status'],
            'response_body' => [
                'animal' => $animalRes['body'],
                'activation' => $activateRes['body'] ?? null,
            ],
            'assertions' => array_merge($assertions, $activateAssertions),
        ]);

        if ($animalRes['status'] !== 201) {
            throw new \RuntimeException('Failed to create animal');
        }
        if ($activateRes && $activateRes['status'] !== 200) {
            throw new \RuntimeException('Device activation failed: ' . ($activateRes['body']['message'] ?? 'unknown'));
        }
    }

    // --- Step 14 ---
    protected function step_14_simulate_move(): void
    {
        $res = $this->request('POST', '/simulator/move', [
            'body' => [
                'device_id' => $this->state['device_id'],
                'latitude' => 24.7136,
                'longitude' => 46.6753,
                'speed' => 5,
                'temperature' => 37.5,
                'battery_drain' => 2,
            ],
        ]);

        $assertions = [
            ['key' => 'status_200', 'label' => 'Returns 200', 'passed' => $res['status'] === 200],
            ['key' => 'location_recorded', 'label' => 'Location recorded', 'passed' => ($res['body']['message'] ?? '') === 'Location recorded'],
            ['key' => 'battery_updated', 'label' => 'Battery updated', 'passed' => isset($res['body']['battery_level'])],
        ];

        $this->state['move_lat'] = 24.7136;
        $this->state['move_lng'] = 46.6753;

        $this->recordStep([
            'name' => 'Simulate Animal Movement',
            'method' => 'POST',
            'endpoint' => '/api/simulator/move',
            'request_body' => ['device_id' => $this->state['device_id'], 'latitude' => 24.7136, 'longitude' => 46.6753, 'speed' => 5],
            'response_status' => $res['status'],
            'response_body' => $res['body'],
            'assertions' => $assertions,
        ]);

        if ($res['status'] !== 200) {
            throw new \RuntimeException('Simulator move failed: ' . ($res['body']['message'] ?? 'unknown'));
        }
    }

    // --- Step 15 ---
    protected function step_15_verify_location(): void
    {
        $res = $this->request('GET', "/devices/{$this->state['device_id']}");

        $device = $res['body']['data'] ?? $res['body'];
        $lat = $device['gps_lat'] ?? null;
        $lng = $device['gps_lng'] ?? null;

        $assertions = [
            ['key' => 'status_200', 'label' => 'Returns 200', 'passed' => $res['status'] === 200],
            ['key' => 'has_gps_lat', 'label' => 'Has GPS latitude', 'passed' => $lat !== null],
            ['key' => 'has_gps_lng', 'label' => 'Has GPS longitude', 'passed' => $lng !== null],
            ['key' => 'lat_matches', 'label' => 'Latitude matches move (' . ($this->state['move_lat'] ?? '?') . ')', 'passed' => abs(floatval($lat) - floatval($this->state['move_lat'] ?? 0)) < 0.001],
            ['key' => 'lng_matches', 'label' => 'Longitude matches move (' . ($this->state['move_lng'] ?? '?') . ')', 'passed' => abs(floatval($lng) - floatval($this->state['move_lng'] ?? 0)) < 0.001],
        ];

        $this->recordStep([
            'name' => 'Verify GPS Location Updated',
            'method' => 'GET',
            'endpoint' => "/api/devices/{$this->state['device_id']}",
            'request_body' => null,
            'response_status' => $res['status'],
            'response_body' => ['gps_lat' => $lat, 'gps_lng' => $lng],
            'assertions' => $assertions,
        ]);

        if ($lat === null || $lng === null) {
            throw new \RuntimeException('Device has no GPS coordinates after simulator move');
        }
    }

    // --- Step 16 ---
    protected function step_16_create_medical_record(): void
    {
        $res = $this->request('POST', '/medical-records', [
            'body' => [
                'animal_id' => $this->state['animal_id'],
                'record_type' => 'checkup',
                'title' => 'Workflow Health Check',
                'description' => 'Routine checkup for workflow camel',
                'record_date' => now()->format('Y-m-d'),
                'veterinarian' => 'Dr. Test',
                'health_status' => 'stable',
            ],
        ]);

        $recordId = null;
        $assertions = [
            ['key' => 'status_201', 'label' => 'Returns 201', 'passed' => $res['status'] === 201],
        ];
        if ($res['status'] === 201) {
            $recordId = $res['body']['data']['id'] ?? null;
            if ($recordId) {
                $assertions[] = ['key' => 'has_record_id', 'label' => 'Has record ID', 'passed' => true];
            }
        }

        $this->state['medical_record_id'] = $recordId;

        $this->recordStep([
            'name' => 'Create Medical Record',
            'method' => 'POST',
            'endpoint' => '/api/medical-records',
            'request_body' => ['animal_id' => $this->state['animal_id'], 'record_type' => 'checkup', 'title' => 'Workflow Health Check'],
            'response_status' => $res['status'],
            'response_body' => $res['body'],
            'assertions' => $assertions,
        ]);

        if ($res['status'] !== 201) {
            throw new \RuntimeException('Failed to create medical record: ' . ($res['body']['message'] ?? 'unknown'));
        }
    }

    // --- Step 17 ---
    protected function step_17_create_task(): void
    {
        $res = $this->request('POST', '/tasks', [
            'body' => [
                'title' => 'Workflow Task: Check Camel',
                'description' => 'Check the workflow camel at pasture',
                'assigned_to' => $this->state['shepherd_id'],
                'animal_id' => $this->state['animal_id'],
                'priority' => 'high',
                'task_type' => 'inspection',
                'due_date' => now()->addDays(1)->format('Y-m-d'),
            ],
        ]);

        $taskId = null;
        $assertions = [
            ['key' => 'status_201', 'label' => 'Returns 201', 'passed' => $res['status'] === 201],
        ];
        if ($res['status'] === 201) {
            $data = $res['body']['data'] ?? $res['body'];
            $taskId = $data['id'] ?? null;
            if ($taskId) {
                $assertions[] = ['key' => 'has_task_id', 'label' => 'Has task ID', 'passed' => true];
            }
        }

        $this->state['task_id'] = $taskId;

        $this->recordStep([
            'name' => 'Create Task for Shepherd',
            'method' => 'POST',
            'endpoint' => '/api/tasks',
            'request_body' => ['title' => 'Workflow Task: Check Camel', 'assigned_to' => $this->state['shepherd_id'], 'priority' => 'high'],
            'response_status' => $res['status'],
            'response_body' => $res['body'],
            'assertions' => $assertions,
        ]);

        if ($res['status'] !== 201) {
            throw new \RuntimeException('Failed to create task: ' . ($res['body']['message'] ?? 'unknown'));
        }
    }

    // --- Step 18 ---
    protected function step_18_create_conversation(): void
    {
        $res = $this->request('POST', '/conversations', [
            'body' => [
                'type' => 'direct',
                'subject' => 'Workflow Camel Checkup',
                'body' => 'Please check the workflow camel and report back',
                'participant_ids' => [$this->state['shepherd_id']],
            ],
        ]);

        $convId = null;
        $assertions = [
            ['key' => 'status_201', 'label' => 'Returns 201', 'passed' => $res['status'] === 201],
        ];
        if ($res['status'] === 201) {
            $data = $res['body']['data'] ?? $res['body'];
            $convId = $data['id'] ?? $data['conversation']['id'] ?? null;
            if ($convId) {
                $assertions[] = ['key' => 'has_conv_id', 'label' => 'Has conversation ID', 'passed' => true];
            }
        }

        $this->state['conversation_id'] = $convId;

        $this->recordStep([
            'name' => 'Create Conversation & Send Message',
            'method' => 'POST',
            'endpoint' => '/api/conversations',
            'request_body' => ['type' => 'direct', 'subject' => 'Workflow Camel Checkup', 'participant_ids' => [$this->state['shepherd_id']]],
            'response_status' => $res['status'],
            'response_body' => $res['body'],
            'assertions' => $assertions,
        ]);

        if ($res['status'] !== 201) {
            throw new \RuntimeException('Failed to create conversation: ' . ($res['body']['message'] ?? 'unknown'));
        }
    }

    // --- Step 19 ---
    protected function step_19_login_shepherd(): void
    {
        $res = $this->request('POST', '/auth/login', [
            'body' => [
                'email' => $this->state['shepherd_email'],
                'password' => 'password',
            ],
            'public' => true,
        ]);

        $token = $res['body']['token'] ?? $res['body']['data']['token'] ?? null;
        $assertions = [
            ['key' => 'status_200', 'label' => 'Returns 200', 'passed' => $res['status'] === 200],
            ['key' => 'has_token', 'label' => 'Has auth token', 'passed' => $token !== null],
        ];

        $this->state['shepherd_token'] = $token;

        $this->recordStep([
            'name' => 'Login as Shepherd',
            'method' => 'POST',
            'endpoint' => '/api/auth/login',
            'request_body' => ['email' => $this->state['shepherd_email'], 'password' => '[REDACTED]'],
            'response_status' => $res['status'],
            'response_body' => ['role' => $res['body']['data']['role'] ?? $res['body']['user']['role'] ?? null],
            'assertions' => $assertions,
        ]);

        if (!$token) throw new \RuntimeException('Shepherd login failed');
    }

    // --- Step 20 ---
    protected function step_20_complete_task(): void
    {
        $res = $this->request('POST', "/tasks/{$this->state['task_id']}/complete", [
            'token' => $this->state['shepherd_token'],
        ]);

        $assertions = [
            ['key' => 'status_200', 'label' => 'Returns 200', 'passed' => $res['status'] === 200],
        ];

        $this->recordStep([
            'name' => 'Shepherd Completes Task',
            'method' => 'POST',
            'endpoint' => "/api/tasks/{$this->state['task_id']}/complete",
            'request_body' => null,
            'response_status' => $res['status'],
            'response_body' => $res['body'],
            'assertions' => $assertions,
        ]);

        if ($res['status'] !== 200) {
            throw new \RuntimeException('Task completion failed: ' . ($res['body']['message'] ?? 'unknown'));
        }
    }

    // --- Step 21 ---
    protected function step_21_reply_conversation(): void
    {
        $res = $this->request('POST', "/conversations/{$this->state['conversation_id']}/messages", [
            'body' => [
                'body' => 'Camel checked, all good. Task completed.',
            ],
            'token' => $this->state['shepherd_token'],
        ]);

        $assertions = [
            ['key' => 'status_201', 'label' => 'Returns 201', 'passed' => $res['status'] === 201],
        ];

        $this->recordStep([
            'name' => 'Shepherd Replies in Conversation',
            'method' => 'POST',
            'endpoint' => "/api/conversations/{$this->state['conversation_id']}/messages",
            'request_body' => ['body' => 'Camel checked, all good. Task completed.'],
            'response_status' => $res['status'],
            'response_body' => $res['body'],
            'assertions' => $assertions,
        ]);

        if ($res['status'] !== 201) {
            throw new \RuntimeException('Reply failed: ' . ($res['body']['message'] ?? 'unknown'));
        }
    }

    // --- Step 22 ---
    protected function step_22_create_geofence(): void
    {
        $polygon = '[[24.7000,46.6600],[24.7000,46.6900],[24.7300,46.6900],[24.7300,46.6600],[24.7000,46.6600]]';
        $this->state['geofence_polygon'] = $polygon;

        $res = $this->request('POST', '/geofences', [
            'body' => [
                'name' => 'Workflow Test Pasture',
                'coordinates' => $polygon,
                'color' => '#00FF00',
                'alert_type' => 'both',
            ],
        ]);

        $geofenceId = null;
        $assertions = [
            ['key' => 'status_201', 'label' => 'Returns 201', 'passed' => $res['status'] === 201],
        ];
        if (in_array($res['status'], [200, 201])) {
            $geofenceId = $res['body']['data']['id'] ?? $res['body']['geofence']['id'] ?? $res['body']['id'] ?? null;
            if ($geofenceId) {
                $assertions[] = ['key' => 'has_geofence_id', 'label' => 'Has geofence ID', 'passed' => true];
            }
        }

        $this->state['geofence_id'] = $geofenceId;

        $this->recordStep([
            'name' => 'Create Geofence',
            'method' => 'POST',
            'endpoint' => '/api/geofences',
            'request_body' => ['name' => 'Workflow Test Pasture', 'coordinates' => '(polygon)'],
            'response_status' => $res['status'],
            'response_body' => $res['body'],
            'assertions' => $assertions,
        ]);

        if (!$geofenceId) {
            throw new \RuntimeException('Failed to create geofence: ' . ($res['body']['message'] ?? 'unknown'));
        }
    }

    // --- Step 23 ---
    protected function step_23_move_outside(): void
    {
        $res = $this->request('POST', '/simulator/move', [
            'body' => [
                'device_id' => $this->state['device_id'],
                'latitude' => 24.6500,
                'longitude' => 46.6500,
                'speed' => 0,
            ],
        ]);

        $assertions = [
            ['key' => 'status_200', 'label' => 'Returns 200', 'passed' => $res['status'] === 200],
        ];

        $this->recordStep([
            'name' => 'Move Outside Geofence (init cache)',
            'method' => 'POST',
            'endpoint' => '/api/simulator/move',
            'request_body' => ['device_id' => $this->state['device_id'], 'latitude' => 24.6500, 'longitude' => 46.6500],
            'response_status' => $res['status'],
            'response_body' => $res['body'],
            'assertions' => $assertions,
        ]);

        if ($res['status'] !== 200) {
            throw new \RuntimeException('Outside move failed: ' . ($res['body']['message'] ?? 'unknown'));
        }
    }

    // --- Step 24 ---
    protected function step_24_move_inside_entry(): void
    {
        $res = $this->request('POST', '/simulator/move', [
            'body' => [
                'device_id' => $this->state['device_id'],
                'latitude' => 24.7136,
                'longitude' => 46.6753,
                'speed' => 5,
            ],
        ]);

        $alertTriggered = $res['body']['alert_triggered'] ?? false;
        $assertions = [
            ['key' => 'status_200', 'label' => 'Returns 200', 'passed' => $res['status'] === 200],
            ['key' => 'alert_triggered', 'label' => 'Entry alert triggered', 'passed' => $alertTriggered === true],
        ];

        $this->recordStep([
            'name' => 'Move Inside Geofence (entry alert)',
            'method' => 'POST',
            'endpoint' => '/api/simulator/move',
            'request_body' => ['device_id' => $this->state['device_id'], 'latitude' => 24.7136, 'longitude' => 46.6753],
            'response_status' => $res['status'],
            'response_body' => $res['body'],
            'assertions' => $assertions,
        ]);

        if ($res['status'] !== 200) {
            throw new \RuntimeException('Inside move failed: ' . ($res['body']['message'] ?? 'unknown'));
        }
    }

    // --- Step 25 ---
    protected function step_25_set_temperature(): void
    {
        $res = $this->request('POST', '/simulator/set-temperature', [
            'body' => [
                'device_id' => $this->state['device_id'],
                'temperature' => 41.0,
            ],
        ]);

        $temp = $res['body']['temperature'] ?? null;
        $assertions = [
            ['key' => 'status_200', 'label' => 'Returns 200', 'passed' => $res['status'] === 200],
            ['key' => 'temp_set', 'label' => 'Temperature set to 41°C', 'passed' => $temp === 41.0 || abs(floatval($temp) - 41.0) < 0.1],
        ];

        $this->state['set_temperature'] = 41.0;

        $this->recordStep([
            'name' => 'Set Temperature (41°C)',
            'method' => 'POST',
            'endpoint' => '/api/simulator/set-temperature',
            'request_body' => ['device_id' => $this->state['device_id'], 'temperature' => 41.0],
            'response_status' => $res['status'],
            'response_body' => $res['body'],
            'assertions' => $assertions,
        ]);

        if ($res['status'] !== 200) {
            throw new \RuntimeException('Set temperature failed: ' . ($res['body']['message'] ?? 'unknown'));
        }
    }

    // --- Step 26 ---
    protected function step_26_toggle_lost(): void
    {
        $res = $this->request('POST', '/simulator/toggle-lost', [
            'body' => [
                'device_id' => $this->state['device_id'],
                'is_lost' => true,
            ],
        ]);

        $isLost = $res['body']['is_lost'] ?? false;
        $assertions = [
            ['key' => 'status_200', 'label' => 'Returns 200', 'passed' => $res['status'] === 200],
            ['key' => 'is_lost', 'label' => 'Animal marked as lost', 'passed' => $isLost === true],
        ];

        $this->recordStep([
            'name' => 'Toggle Lost Animal',
            'method' => 'POST',
            'endpoint' => '/api/simulator/toggle-lost',
            'request_body' => ['device_id' => $this->state['device_id'], 'is_lost' => true],
            'response_status' => $res['status'],
            'response_body' => $res['body'],
            'assertions' => $assertions,
        ]);

        if ($res['status'] !== 200 || !$isLost) {
            throw new \RuntimeException('Toggle lost failed: ' . ($res['body']['message'] ?? 'unknown'));
        }
    }

    // --- Step 27 ---
    protected function step_27_move_outside_exit(): void
    {
        $res = $this->request('POST', '/simulator/move', [
            'body' => [
                'device_id' => $this->state['device_id'],
                'latitude' => 24.6500,
                'longitude' => 46.6500,
                'speed' => 0,
            ],
        ]);

        $alertTriggered = $res['body']['alert_triggered'] ?? false;
        $assertions = [
            ['key' => 'status_200', 'label' => 'Returns 200', 'passed' => $res['status'] === 200],
            ['key' => 'exit_alert', 'label' => 'Exit alert triggered', 'passed' => $alertTriggered === true],
        ];

        $this->recordStep([
            'name' => 'Move Outside Geofence (exit alert)',
            'method' => 'POST',
            'endpoint' => '/api/simulator/move',
            'request_body' => ['device_id' => $this->state['device_id'], 'latitude' => 24.6500, 'longitude' => 46.6500],
            'response_status' => $res['status'],
            'response_body' => $res['body'],
            'assertions' => $assertions,
        ]);

        if ($res['status'] !== 200) {
            throw new \RuntimeException('Exit move failed: ' . ($res['body']['message'] ?? 'unknown'));
        }
    }

    // --- Step 28 ---
    protected function step_28_verify_alerts(): void
    {
        // Fetch alerts list
        $alertsRes = $this->request('GET', '/geofence-alerts');

        // Fetch device to check temperature + lost status
        $deviceRes = $this->request('GET', "/devices/{$this->state['device_id']}");

        $alerts = $alertsRes['body']['data'] ?? [];
        $device = $deviceRes['body']['data'] ?? $deviceRes['body'];

        $hasEntry = false;
        $hasExit = false;
        $hasLost = false;
        $entryAlertId = null;

        foreach ($alerts as $a) {
            $type = $a['type'] ?? '';
            if ($type === 'entry') { $hasEntry = true; $entryAlertId = $a['id'] ?? null; }
            if ($type === 'exit') { $hasExit = true; }
            if ($type === 'lost') { $hasLost = true; }
        }

        $deviceTemp = $device['temperature'] ?? null;
        $deviceLost = $device['is_lost'] ?? null;

        $assertions = [
            ['key' => 'alerts_200', 'label' => 'Alerts endpoint returns 200', 'passed' => $alertsRes['status'] === 200],
            ['key' => 'device_200', 'label' => 'Device endpoint returns 200', 'passed' => $deviceRes['status'] === 200],
            ['key' => 'has_entry', 'label' => 'Entry alert exists', 'passed' => $hasEntry],
            ['key' => 'has_exit', 'label' => 'Exit alert exists', 'passed' => $hasExit],
            ['key' => 'has_lost', 'label' => 'Lost alert exists', 'passed' => $hasLost],
            ['key' => 'temp_41', 'label' => 'Temperature is 41°C', 'passed' => $deviceTemp !== null && abs(floatval($deviceTemp) - 41.0) < 0.1],
            ['key' => 'is_lost', 'label' => 'Device is_lost is true', 'passed' => $deviceLost == true || $deviceLost === 1],
        ];

        $this->state['entry_alert_id'] = $entryAlertId;

        $this->recordStep([
            'name' => 'Verify All Alerts & Status',
            'method' => 'GET',
            'endpoint' => '/api/geofence-alerts + /api/devices/{id}',
            'request_body' => null,
            'response_status' => 200,
            'response_body' => [
                'alerts_count' => count($alerts),
                'has_entry' => $hasEntry,
                'has_exit' => $hasExit,
                'has_lost' => $hasLost,
                'temperature' => $deviceTemp,
                'is_lost' => $deviceLost,
            ],
            'assertions' => $assertions,
        ]);

        if (!$hasEntry || !$hasExit || !$hasLost) {
            $missing = [];
            if (!$hasEntry) $missing[] = 'entry';
            if (!$hasExit) $missing[] = 'exit';
            if (!$hasLost) $missing[] = 'lost';
            throw new \RuntimeException('Missing alerts: ' . implode(', ', $missing));
        }
    }

    // --- Step 29 ---
    protected function step_29_acknowledge_alert(): void
    {
        $alertId = $this->state['entry_alert_id'];
        if (!$alertId) {
            throw new \RuntimeException('No entry alert ID available to acknowledge');
        }

        $res = $this->request('PATCH', "/geofence-alerts/{$alertId}/acknowledge");

        $assertions = [
            ['key' => 'status_200', 'label' => 'Returns 200', 'passed' => $res['status'] === 200],
        ];

        $this->recordStep([
            'name' => 'Acknowledge Entry Alert',
            'method' => 'PATCH',
            'endpoint' => "/api/geofence-alerts/{$alertId}/acknowledge",
            'request_body' => null,
            'response_status' => $res['status'],
            'response_body' => $res['body'],
            'assertions' => $assertions,
        ]);

        if ($res['status'] !== 200) {
            throw new \RuntimeException('Acknowledge failed: ' . ($res['body']['message'] ?? 'unknown'));
        }
    }

    // --- Step 30 ---
    protected function step_30_unmark_lost(): void
    {
        $res = $this->request('POST', '/simulator/toggle-lost', [
            'body' => [
                'device_id' => $this->state['device_id'],
                'is_lost' => false,
            ],
        ]);

        $isLost = $res['body']['is_lost'] ?? true;
        $assertions = [
            ['key' => 'status_200', 'label' => 'Returns 200', 'passed' => $res['status'] === 200],
            ['key' => 'unmarked', 'label' => 'Animal unmarked as lost', 'passed' => $isLost === false],
        ];

        $this->recordStep([
            'name' => 'Unmark Lost Animal',
            'method' => 'POST',
            'endpoint' => '/api/simulator/toggle-lost',
            'request_body' => ['device_id' => $this->state['device_id'], 'is_lost' => false],
            'response_status' => $res['status'],
            'response_body' => $res['body'],
            'assertions' => $assertions,
        ]);

        if ($res['status'] !== 200) {
            throw new \RuntimeException('Unmark lost failed: ' . ($res['body']['message'] ?? 'unknown'));
        }
    }
}
