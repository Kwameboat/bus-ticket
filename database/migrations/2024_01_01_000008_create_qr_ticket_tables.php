<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('qr_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_seat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_number', 50)->unique();
            $table->string('qr_token', 500)->unique()->comment('HMAC-signed token for QR encoding');
            $table->string('qr_payload_hash', 64)->comment('SHA256 of payload for tamper detection');
            $table->string('seat_label', 20);
            $table->string('passenger_name', 200);
            $table->enum('status', ['generated','valid','used','cancelled','expired','refunded'])->default('generated');
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_until')->nullable();
            $table->dateTime('used_at')->nullable();
            $table->dateTime('generated_at');
            $table->string('qr_image_path')->nullable()->comment('Cached QR image file path');
            $table->timestamps();
            $table->index(['ticket_number']);
            $table->index(['trip_id','status']);
            $table->index(['booking_id']);
        });

        Schema::create('ticket_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scanned_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->enum('scan_method', ['qr_camera','qr_manual','manual_override'])->default('qr_camera');
            $table->enum('scan_result', ['valid','already_used','cancelled','refunded','wrong_trip','expired','invalid'])->default('valid');
            $table->string('device_info', 300)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('boarding_granted')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['qr_ticket_id','scan_result']);
            $table->index(['trip_id','scanned_by']);
        });

        Schema::create('boarding_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conductor_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('closed_at')->nullable();
            $table->unsignedSmallInteger('total_scanned')->default(0);
            $table->unsignedSmallInteger('total_boarded')->default(0);
            $table->unsignedSmallInteger('invalid_scans')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['trip_id','conductor_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('boarding_sessions');
        Schema::dropIfExists('ticket_scans');
        Schema::dropIfExists('qr_tickets');
    }
};
