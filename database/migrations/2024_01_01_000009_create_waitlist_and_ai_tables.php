<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('boarding_point_id')->constrained('terminals')->cascadeOnDelete();
            $table->foreignId('dropoff_point_id')->constrained('terminals')->cascadeOnDelete();
            $table->unsignedTinyInteger('seats_requested')->default(1);
            $table->unsignedSmallInteger('position');
            $table->enum('status', ['waiting','notified','claimed','expired','cancelled'])->default('waiting');
            $table->dateTime('notified_at')->nullable();
            $table->dateTime('claim_expires_at')->nullable()->comment('Time limit to claim offered seat');
            $table->dateTime('claimed_at')->nullable();
            $table->timestamps();
            $table->unique(['trip_id','user_id']);
            $table->index(['trip_id','status','position']);
        });

        Schema::create('ai_chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_token', 100)->unique();
            $table->enum('context_type', ['passenger','admin','operator'])->default('passenger');
            $table->unsignedInteger('total_messages')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->dateTime('last_active_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['user_id','context_type']);
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('ai_chat_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['user','assistant','system'])->default('user');
            $table->text('content');
            $table->string('provider', 50)->nullable()->comment('openai,claude,mock');
            $table->string('model', 100)->nullable();
            $table->unsignedInteger('tokens_used')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['session_id','role']);
        });

        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->default('mock')->comment('openai,claude,mock');
            $table->string('model', 100)->default('gpt-4o-mini');
            $table->string('api_key_env', 100)->default('OPENAI_API_KEY')->comment('Env variable name (not the key itself)');
            $table->unsignedSmallInteger('max_tokens')->default(1000);
            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->unsignedTinyInteger('rate_limit_per_hour')->default(20);
            $table->boolean('enabled')->default(false);
            $table->boolean('passenger_chat_enabled')->default(false);
            $table->boolean('admin_insights_enabled')->default(false);
            $table->boolean('seat_recommendations_enabled')->default(false);
            $table->text('passenger_system_prompt')->nullable();
            $table->text('admin_system_prompt')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('ai_settings');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_chat_sessions');
        Schema::dropIfExists('waitlist_entries');
    }
};
