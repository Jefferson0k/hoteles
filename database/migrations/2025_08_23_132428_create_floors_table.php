<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('floors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sub_branch_id');
            $table->string('name');
            $table->integer('floor_number');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            
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
            $table->index(['sub_branch_id', 'is_active', 'deleted_at']);
            $table->index('created_by');
            $table->index('updated_by');
            
            // Evitar duplicados de piso en el mismo local
            $table->unique(['sub_branch_id', 'floor_number', 'deleted_at'], 'unique_floor_per_sub_branch');
        });
    }

    public function down()
    {
        Schema::dropIfExists('floors');
    }
};