<?php

namespace Tests\Unit\Rules;

use App\Models\Batch;
use App\Rules\ValidBatchForSale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValidBatchForSaleTest extends TestCase
{
    use RefreshDatabase;

    private ValidBatchForSale $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new ValidBatchForSale();
    }

    private function validate(mixed $value): ?string
    {
        $error = null;
        $this->rule->validate('batch_id', $value, function (string $msg) use (&$error) {
            $error = $msg;
        });
        return $error;
    }

    #[Test]
    public function passes_when_batch_id_is_null(): void
    {
        $this->assertNull($this->validate(null));
    }

    #[Test]
    public function fails_when_batch_not_found(): void
    {
        $error = $this->validate(99999);

        $this->assertStringContainsString('não foi encontrado', $error);
    }

    #[Test]
    public function fails_when_batch_status_is_expired(): void
    {
        $batch = Batch::factory()->create(['status' => 'expired', 'batch_number' => 'LOT-001']);

        $error = $this->validate($batch->id);

        $this->assertStringContainsString('vencido', $error);
        $this->assertStringContainsString('LOT-001', $error);
    }

    #[Test]
    public function fails_when_batch_status_is_quarantine(): void
    {
        $batch = Batch::factory()->create(['status' => 'quarantine', 'batch_number' => 'LOT-002']);

        $error = $this->validate($batch->id);

        $this->assertStringContainsString('quarentena', $error);
    }

    #[Test]
    public function fails_when_batch_status_is_recalled(): void
    {
        $batch = Batch::factory()->create(['status' => 'recalled', 'batch_number' => 'LOT-003']);

        $error = $this->validate($batch->id);

        $this->assertStringContainsString('recall', $error);
    }

    #[Test]
    public function fails_when_expiry_date_is_past(): void
    {
        $batch = Batch::factory()->create([
            'status'      => 'active',
            'batch_number' => 'LOT-004',
            'expiry_date' => Carbon::yesterday(),
        ]);

        $error = $this->validate($batch->id);

        $this->assertStringContainsString('venceu', $error);
    }

    #[Test]
    public function passes_when_batch_is_active_and_not_expired(): void
    {
        $batch = Batch::factory()->create([
            'status'      => 'active',
            'expiry_date' => Carbon::tomorrow(),
        ]);

        $this->assertNull($this->validate($batch->id));
    }
}
