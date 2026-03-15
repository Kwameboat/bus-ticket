<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 100)->default('general');
            $table->string('key', 200)->unique();
            $table->text('value')->nullable();
            $table->string('cast_type', 50)->default('string')->comment('string,bool,int,json,array');
            $table->string('label', 300)->nullable();
            $table->string('description', 500)->nullable();
            $table->boolean('is_public')->default(false)->comment('Expose to frontend JS');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->string('model_type', 200)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url', 1000)->nullable();
            $table->timestamps();
            $table->index(['model_type','model_id']);
            $table->index(['user_id','action']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('settings');
    }
};
