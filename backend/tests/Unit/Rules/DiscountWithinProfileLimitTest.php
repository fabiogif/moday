<?php

namespace Tests\Unit\Rules;

use App\Rules\DiscountWithinProfileLimit;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DiscountWithinProfileLimitTest extends TestCase
{
    private function validate(DiscountWithinProfileLimit $rule, mixed $value): ?string
    {
        $error = null;
        $rule->validate('discount_percent', $value, function (string $msg) use (&$error) {
            $error = $msg;
        });
        return $error;
    }

    #[Test]
    public function passes_when_max_allowed_is_null(): void
    {
        $rule = new DiscountWithinProfileLimit(null);

        $this->assertNull($this->validate($rule, 100));
    }

    #[Test]
    public function passes_when_discount_equals_max_allowed(): void
    {
        $rule = new DiscountWithinProfileLimit(10.0);

        $this->assertNull($this->validate($rule, 10.0));
    }

    #[Test]
    public function passes_when_discount_is_below_limit(): void
    {
        $rule = new DiscountWithinProfileLimit(15.0);

        $this->assertNull($this->validate($rule, 10.0));
    }

    #[Test]
    public function fails_when_discount_exceeds_limit(): void
    {
        $rule = new DiscountWithinProfileLimit(10.0);

        $error = $this->validate($rule, 10.01);

        $this->assertNotNull($error);
        $this->assertStringContainsString('10%', $error);
    }

    #[Test]
    public function fails_when_discount_far_exceeds_limit(): void
    {
        $rule = new DiscountWithinProfileLimit(5.0);

        $error = $this->validate($rule, 50.0);

        $this->assertNotNull($error);
        $this->assertStringContainsString('5%', $error);
    }

    #[Test]
    public function passes_zero_discount_with_any_limit(): void
    {
        $rule = new DiscountWithinProfileLimit(5.0);

        $this->assertNull($this->validate($rule, 0));
    }
}
