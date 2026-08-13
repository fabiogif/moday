<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holiday_calendars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable(); // null = system-wide
            $table->string('name', 150);
            $table->string('slug', 100);
            // For fixed annual dates (Christmas = month:12, day:25)
            $table->tinyInteger('month')->nullable();
            $table->tinyInteger('day')->nullable();
            // For floating dates (Páscoa, Carnaval) — stored per occurrence
            $table->date('specific_date')->nullable();
            $table->string('type', 30)->default('national'); // national | state | commemorative | custom
            $table->string('category', 50)->default('comercial'); // comercial | religioso | civil
            $table->smallInteger('alert_days_before')->default(30); // alert N days before
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'month', 'day']);
            $table->index(['specific_date']);
            $table->index(['tenant_id', 'type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_calendars');
    }
};
