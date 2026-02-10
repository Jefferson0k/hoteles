<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sub_branch_checkin_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sub_branch_id');
            
            // Horarios
            $table->time('checkin_time')->default('14:00');
            $table->time('checkout_time')->default('12:00');
            
            // Costos adicionales
            $table->decimal('early_checkin_cost', 10, 2)->default(0);
            $table->decimal('late_checkout_cost', 10, 2)->default(0);
            
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
            $table->unique(['sub_branch_id', 'deleted_at'], 'unique_sub_branch_checkin');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sub_branch_checkin_settings');
    }
};
