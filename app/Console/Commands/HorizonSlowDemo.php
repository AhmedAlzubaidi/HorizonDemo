<?php

namespace App\Console\Commands;

use App\Jobs\TimeConsumingJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class HorizonSlowDemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dispatch:single-process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);
        $this->info('Dispatching jobs to single process queue...');

        $jobIds = [];
        for ($i = 0; $i < 100; $i++) {
            $jobId = uniqid();
            $jobIds[] = $jobId;
            TimeConsumingJob::dispatch($jobId)->onQueue('single_process');
            $this->info("Dispatched job {$jobId} to single process queue.");
            Cache::put("job_status_{$jobId}", 'pending', 600);
        }

        $this->info('All jobs dispatched. Waiting for completion...');

        while (true) {
            $completedJobs = 0;

            foreach ($jobIds as $jobId) {
                if (Cache::get("job_status_{$jobId}") === 'completed') {
                    $completedJobs++;
                }
            }

            $this->info("Completed jobs: {$completedJobs}/100");

            if ($completedJobs === 100) {
                break;
            }

            sleep(1);
        }

        $endTime = microtime(true);
        $this->info("All jobs completed in " . round($endTime - $startTime, 2) . " seconds.");
    }
}
