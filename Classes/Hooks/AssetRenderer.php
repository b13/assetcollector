<?php
declare(strict_types = 1);
namespace B13\Assetcollector\Hooks;

/*
 * This file is part of TYPO3 CMS-based extension "assetcollector" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use B13\Assetcollector\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Hooks into PageRenderer to add CSS / JS / SVG files
 */
class AssetRenderer implements SingletonInterface
{

    /**
     * Called via PageRenderer->render-postProcess(), all our includes are added to "headerData", this is called
     * for cacheable and non-cacheable logic.
     *
     * @param $params
     * @param PageRenderer $pageRenderer
     */
    public function insertAssets($params, PageRenderer $pageRenderer): void
    {
        $typoScriptFrontendController = $this->getTypoScriptFrontendController();
        if ($typoScriptFrontendController instanceof TypoScriptFrontendController) {
            $this->collectInlineAssets($params, $typoScriptFrontendController);
            $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
            $cached = $this->getTypoScriptFrontendController()->config['b13/assetcollector'];
            if (!empty($cached['cssFiles']) && is_array($cached['cssFiles'])) {
                $assetCollector->mergeCssFiles($cached['cssFiles']);
            }
            if (!empty($cached['inlineCss']) && is_array($cached['inlineCss'])) {
                $assetCollector->mergeInlineCss($cached['inlineCss']);
            }
            if (!empty($cached['jsFiles']) && is_array($cached['jsFiles'])) {
                foreach ($cached['jsFiles'] as $data) {
                    $assetCollector->addJavaScriptFile($data['fileName'], $data['additionalAttributes']);
                }
            }
            $params['headerData'] = array_merge(
                $params['headerData'],
                [$assetCollector->buildInlineCssTag(), $assetCollector->buildJavaScriptIncludes()]
            );
        }
    }

    /**
     * Called via insertAssets() - this means, this is run at any time, even when "fully cached" or
     * with USER_INT* objects on it.
     *
     * At this point, we're re-evaluating all included files from the cached information plus adding
     * new information.
     * Then, the full information is again stored in the "b13/assetcollector" bucket.
     *
     * @param array $params
     * @param TypoScriptFrontendController $frontendController
     */
    public function collectInlineAssets($params, TypoScriptFrontendController $frontendController): void
    {
        $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
        $cached = $frontendController->config['b13/assetcollector'];

        // Add individual registered JS files
        foreach ($frontendController->pSetup['jsFiles.'] as $key => $jsFile)
        {
            if (is_array($jsFile)) {
                continue;
            }
            $additionalAttributes = $frontendController->pSetup['jsFiles.'][$key . '.'] ?? [];
            $assetCollector->addJavaScriptFile($jsFile, $additionalAttributes);
        }
        if (!empty($cached['jsFiles']) && is_array($cached['jsFiles'])) {
            foreach ($cached['jsFiles'] as $data) {
                $assetCollector->addJavaScriptFile($data['fileName'], $data['additionalAttributes']);
            }
        }
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
            'jsFiles' => $assetCollector->getJavaScriptFiles(),
            'cssFiles' => $assetCollector->getUniqueCssFiles(),
            'inlineCss' => $assetCollector->getUniqueInlineCss(),
            'xmlFiles' => $assetCollector->getUniqueXmlFiles()
        ];
        $frontendController->config['b13/assetcollector'] = $cached;
    }

    /**
     * Hook to add all external CSS files specified in assetcollector view helpers to page renderer.
     * Called via PageRenderer->render-preProcess().
     *
     * @param $params
     * @param PageRenderer $pageRenderer
     */
    public function collectCssFiles($params, PageRenderer $pageRenderer): void
    {
        $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
        foreach ($assetCollector->getExternalCssFiles() as $cssFile) {
            $pageRenderer->addCssFile($cssFile['fileName'], 'stylesheet', $cssFile['mediaType'], '', false, false, '', true);
        }
    }

    protected function getTypoScriptFrontendController(): ?TypoScriptFrontendController
    {
        if ($GLOBALS['TSFE'] instanceof TyposcriptFrontendController) {
            return $GLOBALS['TSFE'];
        } else {
            return null;
        }
    }
}
