<?php

declare(strict_types=1);

namespace B13\Assetcollector\Middleware;

/*
 * This file is part of TYPO3 CMS-based extension "assetcollector" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use B13\Assetcollector\AssetCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\Stream;

/**
 * Middleware to add inline SVGs at the end of the HTML <body> tag.
 */
class InlineSvgInjector implements MiddlewareInterface
{
    public function __construct(private readonly FrontendInterface $cache, private readonly AssetCollector $assetCollector)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if ($response instanceof NullResponse) {
            return $response;
        }
        $svgAsset = $this->getInlineSvgAsset($request);
        if ($svgAsset !== '') {
            $body = $response->getBody();
            $body->rewind();
            $contents = $response->getBody()->getContents();
            if (str_contains($contents, '</body>')) {
                $content = str_ireplace(
                    '</body>',
                    $svgAsset . '</body>',
                    $contents
                );
            } else {
                $content = $contents . $svgAsset;
            }
            $body = new Stream('php://temp', 'rw');
            $body->write($content);
            $response = $response->withBody($body);
        }
        return $response;
    }

    protected function getInlineSvgAsset(ServerRequestInterface $request): string
    {
        /** @var CacheDataCollector $cacheDataCollector */
        $cacheDataCollector = $request->getAttribute('frontend.cache.collector');
        $identifier = $cacheDataCollector->getPageCacheIdentifier();
        $cached = [];
        if ($this->cache->has($identifier)) {
            $cached = $this->cache->get($identifier);
        }
        if (!empty($cached['xmlFiles'] ?? null) && is_array($cached['xmlFiles'])) {
            $this->assetCollector->mergeXmlFiles($cached['xmlFiles']);
        }
        return $this->assetCollector->buildInlineXmlTag();
    }
}
