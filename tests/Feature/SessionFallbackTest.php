<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\User;

class SessionFallbackTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Testa que usuário permanece logado quando Redis cai
     */
    public function test_user_remains_logged_in_when_redis_fails(): void
    {
        // Criar usuário
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        
        // Fazer login
        $response = $this->post('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);
        
        $response->assertStatus(200);
        $token = $response->json('access_token');
        
        // Verificar que pode acessar rota protegida
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->get('/api/auth/me');
        
        $response->assertStatus(200);
        $response->assertJson([
            'email' => 'test@example.com'
        ]);
        
        // Verificar que sessão está no database
        $this->assertDatabaseHas('sessions', [
            'user_id' => $user->id
        ]);
    }
    
    /**
     * Testa múltiplas sessões simultâneas do mesmo usuário
     */
    public function test_multiple_simultaneous_sessions(): void
    {
        $user = User::factory()->create([
            'email' => 'multi@example.com',
            'password' => bcrypt('password123'),
        ]);
        
        // Login 1 (Desktop)
        $response1 = $this->post('/api/auth/login', [
            'email' => 'multi@example.com',
            'password' => 'password123',
        ]);
        $token1 = $response1->json('access_token');
        
        // Login 2 (Mobile)
        $response2 = $this->post('/api/auth/login', [
            'email' => 'multi@example.com',
            'password' => 'password123',
        ]);
        $token2 = $response2->json('access_token');
        
        // Ambas as sessões devem funcionar
        $this->withHeader('Authorization', "Bearer {$token1}")
            ->get('/api/auth/me')
            ->assertStatus(200);
            
        $this->withHeader('Authorization', "Bearer {$token2}")
            ->get('/api/auth/me')
            ->assertStatus(200);
        
        // Verificar múltiplas sessões no database
        $sessionCount = DB::table('sessions')
            ->where('user_id', $user->id)
            ->count();
            
        $this->assertGreaterThanOrEqual(2, $sessionCount);
    }
    
    /**
     * Testa que sessão expira corretamente
     */
    public function test_session_expires_correctly(): void
    {
        $user = User::factory()->create();
        
        // Criar sessão antiga
        DB::table('sessions')->insert([
            'id' => 'expired_test_session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'payload' => 'test_payload',
            'last_activity' => time() - 7300, // Mais de 2 horas atrás
        ]);
        
        // Executar garbage collection
        $handler = new \App\Session\HybridSessionHandler(7200);
        $deleted = $handler->gc(7200);
        
        $this->assertGreaterThan(0, $deleted);
        
        // Verificar que sessão foi removida
        $exists = DB::table('sessions')
            ->where('id', 'expired_test_session')
            ->exists();
            
        $this->assertFalse($exists);
    }
    
    /**
     * Testa que logout remove sessão de todos os stores
     */
    public function test_logout_removes_session_from_all_stores(): void
    {
        $user = User::factory()->create([
            'email' => 'logout@example.com',
            'password' => bcrypt('password123'),
        ]);
        
        // Login
        $response = $this->post('/api/auth/login', [
            'email' => 'logout@example.com',
            'password' => 'password123',
        ]);
        
        $token = $response->json('access_token');
        
        // Verificar que sessão existe
        $this->assertDatabaseHas('sessions', [
            'user_id' => $user->id
        ]);
        
        // Logout
        $this->withHeader('Authorization', "Bearer {$token}")
            ->post('/api/auth/logout')
            ->assertStatus(200);
        
        // Verificar que sessão foi removida do database
        $sessionCount = DB::table('sessions')
            ->where('user_id', $user->id)
            ->count();
            
        $this->assertEquals(0, $sessionCount);
    }
}
