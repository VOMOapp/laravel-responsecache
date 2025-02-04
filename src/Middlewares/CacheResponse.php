<?php

namespace Spatie\ResponseCache\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Spatie\ResponseCache\ResponseCache;
use Spatie\ResponseCache\Events\CacheMissed;
use Spatie\ResponseCache\Replacers\Replacer;
use Symfony\Component\HttpFoundation\Response;
use Spatie\ResponseCache\Events\ResponseCacheHit;

class CacheResponse
{
    /** @var \Spatie\ResponseCache\ResponseCache */
    protected $responseCache;

    public function __construct(ResponseCache $responseCache)
    {
        $this->responseCache = $responseCache;
    }

    public function handle(Request $request, Closure $next, $lifetimeInMinutes = null): Response
    {
        if ($this->responseCache->enabled($request)) {
            if ($this->responseCache->hasBeenCached($request)) {
                event(new ResponseCacheHit($request));

                $response = $this->responseCache->getCachedResponseFor($request);

                collect(config('responsecache.replacers', []))->map(function ($replacerClass) {
                    return app($replacerClass);
                })->each(function (Replacer $replacer) use ($response) {
                    $replacer->replaceCachedResponse($response);
                });

                return $response;
            }
        }

        $response = $next($request);

        if ($this->responseCache->enabled($request)) {
            if ($this->responseCache->shouldCache($request, $response)) {
                $this->makeReplacementsAndCacheResponse($request, $response, $lifetimeInMinutes);
            }
        }

        event(new CacheMissed($request));

        return $response;
    }

    private function makeReplacementsAndCacheResponse(Request $request, Response $response, $lifetimeInMinutes = null): void
    {
        $cachedResponse = clone $response;
         collect(config('responsecache.replacers', []))->map(function ($replacerClass) {
            return app($replacerClass);
        })->each(function (Replacer $replacer) use ($cachedResponse) {
            $replacer->transformInitialResponse($cachedResponse);
        });
         $this->responseCache->cacheResponse($request, $cachedResponse, $lifetimeInMinutes);
    }
}
