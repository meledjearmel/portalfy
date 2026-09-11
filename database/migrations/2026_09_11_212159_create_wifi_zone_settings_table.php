<?php

use App\Enums\CredentialMode;
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
        Schema::create('wifi_zone_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('RapidNet Wifi Zone');
            $table->string('slogan')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('color_primary')->default('#0F9D8C');
            $table->string('color_secondary')->default('#0B2B26');
            $table->string('color_accent')->default('#0F9D8C');
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('opening_hours')->nullable();
            $table->json('social_links')->nullable();
            $table->text('terms')->nullable();
            $table->text('privacy_policy')->nullable();
            $table->string('credential_mode')->default(CredentialMode::Unique->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wifi_zone_settings');
    }
};
