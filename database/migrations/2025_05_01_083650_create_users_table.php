<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\Department;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('staff_number')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('department', Department::all());
            $table->foreignId('lecturer_id')->nullable()->constrained('lecturers', 'id');
            $table->dateTime('last_login')->nullable();
            $table->string('password_reset_token')->nullable();
            $table->dateTime('password_reset_expiry')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_password_updated')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};