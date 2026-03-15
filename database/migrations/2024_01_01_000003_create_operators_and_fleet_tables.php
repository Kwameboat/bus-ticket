<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('operators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            $table->string('logo')->nullable();
            $table->string('reg_number', 100)->nullable()->unique();
            $table->string('address', 500)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 200)->nullable();
            $table->decimal('commission_rate', 5, 2)->default(10.00)->comment('% platform takes');
            $table->text('description')->nullable();
            $table->enum('status', ['active','inactive','suspended'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('buses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('reg_number', 100)->unique();
            $table->string('plate_number', 50)->nullable();
            $table->string('bus_type', 100)->comment('Mini,Standard,Executive,VIP');
            $table->unsignedSmallInteger('capacity')->default(30);
            $table->json('amenities')->nullable()->comment('AC,WiFi,Charging,TV,Toilet');
            $table->string('color', 50)->nullable();
            $table->string('make', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->year('year')->nullable();
            $table->string('image')->nullable();
            $table->enum('status', ['active','maintenance','inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('seat_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->unsignedTinyInteger('rows')->default(8);
            $table->unsignedTinyInteger('cols')->default(4);
            $table->json('layout')->comment('2D grid: null=aisle, 0=no seat, seat_label=string');
            $table->json('special_seats')->nullable()->comment('driver,conductor positions');
            $table->boolean('is_default')->default(true);
            $table->timestamps();
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('license_number', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('photo')->nullable();
            $table->string('id_number', 100)->nullable();
            $table->date('license_expiry')->nullable();
            $table->enum('status', ['active','inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('conductors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 200);
            $table->string('staff_id', 100)->nullable()->unique();
            $table->string('phone', 20)->nullable();
            $table->string('photo')->nullable();
            $table->enum('status', ['active','inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('conductors');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('seat_plans');
        Schema::dropIfExists('buses');
        Schema::dropIfExists('operators');
    }
};
