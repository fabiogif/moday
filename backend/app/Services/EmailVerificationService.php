<?php

namespace App\Services;

use App\Mail\EmailVerificationCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class EmailVerificationService
{
    private const CODE_TTL_SECONDS = 900; // 15 min

    private const MAX_ATTEMPTS = 5;

    private const RESEND_COOLDOWN_SECONDS = 60;

    public function __construct(
        private readonly EmailService $emailService,
    ) {}

    public function hasVerifiedEmail(User $user): bool
    {
        return $user->email_verified_at !== null;
    }

    /**
     * @return array{success: bool, message: string, error?: string, retry_after?: int}
     */
    public function send(User $user, bool $isResend = false): array
    {
        if ($this->hasVerifiedEmail($user)) {
            return [
                'success' => false,
                'message' => 'Este e-mail já foi verificado.',
                'error' => 'already_verified',
            ];
        }

        if ($isResend) {
            $cooldownKey = $this->resendKey($user->id);
            $retryAfter = Cache::get($cooldownKey);
            if ($retryAfter) {
                $seconds = max(1, (int) $retryAfter - time());

                return [
                    'success' => false,
                    'message' => "Aguarde {$seconds}s antes de solicitar um novo código.",
                    'error' => 'resend_cooldown',
                    'retry_after' => $seconds,
                ];
            }
        }

        $code = (string) random_int(100000, 999999);

        Cache::put($this->codeKey($user->id), [
            'hash' => Hash::make($code),
            'attempts' => 0,
        ], self::CODE_TTL_SECONDS);

        Cache::put(
            $this->resendKey($user->id),
            time() + self::RESEND_COOLDOWN_SECONDS,
            self::RESEND_COOLDOWN_SECONDS
        );

        try {
            $this->emailService->send($user->email, new EmailVerificationCodeMail($user, $code));
        } catch (\Throwable $e) {
            Log::error('EmailVerification: falha ao enviar código', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Não foi possível enviar o e-mail de verificação. Tente novamente em instantes.',
                'error' => 'send_failed',
            ];
        }

        return [
            'success' => true,
            'message' => $isResend
                ? 'Novo código enviado para o seu e-mail.'
                : 'Código de verificação enviado para o seu e-mail.',
        ];
    }

    /**
     * @return array{success: bool, message: string, error?: string}
     */
    public function resend(User $user): array
    {
        return $this->send($user, true);
    }

    /**
     * @return array{success: bool, message: string, error?: string}
     */
    public function verify(User $user, string $code): array
    {
        if ($this->hasVerifiedEmail($user)) {
            return [
                'success' => true,
                'message' => 'E-mail já verificado.',
            ];
        }

        $payload = Cache::get($this->codeKey($user->id));

        if (!$payload || empty($payload['hash'])) {
            return [
                'success' => false,
                'message' => 'Código expirado. Solicite um novo código.',
                'error' => 'expired_code',
            ];
        }

        $attempts = (int) ($payload['attempts'] ?? 0);
        if ($attempts >= self::MAX_ATTEMPTS) {
            Cache::forget($this->codeKey($user->id));

            return [
                'success' => false,
                'message' => 'Muitas tentativas incorretas. Solicite um novo código ou aguarde alguns minutos.',
                'error' => 'too_many_attempts',
            ];
        }

        if (!Hash::check($code, $payload['hash'])) {
            $payload['attempts'] = $attempts + 1;
            Cache::put($this->codeKey($user->id), $payload, self::CODE_TTL_SECONDS);

            if ($payload['attempts'] >= self::MAX_ATTEMPTS) {
                Cache::forget($this->codeKey($user->id));

                return [
                    'success' => false,
                    'message' => 'Muitas tentativas incorretas. Solicite um novo código ou aguarde alguns minutos.',
                    'error' => 'too_many_attempts',
                ];
            }

            return [
                'success' => false,
                'message' => 'Código incorreto. Verifique o e-mail e tente novamente.',
                'error' => 'invalid_code',
            ];
        }

        $user->forceFill(['email_verified_at' => now()])->save();
        Cache::forget($this->codeKey($user->id));
        Cache::forget($this->resendKey($user->id));

        return [
            'success' => true,
            'message' => 'E-mail confirmado com sucesso.',
        ];
    }

    private function codeKey(int $userId): string
    {
        return "email_verify:{$userId}";
    }

    private function resendKey(int $userId): string
    {
        return "email_verify_resend:{$userId}";
    }
}
