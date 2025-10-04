<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\CategoryStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid');
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('description');
            $table->string('url');
            $table->boolean('is_active')->default(true);
            $table->enum('status', array_column(CategoryStatus::cases(), 'name'))->default('A');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->timestamps();


        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
