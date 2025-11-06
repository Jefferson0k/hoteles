<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('booking_consumptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_id');
            $table->uuid('product_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->datetime('consumed_at');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'paid'])->default('paid');
            // Auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('booking_id')->references('id')->on('bookings');
            $table->foreign('product_id')->references('id')->on('products');
            
            // Índices
            $table->index(['booking_id', 'deleted_at']);
            $table->index(['consumed_at', 'deleted_at']);
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('booking_consumptions');
    }
};
