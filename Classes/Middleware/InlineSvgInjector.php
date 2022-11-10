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
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Middleware to add inline SVGs at the end of the HTML <body> tag.
 */
class InlineSvgInjector implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if ($this->isOutputting($response)) {
            $svgAsset = $this->getInlineSvgAsset();
            if ($svgAsset !== '') {
                $body = $response->getBody();
                $body->rewind();
                $contents = $response->getBody()->getContents();
                if (strpos($contents, '</body>') !== false) {
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
        }
        return $response;
    }

    protected function isOutputting(ResponseInterface $response): bool
    {
        if ($response instanceof NullResponse) {
            return false;
        }
        $controller = $this->getTypoScriptFrontendController();
        if (!($controller instanceof TypoScriptFrontendController)) {
            return false;
        }
        // Once support for TYPO3 v10 is dropped, this condition can be removed.
        if (method_exists($controller, 'isOutputting')) {
            return $controller->isOutputting();
        }
        return true;
    }

    protected function getInlineSvgAsset(): string
    {
        $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
        $cached = $this->getTypoScriptFrontendController()->config['b13/assetcollector'] ?? [];
        if (!empty($cached['xmlFiles'] ?? null) && is_array($cached['xmlFiles'])) {
            $assetCollector->mergeXmlFiles($cached['xmlFiles']);
        }
        return $assetCollector->buildInlineXmlTag();
    }

    protected function getTypoScriptFrontendController(): ?TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'] ?? null;
    }
}
