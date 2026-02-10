<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('sub_branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_id'); // referencia a la sucursal principal
            $table->string('name');    // Ej: "Hotel Piura Centro"
            $table->string('code')->unique();
            $table->text('address');
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);

            // Auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Relación con sucursal
            $table->foreign('branch_id')
                  ->references('id')->on('branches')
                  ->cascadeOnDelete();

            // Índices
            $table->index(['branch_id', 'is_active', 'deleted_at']);
            $table->index('code');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down() {
        Schema::dropIfExists('sub_branches');
    }
};
