<?php

namespace App\Http\Middleware;

use App\Actions\CaptureHotspotContextAction;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Le routeur peut renvoyer le visiteur sur n'importe quelle page du portail
 * avec ses paramètres (mac, ip, link-login, link-orig) : on les garde en
 * session dès qu'ils apparaissent, pour toute la suite de la navigation.
 */
class CaptureHotspotContext
{
    public function __construct(private CaptureHotspotContextAction $captureHotspotContext) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->query->has('link-login')) {
            $this->captureHotspotContext->handle($request);
        }

        return $next($request);
    }
}
