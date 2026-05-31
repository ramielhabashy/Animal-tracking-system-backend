<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkflowTestRun;
use App\Services\WorkflowTestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowTestController extends Controller
{
    public function __construct(
        protected WorkflowTestService $workflowTestService
    ) {}

    public function run(): JsonResponse
    {
        $run = WorkflowTestRun::create([
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $result = $this->workflowTestService->run();

            $run->update([
                'status' => 'completed',
                'results' => $result['results'],
                'summary' => $result['summary'],
                'completed_at' => now(),
            ]);

            return response()->json([
                'data' => [
                    'id' => $run->id,
                    'summary' => $result['summary'],
                    'results' => $result['results'],
                ],
            ]);
        } catch (\Exception $e) {
            $run->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);

            return response()->json([
                'message' => 'Workflow test failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function latest(): JsonResponse
    {
        $run = WorkflowTestRun::latest()->first();

        if (!$run) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => $run->toArray(),
        ]);
    }

    public function index(): JsonResponse
    {
        $runs = WorkflowTestRun::latest()->take(10)->get();

        return response()->json([
            'data' => $runs->toArray(),
        ]);
    }
}
