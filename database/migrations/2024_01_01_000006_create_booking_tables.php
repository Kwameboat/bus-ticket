<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 50)->unique();
            $table->enum('type', ['flat','percentage'])->default('flat');
            $table->decimal('value', 10, 2);
            $table->decimal('min_fare', 10, 2)->default(0.00);
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedTinyInteger('max_per_user')->default(1);
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_to')->nullable();
            $table->enum('status', ['active','inactive','expired'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_ref', 20)->unique();
            $table->string('invoice_number', 30)->unique()->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('boarding_point_id')->constrained('terminals')->cascadeOnDelete();
            $table->foreignId('dropoff_point_id')->constrained('terminals')->cascadeOnDelete();
            $table->foreignId('promo_code_id')->nullable()->constrained('promo_codes')->nullOnDelete();
            $table->unsignedTinyInteger('seat_count')->default(1);
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('service_charge', 10, 2)->default(0.00);
            $table->decimal('grand_total', 10, 2);
            $table->string('currency', 5)->default('GHS');
            $table->enum('payment_status', ['pending','paid','failed','refunded','partial_refund'])->default('pending');
            $table->enum('booking_status', [
                'pending','confirmed','cancelled','completed','no_show','rescheduled'
            ])->default('pending');
            $table->string('payment_method', 50)->nullable();
            $table->dateTime('expires_at')->nullable()->comment('Unpaid booking expiry');
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->boolean('is_reschedule')->default(false);
            $table->foreignId('original_booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id','booking_status']);
            $table->index(['trip_id','payment_status']);
            $table->index('booking_ref');
        });

        Schema::create('booking_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_seat_id')->constrained()->cascadeOnDelete();
            $table->string('seat_label', 20);
            $table->string('passenger_name', 200);
            $table->string('passenger_phone', 20)->nullable();
            $table->string('passenger_id_type', 50)->nullable();
            $table->string('passenger_id_number', 100)->nullable();
            $table->decimal('fare', 10, 2);
            $table->boolean('is_primary_passenger')->default(false);
            $table->timestamps();
            $table->index(['booking_id']);
        });

        Schema::create('cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 500)->nullable();
            $table->enum('cancelled_by', ['passenger','operator','admin','system'])->default('passenger');
            $table->boolean('refund_eligible')->default(false);
            $table->decimal('refund_amount', 10, 2)->default(0.00);
            $table->enum('status', ['pending','approved','rejected'])->default('pending');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reschedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('old_trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('new_trip_id')->constrained('trips')->cascadeOnDelete();
            $table->decimal('fare_difference', 10, 2)->default(0.00);
            $table->enum('status', ['pending','confirmed','rejected'])->default('confirmed');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('reschedules');
        Schema::dropIfExists('cancellations');
        Schema::dropIfExists('booking_seats');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('promo_codes');
    }
};
