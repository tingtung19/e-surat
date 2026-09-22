<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('letters', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->enum('type', ['internal', 'official', 'external']);
            $table->string('number')->nullable()->unique();
            $table->foreignId('category_id')->nullable()->constrained('letter_categories')->nullOnDelete();
            $table->foreignId('sender_division_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_division_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('external_sender')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letters');
    }
};
