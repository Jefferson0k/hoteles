<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rate_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // Por Horas, Por Día, Por Noche
            $table->string('code')->unique(); // HOURLY, DAILY, NIGHTLY
            $table->text('description')->nullable();
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
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rate_types');
    }
};
