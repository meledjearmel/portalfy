<?php

namespace App\Http\Controllers;

use App\Models\HotspotAccount;
use App\Models\WifiZoneSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintHotspotAccountsController extends Controller
{
    /**
     * Planche de tickets à découper pour les codes créés par l'admin.
     * Les codes achetés en ligne sont exclus : ils appartiennent déjà à un
     * client et n'ont pas vocation à être revendus sur papier.
     */
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'max:200'],
            'ids.*' => ['integer'],
        ]);

        $accounts = HotspotAccount::query()
            ->with('package')
            ->whereNull('order_id')
            ->whereIn('id', $validated['ids'])
            ->orderBy('id')
            ->get();

        abort_if($accounts->isEmpty(), 404);

        return view('admin.print-hotspot-accounts', [
            'accounts' => $accounts,
            'zone' => WifiZoneSetting::current(),
        ]);
    }
}
