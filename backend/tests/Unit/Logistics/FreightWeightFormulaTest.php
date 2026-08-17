<?php

namespace Tests\Unit\Logistics;

use App\Services\Logistics\FreightWeightService;
use PHPUnit\Framework\TestCase;

class FreightWeightFormulaTest extends TestCase
{
    public function test_fp_equals_cf_plus_cv_times_mkp(): void
    {
        $service = new FreightWeightService();

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('computeFpUnit');
        $method->setAccessible(true);

        // CF=1.20, CV=0.80, MKP=1.25 → FP = 2.00 * 1.25 = 2.50
        $fp = $method->invoke($service, 1.20, 0.80, 1.25);
        $this->assertSame(2.5, $fp);
    }

    public function test_default_settings_include_formula_and_per_kg_mode(): void
    {
        // getSettings needs Tenant — covered in feature test; here only defaults shape
        $defaults = FreightWeightService::DEFAULT_SETTINGS;

        $this->assertSame(0.0, $defaults['cf']);
        $this->assertSame(0.0, $defaults['cv']);
        $this->assertSame(1.0, $defaults['mkp']);
        $this->assertSame(FreightWeightService::CHARGE_MODE_PER_KG, $defaults['charge_mode']);
    }
}
