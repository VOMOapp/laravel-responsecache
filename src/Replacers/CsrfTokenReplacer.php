<?php

namespace Spatie\ResponseCache\Replacers;

use Symfony\Component\HttpFoundation\Response;

class CsrfTokenReplacer implements Replacer
{
    protected $replacedBy = '<csrf-token-here>';

    public function transformInitialResponse(Response $response): void
    {
        if (! $response->getContent()) {
            return;
        }

        $response->setContent(str_replace(
            csrf_token(),
            $this->replacedBy,
            $response->getContent()
        ));
    }

    public function replaceCachedResponse(Response $response): void
    {
        if (! $response->getContent()) {
            return;
        }

        $response->setContent(str_replace(
            $this->replacedBy,
            csrf_token(),
            $response->getContent()
        ));
    }
}