<?php

namespace Tests\Unit\Services;

use App\Models\Integrations\Ifood\IfoodApiToken;
use App\Ports\Integrations\Ifood\IfoodAuthPort;
use App\Repositories\Contracts\IfoodTokenRepositoryInterface;
use App\Services\Integrations\Ifood\IfoodTokenService;
use Illuminate\Support\Facades\Crypt;
use Mockery;
use Tests\TestCase;

class IfoodTokenServiceTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);
    }

    public function test_generate_and_store_token_encrypts_credentials(): void
    {
        $authPort = Mockery::mock(IfoodAuthPort::class);
        $repository = Mockery::mock(IfoodTokenRepositoryInterface::class);

        $authPort->shouldReceive('generateToken')
            ->once()
            ->andReturn([
                'access_token' => 'plain-token',
                'refresh_token' => 'refresh-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ]);

        $repository->shouldReceive('storeToken')
            ->once()
            ->with(Mockery::on(function ($data) {
                return isset($data['tenant_id'], $data['access_token'], $data['refresh_token'], $data['expires_at'])
                    && $data['tenant_id'] === 1
                    && $data['access_token'] !== 'plain-token'
                    && $data['refresh_token'] !== 'refresh-token';
            }))
            ->andReturn($this->makeTokenModel('plain-token', 'refresh-token'));

        $service = new IfoodTokenService($authPort, $repository);

        $token = $service->generateAndStoreToken(1);

        $this->assertInstanceOf(IfoodApiToken::class, $token);
        $this->assertSame('plain-token', Crypt::decryptString($token->access_token));
    }

    public function test_get_valid_access_token_returns_existing_token(): void
    {
        $authPort = Mockery::mock(IfoodAuthPort::class);
        $repository = Mockery::mock(IfoodTokenRepositoryInterface::class);

        $tokenModel = $this->makeTokenModel('plain-token', 'refresh-token');
        $tokenModel->expires_at = now()->addHour();

        $repository->shouldReceive('getActiveToken')
            ->once()
            ->with(1)
            ->andReturn($tokenModel);

        $service = new IfoodTokenService($authPort, $repository);

        $token = $service->getValidAccessToken(1);

        $this->assertSame('plain-token', $token);
    }

    public function test_get_valid_access_token_refreshes_when_expiring(): void
    {
        $authPort = Mockery::mock(IfoodAuthPort::class);
        $repository = Mockery::mock(IfoodTokenRepositoryInterface::class);

        $expiringToken = $this->makeTokenModel('old-token', 'refresh-token');
        $expiringToken->expires_at = now()->addMinute();

        $repository->shouldReceive('getActiveToken')
            ->once()
            ->with(1)
            ->andReturn($expiringToken);

        $authPort->shouldReceive('refreshToken')
            ->once()
            ->with([
                'refresh_token' => 'refresh-token',
            ])
            ->andReturn([
                'access_token' => 'new-token',
                'refresh_token' => 'refresh-token',
                'expires_in' => 7200,
                'token_type' => 'Bearer',
            ]);

        $repository->shouldReceive('storeToken')
            ->once()
            ->andReturn($this->makeTokenModel('new-token', 'refresh-token'));

        $service = new IfoodTokenService($authPort, $repository);

        $token = $service->getValidAccessToken(1);

        $this->assertSame('new-token', $token);
    }

    private function makeTokenModel(string $accessToken, string $refreshToken): IfoodApiToken
    {
        $model = new IfoodApiToken([
            'tenant_id' => 1,
            'access_token' => Crypt::encryptString($accessToken),
            'refresh_token' => Crypt::encryptString($refreshToken),
            'expires_at' => now()->addHour(),
            'token_type' => 'Bearer',
            'scope' => null,
            'metadata' => [],
            'is_active' => true,
        ]);

        $model->exists = true;

        return $model;
    }
}

