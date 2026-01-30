<?php

namespace App\Http\Middleware;

use App\Models\Tentacle;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class TentacleResolver
{
    /**
     * Handle an incoming request.
     *
     * Resolves the current tentacle from:
     * - TENTACLE_ID env variable (development)
     * - Domain mapping (production - TODO)
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tentacleId = $this->resolveTentacleId($request);

        if (! $tentacleId) {
            abort(404, 'Tentacle not configured');
        }

        $tentacle = Tentacle::find($tentacleId);

        if (! $tentacle) {
            abort(404, 'Tentacle not found');
        }

        // Store in request attributes for controllers
        $request->attributes->set('tentacle', $tentacle);
        $request->attributes->set('tentacle_id', $tentacle->id);

        // Share with all views
        View::share('tentacle', $tentacle);
        View::share('tentacle_id', $tentacle->id);

        return $next($request);
    }

    /**
     * Resolve tentacle ID from environment or domain.
     */
    protected function resolveTentacleId(Request $request): ?int
    {
        // Development: use TENTACLE_ID from .env
        if ($envId = config('app.tentacle_id')) {
            return (int) $envId;
        }

        // Production: could resolve from domain
        // TODO: implement domain-to-tentacle mapping
        // $host = $request->getHost();
        // return Tentacle::where('domain', $host)->value('id');

        return null;
    }
}
