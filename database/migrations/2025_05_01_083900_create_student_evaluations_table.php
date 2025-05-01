<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\NominationStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students', 'id');
            $table->enum('nomination_status', NominationStatus::all());
            $table->foreignId('examiner1_id')->nullable()->constrained('lecturers', 'id');
            $table->foreignId('examiner2_id')->nullable()->constrained('lecturers', 'id');
            $table->foreignId('examiner3_id')->nullable()->constrained('lecturers', 'id');
            $table->foreignId('chairperson_id')->nullable()->constrained('lecturers', 'id');
            $table->boolean('is_auto_assigned')->default(false);
            $table->foreignId('nominated_by')->nullable()->constrained('users', 'id');
            $table->dateTime('nominated_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users', 'id');
            $table->dateTime('locked_at')->nullable();
            $table->integer('semester');
            $table->string('academic_year');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_evaluations');
    }
};