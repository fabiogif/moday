<?php

namespace App\Repositories\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Busca por texto livre reutilizável entre repositories.
 *
 * MySQL/MariaDB: usa FULLTEXT (MATCH ... AGAINST), que tem índice e escala
 * muito melhor que LIKE '%termo%' (que nunca usa índice B-tree).
 *
 * Outras conexões (SQLite nos testes): cai para LIKE, já que FULLTEXT não
 * existe ali — mantém a suíte de testes funcionando sem depender de MySQL.
 */
trait SearchesFullText
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array<int, string> $fullTextColumns Colunas cobertas por um índice FULLTEXT
     *        (deve bater exatamente com o índice criado na migration).
     * @param array<int, string> $likeColumns Colunas de código/identificador (e-mail, telefone,
     *        SKU, UUID etc.) onde a busca por substring no meio da string ainda é desejada.
     */
    protected function applyFullTextSearch($query, array $fullTextColumns, string $term, array $likeColumns = [])
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $like = '%' . $term . '%';
        $isMysql = DB::connection()->getDriverName() === 'mysql';

        return $query->where(function ($q) use ($fullTextColumns, $likeColumns, $term, $like, $isMysql) {
            if ($isMysql) {
                if ($fullTextColumns !== []) {
                    $q->whereFullText($fullTextColumns, $term);
                }
                foreach ($likeColumns as $column) {
                    $q->orWhere($column, 'like', $like);
                }
                return;
            }

            foreach (array_merge($fullTextColumns, $likeColumns) as $i => $column) {
                $i === 0 ? $q->where($column, 'like', $like) : $q->orWhere($column, 'like', $like);
            }
        });
    }
}
