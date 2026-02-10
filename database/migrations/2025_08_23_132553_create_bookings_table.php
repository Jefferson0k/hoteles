<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('booking_code')->unique();
            
            // RELACIONES PRINCIPALES
            $table->uuid('room_id');
            $table->uuid('customers_id');
            $table->uuid('rate_type_id');
            $table->uuid('currency_id');
            $table->uuid('sub_branch_id');
            $table->uuid('client_id')->nullable();
            
            // TIEMPO Y FECHAS
            $table->datetime('check_in');
            $table->datetime('check_out');
            $table->datetime('actual_check_out')->nullable();
            $table->integer('quantity')->default(1)->comment('Cantidad de unidades reservadas');
            $table->integer('total_hours');
            $table->integer('actual_hours')->nullable();
            
            // COSTOS
            $table->decimal('rate_per_unit', 10, 2)->nullable();
            $table->decimal('rate_per_hour', 10, 2);
            $table->decimal('room_subtotal', 10, 2);
            $table->decimal('products_subtotal', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('penalty_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            
            // ESTADOS
            $table->enum('status', [
                'pending',
                'confirmed',
                'checked_in',
                'checked_out',
                'cancelled'
            ])->default('pending');
            
            // CANCELACIÓN
            $table->datetime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            
            // CÓMO TERMINÓ
            $table->enum('finish_type', ['manual', 'automatic'])->nullable();
            
            // COMPROBANTE
            $table->enum('voucher_type', ['ticket', 'boleta', 'factura'])->default('ticket');
            $table->string('voucher_number')->nullable();
            
            // OTROS
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('finished_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // FOREIGN KEYS
            $table->foreign('room_id')
                  ->references('id')
                  ->on('rooms');
            $table->foreign('customers_id')
                  ->references('id')
                  ->on('customers');
            $table->foreign('rate_type_id')
                  ->references('id')
                  ->on('rate_types');
            $table->foreign('currency_id')
                  ->references('id')
                  ->on('currencies');
            $table->foreign('sub_branch_id')
                  ->references('id')
                  ->on('sub_branches');
            
            // ÍNDICES
            $table->index(['room_id', 'status']);
            $table->index(['status', 'check_out']);
            $table->index(['booking_code']);
            $table->index(['customers_id']);
            $table->index(['sub_branch_id', 'status', 'deleted_at']);
            $table->index(['created_at', 'status']);
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookings');
    }
};
