<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('details_plan')) {
            Schema::create('details_plan', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['plan_id', 'name'], 'details_plan_plan_name_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('details_plan');
    }
};

