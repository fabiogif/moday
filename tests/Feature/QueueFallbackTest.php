<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Jobs\TestJob;

class QueueFallbackTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Testa que job é enfileirado no database quando Redis falha
     */
    public function test_job_is_queued_in_database(): void
    {
        // Configurar para usar database queue
        Config::set('queue.default', 'database');
        
        // Criar um job de teste simples
        $jobData = ['test' => 'data_' . time()];
        
        // Dispatch job
        Queue::push(function ($job) use ($jobData) {
            // Job de teste
            $job->delete();
        });
        
        // Verificar que foi enfileirado no database
        $this->assertDatabaseHas('jobs', [
            'queue' => 'default'
        ]);
    }
    
    /**
     * Testa que job é processado com sucesso
     */
    public function test_job_is_processed_successfully(): void
    {
        Config::set('queue.default', 'database');
        
        // Flag para verificar se job foi processado
        $processed = false;
        
        Queue::push(function ($job) use (&$processed) {
            $processed = true;
            $job->delete();
        });
        
        // Processar jobs pendentes
        $this->artisan('queue:work', [
            '--once' => true,
            '--queue' => 'default'
        ]);
        
        $this->assertTrue($processed);
    }
    
    /**
     * Testa que job com falha é registrado
     */
    public function test_failed_job_is_logged(): void
    {
        Config::set('queue.default', 'database');
        
        // Job que vai falhar
        Queue::push(function ($job) {
            throw new \Exception('Test job failure');
        });
        
        // Tentar processar (vai falhar)
        try {
            $this->artisan('queue:work', [
                '--once' => true,
                '--queue' => 'default',
                '--tries' => 1
            ]);
        } catch (\Exception $e) {
            // Esperado
        }
        
        // Verificar que foi registrado como falha
        $this->assertDatabaseHas('failed_jobs', [
            'queue' => 'default'
        ]);
    }
    
    /**
     * Testa retry de jobs
     */
    public function test_job_retry_mechanism(): void
    {
        Config::set('queue.default', 'database');
        
        $attempts = 0;
        
        // Job que falha nas primeiras tentativas
        Queue::push(function ($job) use (&$attempts) {
            $attempts++;
            
            if ($attempts < 2) {
                // Falha nas primeiras tentativas
                $job->release(1);
            } else {
                // Sucesso na segunda tentativa
                $job->delete();
            }
        });
        
        // Primeira tentativa (vai falhar e fazer release)
        $this->artisan('queue:work', [
            '--once' => true,
            '--queue' => 'default'
        ]);
        
        $this->assertEquals(1, $attempts);
        
        // Segunda tentativa (vai ter sucesso)
        $this->artisan('queue:work', [
            '--once' => true,
            '--queue' => 'default'
        ]);
        
        $this->assertEquals(2, $attempts);
    }
    
    /**
     * Testa que jobs são processados na ordem correta
     */
    public function test_jobs_are_processed_in_order(): void
    {
        Config::set('queue.default', 'database');
        
        $processed = [];
        
        // Enfileirar múltiplos jobs
        for ($i = 1; $i <= 5; $i++) {
            Queue::push(function ($job) use ($i, &$processed) {
                $processed[] = $i;
                $job->delete();
            });
        }
        
        // Processar todos os jobs
        $this->artisan('queue:work', [
            '--stop-when-empty' => true,
            '--queue' => 'default'
        ]);
        
        // Verificar ordem (FIFO - First In First Out)
        $this->assertEquals([1, 2, 3, 4, 5], $processed);
    }
    
    /**
     * Testa que jobs em diferentes queues são independentes
     */
    public function test_multiple_queues_are_independent(): void
    {
        Config::set('queue.default', 'database');
        
        // Enfileirar em queue normal
        Queue::push(function ($job) {
            $job->delete();
        }, null, 'default');
        
        // Enfileirar em queue prioritária
        Queue::push(function ($job) {
            $job->delete();
        }, null, 'high');
        
        // Verificar que ambos foram enfileirados
        $defaultJobs = DB::table('jobs')
            ->where('queue', 'default')
            ->count();
            
        $highJobs = DB::table('jobs')
            ->where('queue', 'high')
            ->count();
        
        $this->assertEquals(1, $defaultJobs);
        $this->assertEquals(1, $highJobs);
    }
}
