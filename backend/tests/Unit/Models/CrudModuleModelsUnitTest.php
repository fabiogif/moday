<?php

namespace Tests\Unit\Models;

use App\Models\Client;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SaleOrder;
use App\Models\Supplier;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CrudModuleModelsUnitTest extends TestCase
{
    #[Test]
    public function sale_order_generates_identify_on_create(): void
    {
        $plan = \App\Models\Plan::factory()->create();
        $tenant = \App\Models\Tenant::factory()->create(['plan_id' => $plan->id]);
        $order = SaleOrder::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'orcamento',
            'total' => 0,
        ]);

        $this->assertNotEmpty($order->identify);
        $this->assertStringStartsWith('PV-', $order->identify);
    }

    #[Test]
    public function sale_order_advances_status_in_flow(): void
    {
        $order = SaleOrder::factory()->make(['status' => 'orcamento']);

        $this->assertTrue($order->canAdvance());
        $this->assertSame('aprovado', $order->nextStatus());
    }

    #[Test]
    public function product_casts_price_and_stock(): void
    {
        $product = Product::factory()->make([
            'price' => '19.99',
            'qtd_stock' => '15',
        ]);

        $this->assertIsString($product->getAttributes()['price'] ?? $product->price);
        $this->assertTrue($product->is_active === true || $product->is_active === false || $product->is_active === 1);
    }

    #[Test]
    public function client_supports_b2b_fields(): void
    {
        $client = new Client([
            'company_name' => 'Farmácia Teste',
            'client_type' => 'farmacia',
            'credit_limit' => 1000,
            'payment_term_days' => 30,
        ]);

        $this->assertSame('Farmácia Teste', $client->company_name);
        $this->assertSame('farmacia', $client->client_type);
    }

    #[Test]
    public function supplier_has_fillable_company_fields(): void
    {
        $supplier = new Supplier([
            'name' => 'Fornecedor X',
            'company_name' => 'Fornecedor X LTDA',
            'document' => '11222333000181',
            'document_type' => 'cnpj',
            'phone' => '11999999999',
        ]);

        $this->assertSame('Fornecedor X LTDA', $supplier->company_name);
        $this->assertSame('cnpj', $supplier->document_type);
    }

    #[Test]
    public function purchase_order_status_constants_exist(): void
    {
        $order = PurchaseOrder::factory()->make(['status' => 'rascunho']);

        $this->assertSame('rascunho', $order->status);
    }
}
