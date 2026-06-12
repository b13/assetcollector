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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\Stream;

/**
 * Middleware to add inline SVGs at the end of the HTML <body> tag.
 */
class InlineSvgInjector implements MiddlewareInterface
{
    public function __construct(
        #[Autowire(service: 'cache.tx_assetcollector')]
        private readonly FrontendInterface $cache,
        private readonly AssetCollector $assetCollector,
        #[Autowire(service: 'cache.tx_assetcollector_registry')]
        private readonly FrontendInterface $registryCache
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if ($response instanceof NullResponse) {
            return $response;
        }
        $body = $response->getBody();
        $body->rewind();
        $contents = $body->getContents();
        $svgAsset = $this->getInlineSvgAsset($request, $contents);
        if ($svgAsset !== '') {
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

    protected function getInlineSvgAsset(ServerRequestInterface $request, string $responseBody): string
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
        // Self-heal: re-resolve any icon referenced in the page that is not (or
        // no longer) collected, so a desynced "tx_assetcollector" cache can never
        // result in a page being delivered with a missing/incomplete SVG sprite.
        $cacheIdentifier = 'icons-' . ($request->getAttribute('site')?->getIdentifier() ?? 'default');
        // In uncached scope the TypoScript-backed registry is available – (re)persist
        // it so it can be used as a fallback for cached requests, where TypoScript
        // (and therefore the registry) is no longer available.
        $liveRegistry = $this->assetCollector->getIconRegistry();
        if ($liveRegistry !== []) {
            $this->registryCache->set($cacheIdentifier, $liveRegistry);
        }
        $this->assetCollector->addReferencedIcons(
            $responseBody,
            fn (): array => $liveRegistry !== [] ? $liveRegistry : $this->readPersistedRegistry($cacheIdentifier)
        );
        return $this->assetCollector->buildInlineXmlTag();
    }

    /**
     * @return array<string, string>
     */
    private function readPersistedRegistry(string $cacheIdentifier): array
    {
        $persisted = $this->registryCache->has($cacheIdentifier) ? $this->registryCache->get($cacheIdentifier) : null;
        return is_array($persisted) ? $persisted : [];
    }
}
