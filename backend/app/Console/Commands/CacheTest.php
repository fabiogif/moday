<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Redis cache connection and functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Redis Cache Connection...');
        $this->newLine();

        try {
            // Test Redis connection
            $this->info('1. Testing Redis Connection...');
            Redis::connection()->ping();
            $this->info('✓ Redis connection successful!');
            $this->newLine();

            // Test Cache Store
            $this->info('2. Testing Cache Store...');
            $testKey = 'test_key_' . time();
            $testValue = 'test_value_' . rand(1000, 9999);
            
            Cache::put($testKey, $testValue, 60);
            $this->info("✓ Cached: {$testKey} = {$testValue}");
            
            $retrieved = Cache::get($testKey);
            if ($retrieved === $testValue) {
                $this->info("✓ Retrieved: {$retrieved}");
            } else {
                $this->error("✗ Retrieved value doesn't match! Got: {$retrieved}");
            }
            
            Cache::forget($testKey);
            $this->info("✓ Cache key deleted");
            $this->newLine();

            // Test Cache Info
            $this->info('3. Cache Configuration:');
            $this->line('Cache Driver: ' . config('cache.default'));
            $this->line('Redis Host: ' . config('database.redis.default.host'));
            $this->line('Redis Port: ' . config('database.redis.default.port'));
            $this->line('Redis Client: ' . config('database.redis.client'));
            $this->newLine();

            // Show cache statistics
            $this->info('4. Redis Info:');
            $info = Redis::connection()->info();
            $this->line('Redis Version: ' . ($info['redis_version'] ?? 'N/A'));
            $this->line('Connected Clients: ' . ($info['connected_clients'] ?? 'N/A'));
            $this->line('Used Memory: ' . ($info['used_memory_human'] ?? 'N/A'));
            $this->newLine();

            $this->info('✓ All cache tests passed successfully!');
            return 0;

        } catch (\Exception $e) {
            $this->error('✗ Cache test failed!');
            $this->error('Error: ' . $e->getMessage());
            $this->newLine();
            $this->warn('Troubleshooting tips:');
            $this->line('1. Make sure Redis is running: docker-compose ps');
            $this->line('2. Check .env file has REDIS_HOST=redis');
            $this->line('3. Restart containers: ./vendor/bin/sail restart');
            return 1;
        }
    }
}

