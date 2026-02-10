<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sub_branch_reservation_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sub_branch_id');
            
            // Configuración de reservas
            $table->integer('min_advance_hours')->default(2)->comment('Anticipación mínima en horas');
            $table->integer('max_advance_days')->default(90)->comment('Anticipación máxima en días');
            $table->decimal('last_minute_surcharge_percentage', 5, 2)->default(0);
            
            // Auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key
            $table->foreign('sub_branch_id')
                  ->references('id')
                  ->on('sub_branches')
                  ->cascadeOnDelete();
            
            // Índices
            $table->index(['sub_branch_id', 'deleted_at']);
            $table->unique(['sub_branch_id', 'deleted_at'], 'unique_sub_branch_reservation');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sub_branch_reservation_settings');
    }
};
