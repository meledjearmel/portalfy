<?php

use App\Enums\CustomerStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            // Pas de contrainte unique() au niveau BDD : les comptes soft-deleted
            // doivent pouvoir libérer leur email/téléphone pour une nouvelle
            // inscription. L'unicité parmi les comptes actifs est appliquée par
            // Rule::unique(Customer::class)->whereNull('deleted_at') côté validation.
            $table->string('email')->index();
            $table->string('phone')->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('status')->default(CustomerStatus::Active->value);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
