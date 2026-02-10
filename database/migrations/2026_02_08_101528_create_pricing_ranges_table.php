<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pricing_ranges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_room_type_price_id');
            
            // Rango de tiempo en minutos
            $table->integer('time_from_minutes')->comment('Desde cuántos minutos');
            $table->integer('time_to_minutes')->comment('Hasta cuántos minutos');
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            
            // Auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key
            $table->foreign('branch_room_type_price_id', 'fk_pricing_ranges_brtp')
                  ->references('id')
                  ->on('branch_room_type_prices')
                  ->cascadeOnDelete();
            
            // Índices
            $table->index(['branch_room_type_price_id', 'is_active', 'deleted_at'], 'idx_pricing_ranges');
            $table->index(['time_from_minutes', 'time_to_minutes']);
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pricing_ranges');
    }
};
