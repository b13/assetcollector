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
 * Hooks into PageRenderer to add CSS / SVG files
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

    protected function getTypoScriptFrontendController(): ?TypoScriptFrontendController
    {
        if ($GLOBALS['TSFE'] instanceof TyposcriptFrontendController) {
            return $GLOBALS['TSFE'];
        } else {
            return null;
        }
    }
}
