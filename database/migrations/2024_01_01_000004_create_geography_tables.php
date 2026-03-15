<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('region', 100)->nullable();
            $table->string('slug', 120)->unique();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->boolean('is_popular')->default(false);
            $table->enum('status', ['active','inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('terminals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 200);
            $table->string('address', 500)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('phone', 20)->nullable();
            $table->boolean('is_main_terminal')->default(false);
            $table->enum('status', ['active','inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_city_id')->constrained('cities')->cascadeOnDelete();
            $table->foreignId('destination_city_id')->constrained('cities')->cascadeOnDelete();
            $table->foreignId('origin_terminal_id')->nullable()->constrained('terminals')->nullOnDelete();
            $table->foreignId('destination_terminal_id')->nullable()->constrained('terminals')->nullOnDelete();
            $table->string('name', 300);
            $table->string('slug', 320)->unique();
            $table->unsignedSmallInteger('distance_km')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active','inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['origin_city_id','destination_city_id','status']);
        });

        Schema::create('route_boarding_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('terminal_id')->constrained()->cascadeOnDelete();
            $table->enum('point_type', ['boarding','dropoff','both'])->default('both');
            $table->unsignedTinyInteger('stop_order')->default(0);
            $table->decimal('fare_modifier', 5, 2)->default(0.00)->comment('% add/subtract from base fare');
            $table->boolean('is_origin')->default(false);
            $table->boolean('is_destination')->default(false);
            $table->timestamps();
            $table->unique(['route_id','terminal_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('route_boarding_points');
        Schema::dropIfExists('routes');
        Schema::dropIfExists('terminals');
        Schema::dropIfExists('cities');
    }
};
