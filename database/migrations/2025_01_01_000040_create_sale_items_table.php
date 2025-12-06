<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sale_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->cascadeOnUpdate();
            $table->integer('qty');
            $table->unsignedInteger('price');
            $table->timestamps();
            $table->softDeletes();
            $table->index('updated_at');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
