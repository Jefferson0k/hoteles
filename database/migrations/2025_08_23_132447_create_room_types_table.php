<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Información básica
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            
            // Características
            $table->integer('capacity')->comment('Capacidad estándar de personas');
            $table->integer('max_capacity')->nullable()->comment('Capacidad máxima permitida');
            
            // Categoría/Clasificación
            $table->string('category')->nullable()->comment('Económica, Estándar, Premium, Lujo');
            
            // Estado
            $table->boolean('is_active')->default(true);
            
            // Auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['is_active', 'deleted_at']);
            $table->index('code');
            $table->index('category');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }
    public function down(){
        Schema::dropIfExists('room_types');
    }
};
