<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipment_id');
            $table->unsignedBigInteger('performed_by');
            $table->string('type');
            $table->text('description');
            $table->decimal('cost', 12, 2)->nullable();
            $table->date('performed_at');
            $table->date('next_maintenance_at')->nullable();
            $table->timestamps();

            $table->foreign('equipment_id')->references('id')->on('equipment')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_maintenance_logs');
    }
};

