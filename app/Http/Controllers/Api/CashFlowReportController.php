<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\AccountReceivable;
use App\Models\AccountPayable;
use App\Models\Expense;
use App\Services\AuthTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CashFlowReportController extends Controller
{
    public function __construct(private readonly AuthTenantService $authTenantService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $start = Carbon::parse($request->get('start', now()->startOfMonth()))->startOfDay();
            $end   = Carbon::parse($request->get('end', now()->endOfMonth()))->endOfDay();

            $receivables = AccountReceivable::where('tenant_id', $tenantId)
                ->whereBetween('due_date', [$start, $end])
                ->whereIn('status', ['pendente', 'vencido', 'recebido', 'parcial'])
                ->select('due_date', 'amount', 'amount_received', 'status', 'description', 'client_id')
                ->get();

            $payables = AccountPayable::where('tenant_id', $tenantId)
                ->whereBetween('due_date', [$start, $end])
                ->whereIn('status', ['pendente', 'vencido', 'pago', 'parcial'])
                ->select('due_date', 'amount', 'amount_paid', 'status', 'description', 'supplier_id')
                ->get();

            $expenses = Expense::where('tenant_id', $tenantId)
                ->whereBetween('due_date', [$start, $end])
                ->whereIn('status', ['pendente', 'pago'])
                ->select('due_date', 'amount', 'status', 'description')
                ->get();

            $timeline = $this->buildTimeline($start, $end, $receivables, $payables, $expenses);

            return ApiResponseClass::sendResponse([
                'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
                'summary' => $this->buildSummary($receivables, $payables, $expenses),
                'timeline' => $timeline,
            ], 'Fluxo de caixa gerado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao gerar fluxo de caixa');
        }
    }

    private function buildTimeline(
        Carbon $start, Carbon $end,
        Collection $receivables, Collection $payables, Collection $expenses
    ): array {
        $days = [];
        $current = $start->copy();
        $runningBalance = 0;

        while ($current <= $end) {
            $date = $current->toDateString();

            $inRealized  = $receivables->where('due_date', $date)->where('status', 'recebido')->sum('amount_received');
            $inProjected = $receivables->where('due_date', $date)->whereIn('status', ['pendente', 'vencido'])->sum('amount');

            $outRealized  = $payables->where('due_date', $date)->where('status', 'pago')->sum('amount_paid')
                          + $expenses->where('due_date', $date)->where('status', 'pago')->sum('amount');
            $outProjected = $payables->where('due_date', $date)->whereIn('status', ['pendente', 'vencido'])->sum('amount')
                          + $expenses->where('due_date', $date)->where('status', 'pendente')->sum('amount');

            $netRealized  = $inRealized - $outRealized;
            $netProjected = $inProjected - $outProjected;
            $runningBalance += $netRealized + $netProjected;

            if ($inRealized + $inProjected + $outRealized + $outProjected > 0) {
                $days[] = [
                    'date'            => $date,
                    'in_realized'     => round($inRealized, 2),
                    'in_projected'    => round($inProjected, 2),
                    'out_realized'    => round($outRealized, 2),
                    'out_projected'   => round($outProjected, 2),
                    'net_realized'    => round($netRealized, 2),
                    'net_projected'   => round($netProjected, 2),
                    'running_balance' => round($runningBalance, 2),
                ];
            }

            $current->addDay();
        }

        return $days;
    }

    private function buildSummary(Collection $receivables, Collection $payables, Collection $expenses): array
    {
        $totalInRealized  = $receivables->where('status', 'recebido')->sum('amount_received');
        $totalInProjected = $receivables->whereIn('status', ['pendente', 'vencido'])->sum('amount');
        $totalOutRealized = $payables->where('status', 'pago')->sum('amount_paid')
                          + $expenses->where('status', 'pago')->sum('amount');
        $totalOutProjected = $payables->whereIn('status', ['pendente', 'vencido'])->sum('amount')
                           + $expenses->where('status', 'pendente')->sum('amount');

        return [
            'total_in_realized'    => round($totalInRealized, 2),
            'total_in_projected'   => round($totalInProjected, 2),
            'total_out_realized'   => round($totalOutRealized, 2),
            'total_out_projected'  => round($totalOutProjected, 2),
            'net_realized'         => round($totalInRealized - $totalOutRealized, 2),
            'net_projected'        => round($totalInProjected - $totalOutProjected, 2),
            'total_net'            => round(($totalInRealized + $totalInProjected) - ($totalOutRealized + $totalOutProjected), 2),
        ];
    }
}
