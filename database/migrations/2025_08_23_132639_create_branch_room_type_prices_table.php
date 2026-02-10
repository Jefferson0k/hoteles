<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('branch_room_type_prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Relaciones
            $table->foreignUuid('sub_branch_id')->constrained('sub_branches')->onDelete('cascade');
            $table->foreignUuid('room_type_id')->constrained('room_types')->onDelete('cascade');
            $table->foreignUuid('rate_type_id')->constrained('rate_types')->onDelete('cascade');
            
            // Vigencia (para temporadas)
            $table->date('effective_from')->comment('Fecha de inicio de vigencia');
            $table->date('effective_to')->nullable()->comment('Fecha de fin de vigencia');
            
            // Estado
            $table->boolean('is_active')->default(true);
            
            // Auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['sub_branch_id', 'room_type_id', 'rate_type_id'], 'branch_room_rate_idx');
            $table->index(['is_active', 'deleted_at']);
            $table->index('effective_from');
            $table->index('effective_to');
            $table->index('created_by');
            $table->index('updated_by');
            
            // Único: Una configuración por combinación y fecha
            $table->unique(
                ['sub_branch_id', 'room_type_id', 'rate_type_id', 'effective_from', 'deleted_at'],
                'unique_branch_room_rate_effective'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('branch_room_type_prices');
    }
};