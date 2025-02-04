<?php

namespace Spatie\ResponseCache\Replacers;

use Symfony\Component\HttpFoundation\Response;

interface Replacer
{
    /*
     * Transform the initial response before it gets cached.
     */
    public function transformInitialResponse(Response $response): void;

    /*
     * Replace any data you want in the cached response before it gets sent.
     */
    public function replaceCachedResponse(Response $response): void;
}