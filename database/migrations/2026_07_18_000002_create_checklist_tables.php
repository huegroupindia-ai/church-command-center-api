<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('church_id')->nullable();
            $table->unsignedBigInteger('department_id')->default(1);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->default('general');
            $table->boolean('is_global')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('checklist_template_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('verification_type')->default('none');
            $table->boolean('is_required')->default(true);
            $table->integer('sort_order')->default(0);
            $table->integer('estimated_minutes')->nullable();
            $table->timestamps();
        });

        Schema::create('service_checklists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('department_id')->default(1);
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('service_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('checklist_id');
            $table->unsignedBigInteger('template_item_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('verification_type')->default('none');
            $table->boolean('is_required')->default(true);
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('evidence', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('checklist_item_id');
            $table->unsignedBigInteger('user_id');
            $table->string('type')->default('photo');
            $table->string('file_path');
            $table->string('file_name');
            $table->integer('file_size')->default(0);
            $table->string('mime_type')->default('image/jpeg');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence');
        Schema::dropIfExists('service_checklist_items');
        Schema::dropIfExists('service_checklists');
        Schema::dropIfExists('checklist_template_items');
        Schema::dropIfExists('checklist_templates');
    }
};
