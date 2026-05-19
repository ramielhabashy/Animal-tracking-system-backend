<?php

namespace App\Console\Commands;

use App\Models\WorkflowTestRun;
use App\Services\WorkflowTestService;
use Illuminate\Console\Command;

class WorkflowTestCommand extends Command
{
    protected $signature = 'workflow:test';
    protected $description = 'Run the full business workflow test';

    public function handle(WorkflowTestService $service): int
    {
        $this->info('Starting workflow test...');

        $run = WorkflowTestRun::create([
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $result = $service->run();

            $run->update([
                'status' => 'completed',
                'results' => $result['results'],
                'summary' => $result['summary'],
                'completed_at' => now(),
            ]);

            $summary = $result['summary'];
            $this->newLine();
            $this->line(str_repeat('=', 60));
            $this->info('  WORKFLOW TEST COMPLETE');
            $this->line(str_repeat('=', 60));

            foreach ($result['results'] as $step) {
                $icon = match ($step['status']) {
                    'passed' => "\xE2\x9C\x85",
                    'failed' => "\xE2\x9D\x8C",
                    'skipped' => "\xE2\xAD\x90",
                    default => "\xE2\x9D\x93",
                };
                $this->line("  {$icon} Step {$step['step']}: {$step['name']} ({$step['duration_ms']}ms)");
                if ($step['error']) {
                    $this->error("     Error: {$step['error']}");
                }
            }

            $this->newLine();
            $this->line(str_repeat('-', 60));
            $this->line("  Total: {$summary['total']} | Passed: {$summary['passed']} | Failed: {$summary['failed']} | Skipped: {$summary['skipped']}");
            $this->line("  Duration: {$summary['duration_ms']}ms");
            $this->line(str_repeat('-', 60));

            return $summary['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
        } catch (\Exception $e) {
            $run->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);

            $this->error("Workflow test failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
