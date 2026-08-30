<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('donor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('donor_name')->nullable();
            $table->string('donor_email')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('type', ['tithe', 'offering', 'building_fund', 'missions', 'benevolence', 'youth', 'other'])->default('tithe');
            $table->enum('method', ['cash', 'check', 'card', 'bank_transfer', 'online', 'other'])->default('cash');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_frequency')->nullable(); // weekly, biweekly, monthly
            $table->boolean('is_tax_deductible')->default(true);
            $table->timestamp('donated_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['church_id', 'donated_at']);
            $table->index(['church_id', 'type']);
            $table->index(['donor_id', 'donated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
