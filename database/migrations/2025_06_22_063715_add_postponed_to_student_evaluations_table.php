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
        Schema::table('student_evaluations', function (Blueprint $table) {
            $table->dateTime('postponed_to')->nullable()->after('postponement_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_evaluations', function (Blueprint $table) {
            $table->dropColumn('postponed_to');
        });
    }
};
