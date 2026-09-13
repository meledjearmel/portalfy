<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Un voucher généré par l'admin n'a pas de commande : order_id devient
     * optionnel, et package_id porte directement le forfait dans ce cas
     * (ProvisionHotspotAccountAction continue de le renseigner aussi pour un
     * compte issu d'une commande, pour que le reste du code puisse lire
     * `$account->package` de la même façon quelle que soit l'origine).
     */
    public function up(): void
    {
        Schema::table('hotspot_accounts', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->change();
            $table->foreignId('package_id')->nullable()->after('order_id')->constrained()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotspot_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_id');
            $table->foreignId('order_id')->nullable(false)->change();
        });
    }
};
