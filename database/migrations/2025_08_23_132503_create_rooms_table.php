<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('floor_id');
            $table->uuid('room_type_id');
            $table->string('room_number');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            
            // Estado
            $table->enum('status', [
                'available',
                'occupied',
                'maintenance',
                'cleaning'
            ])->default('available');
            $table->timestamp('status_changed_at')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('floor_id')
                  ->references('id')
                  ->on('floors')
                  ->cascadeOnDelete();
            $table->foreign('room_type_id')
                  ->references('id')
                  ->on('room_types');
            
            // Índices
            $table->index(['floor_id', 'room_number', 'deleted_at']);
            $table->index(['status', 'is_active', 'deleted_at']);
            $table->index('room_type_id');
            $table->index('created_by');
            $table->index('updated_by');
            
            // Evitar duplicados de habitación en el mismo piso
            $table->unique(['floor_id', 'room_number', 'deleted_at'], 'unique_room_per_floor');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rooms');
    }
};
