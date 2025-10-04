<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid');
            $table->unsignedBigInteger('plan_id');
            $table->string('name')->unique();
            $table->string('cnpj')->unique();
            $table->string('email')->unique();
            $table->string('url')->unique();
            $table->string('logo')->nullable();
            $table->enum('active', ['Y', 'N'])->default('Y');

            $table->date('subscription')->nullable();
            $table->date('expire_at')->nullable();
            $table->string('subscription_id', 255)->nullable();
            $table->string('subscription_plan')->nullable();
            $table->string('subscription_active')->default(false);
            $table->string('subscription_suspended')->default(false);
            $table->timestamps();

            $table->foreign('plan_id')->references('id')->on('plans');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
