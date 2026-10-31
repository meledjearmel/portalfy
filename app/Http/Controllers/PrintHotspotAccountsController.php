<?php

namespace App\Http\Controllers;

use App\Models\HotspotAccount;
use App\Models\WifiZoneSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintHotspotAccountsController extends Controller
{
    /**
     * Feuilles A4 de tickets à découper (15 par feuille, 3 × 5), pour un lot
     * entier (`lot`) ou une sélection de codes (`ids`). Seuls les codes créés
     * par l'admin et jamais utilisés sont imprimés : un code acheté en ligne
     * appartient déjà à un client, et un code utilisé, expiré ou suspendu ne
     * doit pas être revendu sur papier.
     */
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'lot' => ['required_without:ids', 'integer'],
            'ids' => ['required_without:lot', 'array', 'max:500'],
            'ids.*' => ['integer'],
        ]);

        $accounts = HotspotAccount::query()
            ->with('package')
            ->unusedVoucher()
            ->when(
                isset($validated['lot']),
                fn ($query) => $query->where('voucher_batch_id', $validated['lot']),
                fn ($query) => $query->whereIn('id', $validated['ids']),
            )
            ->orderBy('id')
            ->get();

        abort_if($accounts->isEmpty(), 404);

        return view('admin.print-hotspot-accounts', [
            'sheets' => $accounts->chunk(15),
            'total' => $accounts->count(),
            'zone' => WifiZoneSetting::current(),
        ]);
    }
}
