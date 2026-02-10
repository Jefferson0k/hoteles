<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('kardex', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('sub_branch_id');
            $table->uuid('movement_detail_id')->nullable();
            $table->decimal('precio_total', 15, 2)->default(0.00);
            $table->decimal('SAnteriorCaja', 15, 2)->default(0.00);
            $table->decimal('SAnteriorFraccion', 15, 2)->default(0.00);
            $table->decimal('cantidadCaja', 15, 2)->default(0.00);
            $table->decimal('cantidadFraccion', 15, 2)->default(0.00);
            $table->decimal('SParcialCaja', 15, 2)->default(0.00);
            $table->decimal('SParcialFraccion', 15, 2)->default(0.00);
            $table->enum('movement_type', ['entrada', 'salida'])->default('entrada');
            $table->enum('movement_category', ['compra', 'venta', 'ajuste', 'otros'])->default('otros');
            $table->tinyInteger('estado')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('sub_branch_id')->references('id')->on('sub_branches');
            $table->foreign('movement_detail_id')->references('id')->on('movement_details')->cascadeOnDelete();
            $table->index(['product_id', 'sub_branch_id', 'movement_detail_id']);
        });
    }

    public function down() {
        Schema::dropIfExists('kardex');
    }
};
