<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sub_branch_penalty_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sub_branch_id');
            
            // Configuración de penalización
            $table->boolean('penalty_active')->default(true);
            $table->integer('charge_interval_minutes')->default(15)->comment('Cada cuántos minutos se cobra');
            $table->decimal('amount_per_interval', 10, 2)->default(15.00);
            $table->enum('penalty_type', ['fixed', 'progressive'])->default('fixed');
            
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
            $table->unique(['sub_branch_id', 'deleted_at'], 'unique_sub_branch_penalty');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sub_branch_penalty_settings');
    }
};
