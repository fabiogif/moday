<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\NotificationPreferenceResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Get notifications for authenticated user
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }

            $perPage = $request->get('per_page', 15);
            $notifications = $this->notificationService->getUserNotifications(
                $user->id,
                $user->tenant_id,
                $perPage
            );

            return ApiResponseClass::sendResponsePaginate(
                NotificationResource::class,
                $notifications,
                200
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao listar notificações');
        }
    }

    /**
     * Get unread notifications
     */
    public function unread(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }

            $notifications = $this->notificationService->getUnreadNotifications(
                $user->id,
                $user->tenant_id
            );

            return ApiResponseClass::sendResponse(
                NotificationResource::collection($notifications),
                'Notificações não lidas carregadas com sucesso',
                200
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao buscar notificações não lidas');
        }
    }

    /**
     * Get unread count
     */
    public function unreadCount(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }

            $count = $this->notificationService->getUnreadCount(
                $user->id,
                $user->tenant_id
            );

            return ApiResponseClass::sendResponse(
                ['count' => $count],
                'Contagem de não lidas carregada com sucesso',
                200
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao buscar contagem');
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(string $uuid): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }

            $result = $this->notificationService->markAsRead(
                $uuid,
                $user->id,
                $user->tenant_id
            );

            if (!$result) {
                return ApiResponseClass::sendResponse(
                    '',
                    'Notificação não encontrada',
                    404
                );
            }

            return ApiResponseClass::sendResponse(
                '',
                'Notificação marcada como lida',
                200
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao marcar notificação como lida');
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }

            $count = $this->notificationService->markAllAsRead(
                $user->id,
                $user->tenant_id
            );

            return ApiResponseClass::sendResponse(
                ['marked' => $count],
                "{$count} notificações marcadas como lidas",
                200
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao marcar notificações como lidas');
        }
    }

    /**
     * Delete notification
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }

            $result = $this->notificationService->deleteNotification(
                $uuid,
                $user->id,
                $user->tenant_id
            );

            if (!$result) {
                return ApiResponseClass::sendResponse(
                    '',
                    'Notificação não encontrada',
                    404
                );
            }

            return ApiResponseClass::sendResponse(
                '',
                'Notificação deletada com sucesso',
                200
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao deletar notificação');
        }
    }

    /**
     * Get user notification preferences
     */
    public function preferences(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }

            $preferences = $this->notificationService->getUserPreferences(
                $user->id,
                $user->tenant_id
            );

            return ApiResponseClass::sendResponse(
                NotificationPreferenceResource::collection($preferences),
                'Preferências carregadas com sucesso',
                200
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao buscar preferências');
        }
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }

            $validated = $request->validated();

            $result = $this->notificationService->updatePreferences(
                $user->id,
                $user->tenant_id,
                $validated['preferences']
            );

            if (!$result) {
                return ApiResponseClass::sendResponse(
                    '',
                    'Erro ao atualizar preferências',
                    500
                );
            }

            return ApiResponseClass::sendResponse(
                '',
                'Preferências atualizadas com sucesso',
                200
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao atualizar preferências');
        }
    }
}

