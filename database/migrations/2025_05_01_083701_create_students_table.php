<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\EvaluationType;
use App\Enums\Department;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('matric_number')->unique();
            $table->string('name');
            $table->string('email');
            $table->foreignId('program_id')->constrained('programs', 'id');
            $table->string('current_semester');
            $table->enum('department', Department::all());
            $table->foreignId('main_supervisor_id')->constrained('lecturers', 'id');
            $table->enum('evaluation_type', EvaluationType::all());
            $table->text('research_title')->nullable();
            $table->boolean('is_postponed')->default(false);
            $table->text('postponement_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};