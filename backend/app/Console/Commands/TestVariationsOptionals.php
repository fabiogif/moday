<?php

namespace App\Console\Commands;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestVariationsOptionals extends Command
{
    protected $signature = 'test:variations-optionals {product_id?}';
    protected $description = 'Testa se variations e optionals estão sendo carregados corretamente';

    public function handle()
    {
        $productId = $this->argument('product_id');

        if ($productId) {
            $product = Product::where('id', $productId)
                ->orWhere('uuid', $productId)
                ->first();
        } else {
            $product = Product::whereNotNull('variations')
                ->orWhereNotNull('optionals')
                ->first();
        }

        if (!$product) {
            $this->error('❌ Nenhum produto encontrado');
            $this->info('💡 Execute: php artisan db:seed --class=ProductSeeder');
            return 1;
        }

        $this->info('=== TESTE DE VARIATIONS E OPTIONALS ===');
        $this->newLine();

        $this->info("📦 Produto: {$product->name}");
        $this->info("🆔 ID: {$product->id}");
        $this->info("🔑 UUID: {$product->uuid}");
        $this->newLine();

        // Testar variations
        $this->info('🔍 VARIATIONS:');
        $this->line("- Tipo: " . gettype($product->variations));
        $this->line("- É Array? " . (is_array($product->variations) ? 'SIM ✅' : 'NÃO ❌'));
        $this->line("- Quantidade: " . (is_array($product->variations) ? count($product->variations) : 0));

        if (is_array($product->variations) && count($product->variations) > 0) {
            $this->line("- Conteúdo:");
            foreach ($product->variations as $variation) {
                $this->line("  • {$variation['name']}: R$ {$variation['price']}");
            }
        } else {
            $this->warn("- Conteúdo: Vazio ou inválido");
        }
        $this->newLine();

        // Testar optionals
        $this->info('🔍 OPTIONALS:');
        $this->line("- Tipo: " . gettype($product->optionals));
        $this->line("- É Array? " . (is_array($product->optionals) ? 'SIM ✅' : 'NÃO ❌'));
        $this->line("- Quantidade: " . (is_array($product->optionals) ? count($product->optionals) : 0));

        if (is_array($product->optionals) && count($product->optionals) > 0) {
            $this->line("- Conteúdo:");
            foreach ($product->optionals as $optional) {
                $this->line("  • {$optional['name']}: R$ {$optional['price']}");
            }
        } else {
            $this->warn("- Conteúdo: Vazio ou inválido");
        }
        $this->newLine();

        // Testar API Resource
        $this->info('🔍 API RESOURCE:');
        $resource = new ProductResource($product);
        $arrayData = $resource->toArray(request());

        $this->line("- Variations no Resource:");
        if (isset($arrayData['variations']) && is_array($arrayData['variations'])) {
            $this->info("  ✅ Existe e é array (" . count($arrayData['variations']) . " itens)");
            foreach ($arrayData['variations'] as $variation) {
                $this->line("    • {$variation['name']}: R$ {$variation['price']}");
            }
        } else {
            $this->error("  ❌ Não existe ou não é array");
        }

        $this->line("- Optionals no Resource:");
        if (isset($arrayData['optionals']) && is_array($arrayData['optionals'])) {
            $this->info("  ✅ Existe e é array (" . count($arrayData['optionals']) . " itens)");
            foreach ($arrayData['optionals'] as $optional) {
                $this->line("    • {$optional['name']}: R$ {$optional['price']}");
            }
        } else {
            $this->error("  ❌ Não existe ou não é array");
        }
        $this->newLine();

        // Verificar banco de dados diretamente
        $this->info('🔍 BANCO DE DADOS (RAW):');
        $rawData = DB::table('products')->where('id', $product->id)->first();
        $this->line("- variations (raw): " . ($rawData->variations ?? 'NULL'));
        $this->line("- optionals (raw): " . ($rawData->optionals ?? 'NULL'));

        $this->newLine();
        $this->info('=== FIM DO TESTE ===');

        return 0;
    }
}

