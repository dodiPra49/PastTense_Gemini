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
        Schema::create('quiz_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('participant_name');
            $table->unsignedInteger('total_questions')->default(10);
            $table->unsignedInteger('correct_answers')->default(0);
            $table->decimal('final_score', 5, 2)->default(0);
            $table->string('status')->default('in_progress'); // in_progress, completed
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_session_id')->constrained('quiz_sessions')->onDelete('cascade');
            $table->string('topic'); // Simple Past Tense, Simple Perfect Tense
            $table->text('question_text');
            $table->text('option_a');
            $table->text('option_b');
            $table->text('option_c');
            $table->text('option_d');
            $table->string('correct_answer', 5); // A, B, C, D
            $table->text('explanation')->nullable();
            $table->timestamps();
        });

        Schema::create('user_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_session_id')->constrained('quiz_sessions')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
            $table->string('selected_answer', 5)->nullable(); // A, B, C, D
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_answers');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('quiz_sessions');
    }
};
