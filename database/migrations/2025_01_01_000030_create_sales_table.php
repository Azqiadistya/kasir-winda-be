<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('paid')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('change')->default(0);
            $table->string('cashier');
            $table->timestamps();
            $table->softDeletes();
            $table->index('updated_at');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
