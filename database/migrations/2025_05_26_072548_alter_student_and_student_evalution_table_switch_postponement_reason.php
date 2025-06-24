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
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('is_postponed');
            $table->dropColumn('postponement_reason');
        });

        Schema::table('student_evaluations', function (Blueprint $table) {
            $table->boolean('is_postponed')->default(false)->after('locked_at');
            $table->text('postponement_reason')->nullable()->after('is_postponed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_evaluations', function (Blueprint $table) {
            $table->dropColumn('postponement_reason');
            $table->dropColumn('is_postponed');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->boolean('is_postponed')->default(false)->after('evaluation_type');
            $table->text('postponement_reason')->nullable()->after('is_postponed');
        });
    }
};
