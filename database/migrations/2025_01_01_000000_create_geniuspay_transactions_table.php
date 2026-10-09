<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('geniuspay.transactions_table', 'geniuspay_transactions'), function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique()->index();
            $table->string('product_reference')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('XOF');
            $table->decimal('fees', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->string('status')->default('pending')->index();
            $table->string('payment_method')->nullable();
            $table->string('gateway')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->json('metadata')->nullable();
            $table->string('environment')->default('sandbox');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            // Colonnes pour la relation polymorphe (trait HasGeniusPayPayments)
            // Permettent de lier une transaction à n'importe quel modèle (User, Order, etc.)
            $table->nullableMorphs('payable');

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('customer_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('geniuspay.transactions_table', 'geniuspay_transactions'));
    }
};
