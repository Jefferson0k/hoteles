<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sub_branch_discounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sub_branch_id');
            
            // Información del descuento
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['percentage', 'fixed', 'seasonal'])->default('percentage');
            $table->decimal('value', 10, 2);
            $table->text('condition')->nullable()->comment('Ej: Mínimo 3 noches, Solo fines de semana');
            
            // Vigencia
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
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
            $table->index(['sub_branch_id', 'is_active', 'deleted_at'], 'idx_sub_branch_discounts');
            $table->index(['valid_from', 'valid_to']);
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sub_branch_discounts');
    }
};
