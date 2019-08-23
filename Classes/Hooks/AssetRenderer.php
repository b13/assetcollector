<?php
namespace B13\Assetcollector\Hooks;

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
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class AssetRenderer
 * @package B13\Assetcollector
 */
class AssetRenderer implements SingletonInterface
{

    /**
     * @param $params
     * @param PageRenderer $pageRenderer
     */
    public function insertInlineAssets($params, PageRenderer $pageRenderer): void
    {
        if ($this->getTypoScriptFrontendController() instanceof TypoScriptFrontendController) {
            $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
            $cached = $this->getTypoScriptFrontendController()->config['b13/assetcollector'];
            if (!empty($cached['cssFiles']) && is_array($cached['cssFiles'])) {
                $assetCollector->mergeCssFiles($cached['cssFiles']);
            }
            if (!empty($cached['inlineCss']) && is_array($cached['inlineCss'])) {
                $assetCollector->mergeInlineCss($cached['inlineCss']);
            }
            $params['headerData'] = array_merge(
                $params['headerData'],
                [$assetCollector->buildInlineCssTag()]
            );
        }
    }

    /**
     * @param $params
     * @param TypoScriptFrontendController $frontendController
     */
    public function collectInlineAssets($params, TypoScriptFrontendController $frontendController): void
    {
        $cached = $frontendController->config['b13/assetcollector'];
        $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
        if (!empty($cached['cssFiles']) && is_array($cached['cssFiles'])) {
            $assetCollector->mergeCssFiles($cached['cssFiles']);
        }
        if (!empty($cached['inlineCss']) && is_array($cached['inlineCss'])) {
            $assetCollector->mergeInlineCss($cached['inlineCss']);
        }
        if (!empty($cached['xmlFiles']) && is_array($cached['xmlFiles'])) {
            $assetCollector->mergeXmlFiles($cached['xmlFiles']);
        }
        $cached = [
            'cssFiles' => $assetCollector->getUniqueCssFiles(),
            'inlineCss' => $assetCollector->getUniqueInlineCss(),
            'xmlFiles' => $assetCollector->getUniqueXmlFiles()
        ];
        $frontendController->config['b13/assetcollector'] = $cached;
    }

    /**
     * Hook to add all external CSS files specified in assetcollector view helpers to page renderer.
     *
     * @param $params
     * @param PageRenderer $pageRenderer
     */
    public function collectCssFiles($params, PageRenderer $pageRenderer): void
    {
        $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
        foreach ($assetCollector->getUniqueExternalCssFiles() as $cssFile) {
            $pageRenderer->addCssFile($cssFile['fileName'], 'stylesheet', $cssFile['mediaType'], '', false, false, '', true);
        }
    }

    /**
     * @return mixed|TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): ?TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

}
