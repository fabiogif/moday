<?php

namespace App\Ports\Integrations\Ifood;

interface IfoodAuthPort
{
    /**
     * @return array{
     *     access_token: string,
     *     expires_in: int,
     *     token_type: string,
     *     scope?: string,
     *     refresh_token?: string
     * }
     */
    public function generateToken(): array;

    /**
     * @param array{refresh_token: string} $payload
     *
     * @return array{
     *     access_token: string,
     *     expires_in: int,
     *     token_type: string,
     *     scope?: string,
     *     refresh_token?: string
     * }
     */
    public function refreshToken(array $payload): array;

    /**
     * @return array{
     *     userCode: string,
     *     authorizationCodeVerifier: string,
     *     verificationUrl: string,
     *     verificationUrlComplete: string,
     *     expiresIn: int
     * }
     */
    public function requestUserCode(string $clientId): array;
}

