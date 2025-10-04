<?php

namespace Tests\Unit\App\Models;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class CategoryUnitTest extends TestCase
{
    protected function model(): Model
    {
        return new Category();
    }
    /**
     * A basic unit test example.
     */
    public function test_example(): void
    {
        $this->assertTrue(true);
    }

    public function testIfUseTraits()
    {
        $traitsNeed = [HasFactory::class];
        $traitsUser =  array_keys(class_uses($this->model()));

        $this->assertEquals($traitsNeed, $traitsUser);
    }
}
