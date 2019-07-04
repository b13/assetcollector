<?php
namespace B13\Assetcollector\Middleware;

/***************************************************************
 * *  Copyright notice - MIT License (MIT)
 *
 *  (c) 2019 b13 GmbH,
 *        David Steeb <david.steeb@b13.com>
 *  All rights reserved
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 ***************************************************************/


use B13\Assetcollector\AssetCollector;
use Psr\Http\Server\MiddlewareInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\Stream;

/**
 * Class AssetRenderer
 * @package B13\Assetcollector
 */
class AssetRenderer implements MiddlewareInterface
{

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if (
            !($response instanceof NullResponse)
            && $this->getTypoScriptFrontendController() instanceof TypoScriptFrontendController
            && $this->getTypoScriptFrontendController()->isOutputting()
        ) {

            $svgAsset = $this->getInlineSvgAsset();
            if ($svgAsset !== '') {
                $body = $response->getBody();
                $body->rewind();
                $contents = $response->getBody()->getContents();
                $content = str_ireplace(
                    '</body>',
                    $svgAsset . '</body>',
                    $contents
                );
                $body = new Stream('php://temp', 'rw');
                $body->write($content);
                $response = $response->withBody($body);
            }

        }
        return $response;
    }

    /**
     * @return string
     */
    protected function getInlineSvgAsset(): string
    {
        $svgAsset = '';
        if ($this->getTypoScriptFrontendController() instanceof TypoScriptFrontendController) {
            $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
            $cached = $this->getTypoScriptFrontendController()->config['b13/assetcollector'];
            if (!empty($cached['xmlFiles']) && is_array($cached['xmlFiles'])) {
                $assetCollector->mergeXmlFiles($cached['xmlFiles']);
            }
            $svgAsset = $assetCollector->buildInlineXmlTag();
        }
        return $svgAsset;
    }

    /**
     * @return mixed|TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): ?TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

}
