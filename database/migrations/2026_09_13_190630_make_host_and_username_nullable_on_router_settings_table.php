<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Plus de valeur par défaut issue de .env pour host/username (voir
     * RouterSetting::current()) : la ligne singleton doit pouvoir exister
     * vide tant que l'admin n'a pas configuré son routeur.
     */
    public function up(): void
    {
        Schema::table('router_settings', function (Blueprint $table) {
            $table->string('host')->nullable()->change();
            $table->string('username')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('router_settings', function (Blueprint $table) {
            $table->string('host')->nullable(false)->change();
            $table->string('username')->nullable(false)->change();
        });
    }
};
