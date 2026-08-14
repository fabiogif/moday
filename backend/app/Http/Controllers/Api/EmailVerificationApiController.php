<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\VerifyEmailCodeRequest;
use App\Http\Resources\UserResource;
use App\Services\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationApiController extends Controller
{
    public function __construct(
        private readonly EmailVerificationService $emailVerification,
    ) {}

    public function send(Request $request): JsonResponse
    {
        $result = $this->emailVerification->resend($request->user());

        if (!$result['success']) {
            $code = $result['error'] === 'resend_cooldown' ? 429 : 422;

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error' => $result['error'] ?? null,
                'retry_after' => $result['retry_after'] ?? null,
            ], $code);
        }

        return ApiResponseClass::sendResponse([
            'email' => $this->maskEmail($request->user()->email),
        ], $result['message']);
    }

    public function verify(VerifyEmailCodeRequest $request): JsonResponse
    {
        $result = $this->emailVerification->verify(
            $request->user(),
            $request->validated()['code']
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error' => $result['error'] ?? null,
            ], 422);
        }

        $user = $request->user()->fresh(['tenant', 'profiles.permissions']);
        $user->setAttribute('permission_slugs', $user->getPermissionsList());

        return ApiResponseClass::sendResponse([
            'user' => new UserResource($user),
            'email_verified' => true,
        ], $result['message']);
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '' || $domain === '') {
            return '***';
        }

        $visible = mb_substr($local, 0, 1);

        return $visible . '***@' . $domain;
    }
}
