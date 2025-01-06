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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->enum('type', ['topics', 'sources', 'authors']);
            $table->string('value');
            $table->timestamps();
        
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'type', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::dropIfExists('user_preferences');
    }
};
