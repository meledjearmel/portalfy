<?php

namespace App\Http\Middleware;

use App\Models\RouterSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRouterIsConfigured
{
    /**
     * Bloque l'accès au reste de l'administration tant qu'aucun routeur
     * MikroTik n'a été enregistré : les forfaits, comptes Hotspot et
     * paramètres n'ont de sens qu'une fois un routeur configuré, et il n'y a
     * plus de valeurs par défaut issues de .env pour "faire semblant" que
     * c'est le cas.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! RouterSetting::current()->isConfigured()) {
            return redirect()->route('admin.router.index');
        }

        return $next($request);
    }
}
