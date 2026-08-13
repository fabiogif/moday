<?php

namespace App\Reports\Contracts;

interface ReportBuilderInterface
{
    /**
     * Define os filtros do relatório
     */
    public function setFilters(array $filters): self;
    
    /**
     * Constrói e retorna os dados do relatório
     */
    public function build(): array;
    
    /**
     * Retorna as colunas do relatório
     */
    public function getColumns(): array;
    
    /**
     * Retorna o título do relatório
     */
    public function getTitle(): string;
    
    /**
     * Retorna metadados adicionais
     */
    public function getMetadata(): array;
}

