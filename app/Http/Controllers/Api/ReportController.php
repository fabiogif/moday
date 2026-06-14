<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\DailySalesReportRequest;
use App\Http\Requests\ClientsReportRequest;
use App\Http\Requests\TopProductsReportRequest;
use App\Http\Requests\MonthlyFinancialReportRequest;
use App\Http\Requests\TableOccupancyReportRequest;
use App\Reports\Builders\DailySalesReportBuilder;
use App\Reports\Builders\ClientsReportBuilder;
use App\Reports\Builders\TopProductsReportBuilder;
use App\Reports\Builders\MonthlyFinancialReportBuilder;
use App\Reports\Builders\TableOccupancyReportBuilder;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $service
    ) {}
    
    /**
     * Lista os relatórios disponíveis
     */
    public function index(): JsonResponse
    {
        $reports = [
            [
                'id' => 'daily-sales',
                'name' => 'Relatório de Vendas Diário',
                'description' => 'Relatório detalhado de vendas por dia',
                'formats' => ['pdf', 'excel', 'csv'],
                'filters' => [
                    'date' => 'Data (opcional, padrão: hoje)',
                    'start_date' => 'Data inicial (opcional)',
                    'end_date' => 'Data final (opcional)',
                    'client_id' => 'ID do cliente (opcional)',
                    'status' => 'Status do pedido (opcional)',
                ],
            ],
            [
                'id' => 'clients',
                'name' => 'Relatório de Clientes',
                'description' => 'Relatório com estatísticas de clientes',
                'formats' => ['pdf', 'excel', 'csv'],
                'filters' => [
                    'is_active' => 'Status (ativo/inativo)',
                    'city' => 'Cidade (opcional)',
                    'state' => 'Estado (opcional)',
                ],
            ],
            [
                'id' => 'top-products',
                'name' => 'Produtos Mais Vendidos',
                'description' => 'Ranking dos produtos mais vendidos',
                'formats' => ['pdf', 'excel', 'csv'],
                'filters' => [
                    'start_date' => 'Data inicial (obrigatório)',
                    'end_date' => 'Data final (obrigatório)',
                    'limit' => 'Quantidade de produtos (padrão: 20)',
                ],
            ],
            [
                'id' => 'monthly-financial',
                'name' => 'Relatório Financeiro Mensal',
                'description' => 'Análise financeira mensal detalhada',
                'formats' => ['pdf', 'excel', 'csv'],
                'filters' => [
                    'month' => 'Mês (1-12, padrão: mês atual)',
                    'year' => 'Ano (padrão: ano atual)',
                ],
            ],
            [
                'id' => 'table-occupancy',
                'name' => 'Ocupação de Mesas',
                'description' => 'Análise de ocupação e faturamento por mesa',
                'formats' => ['pdf', 'excel', 'csv'],
                'filters' => [
                    'start_date' => 'Data inicial (obrigatório)',
                    'end_date' => 'Data final (obrigatório)',
                ],
            ],
        ];
        
        return ApiResponseClass::sendResponse($reports, 'Relatórios disponíveis', 200);
    }
    
    /**
     * Gera relatório de vendas diário
     */
    public function dailySales(DailySalesReportRequest $request)
    {
        return $this->generateReport(
            DailySalesReportBuilder::class,
            $request,
            $request->validated()
        );
    }
    
    /**
     * Gera relatório de clientes
     */
    public function clients(ClientsReportRequest $request)
    {
        return $this->generateReport(
            ClientsReportBuilder::class,
            $request,
            $request->validated()
        );
    }
    
    /**
     * Gera relatório de produtos mais vendidos
     */
    public function topProducts(TopProductsReportRequest $request)
    {
        return $this->generateReport(
            TopProductsReportBuilder::class,
            $request,
            $request->validated()
        );
    }
    
    /**
     * Gera relatório financeiro mensal
     */
    public function monthlyFinancial(MonthlyFinancialReportRequest $request)
    {
        return $this->generateReport(
            MonthlyFinancialReportBuilder::class,
            $request,
            $request->validated()
        );
    }
    
    /**
     * Gera relatório de ocupação de mesas
     */
    public function tableOccupancy(TableOccupancyReportRequest $request)
    {
        return $this->generateReport(
            TableOccupancyReportBuilder::class,
            $request,
            $request->validated()
        );
    }
    
    /**
     * Método auxiliar para gerar relatório
     */
    private function generateReport(string $builderClass, Request $request, array $validated)
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::unauthorized('Usuário não autenticado ou sem tenant');
            }
            
            $format = $validated['format'];
            unset($validated['format']);
            
            // Adiciona tenant_id aos filtros
            $validated['tenant_id'] = $user->tenant_id;
            
            $builder = app($builderClass);
            $exporter = $this->service->getExporter($format);
            
            $filePath = $this->service->generate($builder, $exporter, $validated);
            $downloadName = basename($filePath);
            
            // Para CSV, retorna o conteúdo diretamente para facilitar assertions em testes
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
            
            // Retorna o arquivo para download com o mime type correto
            return response()->download(
                $filePath,
                $downloadName,
                [
                    'Content-Type' => $exporter->getMimeType(),
                    'Content-Disposition' => 'attachment; filename="' . $downloadName . '"'
                ]
            )->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao gerar relatório');
        }
    }
}

