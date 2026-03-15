<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 5)->default('GHS');
            $table->string('method', 50)->comment('card,momo,wallet,ussd');
            $table->string('gateway', 50)->comment('paystack,wallet,mtn_momo');
            $table->string('gateway_ref', 200)->nullable()->unique();
            $table->string('gateway_access_code', 200)->nullable();
            $table->enum('status', ['pending','success','failed','abandoned'])->default('pending');
            $table->json('webhook_payload')->nullable();
            $table->json('verification_response')->nullable();
            $table->json('meta')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();
            $table->index(['booking_id','status']);
            $table->index(['gateway_ref']);
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 5)->default('GHS');
            $table->enum('method', ['wallet','bank_transfer','original_method'])->default('wallet');
            $table->enum('status', ['pending','approved','rejected','processed'])->default('pending');
            $table->string('gateway_refund_ref', 200)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->string('currency', 5)->default('GHS');
            $table->enum('status', ['active','frozen','closed'])->default('active');
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['credit','debit'])->default('credit');
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_before', 12, 2)->default(0.00);
            $table->decimal('balance_after', 12, 2)->default(0.00);
            $table->string('reference', 200)->unique();
            $table->string('description', 500);
            $table->string('source', 100)->nullable()->comment('booking,refund,fund,admin_credit');
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending','completed','failed','reversed'])->default('completed');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['wallet_id','type']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
    }
};
