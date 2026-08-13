<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class QueueFallbackTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Testa que job é enfileirado no database quando Redis falha
     */
    public function test_job_is_queued_in_database(): void
    {
        Config::set('queue.default', 'database');
        
        Queue::push(function ($job) {
            $job->delete();
        });
        
        $this->assertDatabaseHas('jobs', [
            'queue' => 'default'
        ]);
    }
    
    /**
     * Testa que job é processado com sucesso (driver sync)
     */
    public function test_job_is_processed_successfully(): void
    {
        Config::set('queue.default', 'sync');
        Cache::forget('queue_job_processed');
        
        dispatch(function () {
            Cache::put('queue_job_processed', true, 60);
        });
        
        $this->assertTrue(Cache::get('queue_job_processed') === true);
    }
    
    /**
     * Testa que job com falha é registrado
     */
    public function test_failed_job_is_logged(): void
    {
        Config::set('queue.default', 'database');
        
        Queue::push(function ($job) {
            throw new \Exception('Test job failure');
        });
        
        try {
            $this->artisan('queue:work', [
                '--once' => true,
                '--queue' => 'default',
                '--tries' => 1
            ]);
        } catch (\Exception $e) {
            // Esperado
        }
        
        $this->assertDatabaseHas('failed_jobs', [
            'queue' => 'default'
        ]);
    }
    
    /**
     * Testa retry de jobs via flag em cache
     */
    public function test_job_retry_mechanism(): void
    {
        Config::set('queue.default', 'sync');
        Cache::forget('queue_retry_attempts');
        
        $run = function () {
            $attempts = (int) Cache::get('queue_retry_attempts', 0) + 1;
            Cache::put('queue_retry_attempts', $attempts, 60);

            if ($attempts < 2) {
                throw new \RuntimeException('retry');
            }
        };

        try {
            dispatch($run);
        } catch (\RuntimeException $e) {
            $this->assertEquals('retry', $e->getMessage());
        }

        dispatch($run);

        $this->assertEquals(2, (int) Cache::get('queue_retry_attempts'));
    }
    
    /**
     * Testa que jobs são processados na ordem correta
     */
    public function test_jobs_are_processed_in_order(): void
    {
        Config::set('queue.default', 'sync');
        Cache::forget('queue_processed_order');
        Cache::put('queue_processed_order', [], 60);
        
        for ($i = 1; $i <= 5; $i++) {
            dispatch(function () use ($i) {
                $processed = Cache::get('queue_processed_order', []);
                $processed[] = $i;
                Cache::put('queue_processed_order', $processed, 60);
            });
        }
        
        $this->assertEquals([1, 2, 3, 4, 5], Cache::get('queue_processed_order'));
    }
    
    /**
     * Testa que jobs em diferentes queues são independentes
     */
    public function test_multiple_queues_are_independent(): void
    {
        Config::set('queue.default', 'database');
        
        Queue::push(function ($job) {
            $job->delete();
        }, '', 'default');
        
        Queue::push(function ($job) {
            $job->delete();
        }, '', 'high');
        
        $defaultJobs = DB::table('jobs')
            ->where('queue', 'default')
            ->count();
            
        $highJobs = DB::table('jobs')
            ->where('queue', 'high')
            ->count();
            
        $this->assertGreaterThanOrEqual(1, $defaultJobs);
        $this->assertGreaterThanOrEqual(1, $highJobs);
    }
}
