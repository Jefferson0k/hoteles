<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sub_branch_notification_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sub_branch_id');
            
            // Configuración de notificaciones
            $table->boolean('reservation_reminder_active')->default(true);
            $table->integer('reminder_hours_before')->default(2);
            $table->boolean('excess_alert_active')->default(true);
            $table->boolean('confirmation_email_active')->default(true);
            
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
            $table->unique(['sub_branch_id', 'deleted_at'], 'unique_sub_branch_notif');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sub_branch_notification_settings');
    }
};
