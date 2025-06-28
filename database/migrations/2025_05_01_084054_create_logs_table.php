<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ActionType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'id');
            $table->string('username');
            $table->string('session_id');
            $table->string('ip_address');
            $table->text('user_agent');
            $table->enum('action_type', ActionType::all());
            $table->string('entity_type');
            $table->string('entity_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('additional_details')->nullable();
            $table->enum('status', ['SUCCESS', 'FAILURE', 'WARNING']);
            $table->text('error_message')->nullable();
            $table->dateTime('performed_at');
            $table->text('request_url');
            $table->string('request_method');
            $table->text('referrer_url')->nullable();
            $table->integer('duration')->nullable();
            $table->boolean('system_event')->default(false);
            $table->timestamp('created_at');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};