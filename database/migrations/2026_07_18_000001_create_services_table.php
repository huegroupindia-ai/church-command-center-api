<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('church_id')->default(1);
            $table->string('name');
            $table->date('service_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('service_type')->default('sunday_morning');
            $table->string('speaker')->nullable();
            $table->string('worship_leader')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
