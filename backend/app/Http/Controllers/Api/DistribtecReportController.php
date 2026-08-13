<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\PeriodReportPreviewRequest;
use App\Http\Requests\PeriodReportRequest;
use App\Reports\Builders\AbcProductsReportBuilder;
use App\Reports\Builders\SalesByClientReportBuilder;
use App\Reports\Builders\SalesByPeriodReportBuilder;
use App\Reports\Builders\StockTurnoverReportBuilder;
use App\Services\DistribtecReportService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DistribtecReportController extends Controller
{
    public function __construct(
        private DistribtecReportService $reportService,
        private ReportService $exportService
    ) {}

    public function salesPreview(PeriodReportPreviewRequest $request): JsonResponse
    {
        return $this->preview($request, 'salesByPeriod');
    }

    public function salesExport(PeriodReportRequest $request)
    {
        return $this->export(SalesByPeriodReportBuilder::class, $request);
    }

    public function salesByClientPreview(PeriodReportPreviewRequest $request): JsonResponse
    {
        return $this->preview($request, 'salesByClient');
    }

    public function salesByClientExport(PeriodReportRequest $request)
    {
        return $this->export(SalesByClientReportBuilder::class, $request);
    }

    public function abcProductsPreview(PeriodReportPreviewRequest $request): JsonResponse
    {
        return $this->preview($request, 'abcProducts');
    }

    public function abcProductsExport(PeriodReportRequest $request)
    {
        return $this->export(AbcProductsReportBuilder::class, $request);
    }

    public function stockTurnoverPreview(PeriodReportPreviewRequest $request): JsonResponse
    {
        return $this->preview($request, 'stockTurnover');
    }

    public function stockTurnoverExport(PeriodReportRequest $request)
    {
        return $this->export(StockTurnoverReportBuilder::class, $request);
    }

    private function preview(PeriodReportPreviewRequest $request, string $method): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::unauthorized('Usuário não autenticado ou sem tenant');
            }

            $filters = array_merge($request->validated(), ['tenant_id' => $user->tenant_id]);
            $data = $this->reportService->{$method}($filters);

            return ApiResponseClass::sendResponse($data, 'Relatório carregado', 200);
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao carregar relatório');
        }
    }

    private function export(string $builderClass, Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::unauthorized('Usuário não autenticado ou sem tenant');
            }

            $validated = $request->validated();
            $format = $validated['format'];
            unset($validated['format']);
            $validated['tenant_id'] = $user->tenant_id;

            $builder = app($builderClass);
            $exporter = $this->exportService->getExporter($format);
            $filePath = $this->exportService->generate($builder, $exporter, $validated);
            $downloadName = basename($filePath);

            if ($format === 'csv') {
                $contents = file_get_contents($filePath) ?: '';
                @unlink($filePath);

                return response(
                    $contents,
                    200,
                    [
                        'Content-Type' => $exporter->getMimeType() . '; charset=UTF-8',
                        'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
                    ]
                );
            }

            return response()->download(
                $filePath,
                $downloadName,
                [
                    'Content-Type' => $exporter->getMimeType(),
                    'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
                ]
            )->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao gerar relatório');
        }
    }
}
