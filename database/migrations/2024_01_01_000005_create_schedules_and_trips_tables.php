<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('conductor_id')->nullable()->constrained()->nullOnDelete();
            $table->time('departure_time');
            $table->time('arrival_time');
            $table->enum('recurrence_type', ['once','daily','weekly','custom'])->default('daily');
            $table->json('recurrence_days')->nullable()->comment('[0,1,2,3,4,5,6] Sun-Sat');
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->boolean('waitlist_enabled')->default(true);
            $table->enum('status', ['active','inactive','cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['route_id','status']);
        });

        Schema::create('fares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('boarding_point_id')->constrained('terminals')->cascadeOnDelete();
            $table->foreignId('dropoff_point_id')->constrained('terminals')->cascadeOnDelete();
            $table->decimal('base_fare', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('service_charge', 10, 2)->default(0.00);
            $table->string('currency', 5)->default('GHS');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['schedule_id','boarding_point_id','dropoff_point_id']);
        });

        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('conductor_id')->nullable()->constrained()->nullOnDelete();
            $table->date('trip_date');
            $table->dateTime('departs_at');
            $table->dateTime('arrives_at');
            $table->enum('status', [
                'scheduled','boarding','in_transit','arrived','cancelled','delayed'
            ])->default('scheduled');
            $table->unsignedSmallInteger('available_seats')->default(0);
            $table->unsignedSmallInteger('boarded_count')->default(0);
            $table->unsignedSmallInteger('total_seats')->default(0);
            $table->dateTime('actual_departure')->nullable();
            $table->dateTime('actual_arrival')->nullable();
            $table->boolean('waitlist_enabled')->default(true);
            $table->text('cancellation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['route_id','trip_date','status']);
            $table->index(['departs_at','status']);
        });

        Schema::create('trip_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('seat_number', 10);
            $table->string('seat_label', 20);
            $table->enum('status', ['available','locked','booked'])->default('available');
            $table->string('locked_by_session', 200)->nullable();
            $table->foreignId('locked_by_user')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('locked_until')->nullable();
            $table->timestamps();
            $table->unique(['trip_id','seat_number']);
            $table->index(['trip_id','status']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('trip_seats');
        Schema::dropIfExists('trips');
        Schema::dropIfExists('fares');
        Schema::dropIfExists('schedules');
    }
};
