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
 *
 * Fully Cacheable 1st hit
 * 1. insertAssets() (taking all data from the current Singleton)
 * 2. collectInlineAssets() - put them in cache
 *
 * Fully cacheable 2nd hit
 *  -- no hook triggered
 *
 * With COA_INT 1st hit
 * 1. collectInlineAssets() - put them in cache
 * 2. insertAssets() - taking data from cache PLUS from the current assetcollector object (cached + USER_INT), and make it unique.
 *
 * With COA_INT 2nd hit
 * 1. insertAssets() - taking data from the cache PLUS all the the assets added via USER_INTs
 */
class AssetRenderer implements SingletonInterface
{

    /**
     * Called via PageRenderer->render-postProcess(). Get this:
     *
     * On a page with uncacheable parts (COA_INT/USER_INT) this part is executed at ANY time (first hit and cached hit).
     * This one is then called after the contentPostProc hook was called (see below). This means, that
     * the "b13/assetcollector" bucket is filled by contentPostProc already plus all information from the USER_INTs
     * which have modified the assetcollector during runtime of a USER_INT.
     *
     * On a fully-cacheable page, this hook is called BEFORE the contentPostProc hook on the first hit, filling everything
     * from the current request. On the cached hit none of the hooks is called.
     * This means: on a fully cached page, nothing is populated at this point.
     *
     * @param $params
     * @param PageRenderer $pageRenderer
     */
    public function insertAssets($params, PageRenderer $pageRenderer): void
    {
        $frontendController = $this->getTypoScriptFrontendController();
        if ($frontendController instanceof TypoScriptFrontendController) {
            $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
            $cached = $frontendController->config['b13/assetcollector'];
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
            // Add individual registered JS files. Only relevant on first hit, fully cacheable
            // when the cache is not accessed yet.
            foreach ($frontendController->pSetup['jsFiles.'] as $key => $jsFile)
            {
                if (is_array($jsFile)) {
                    continue;
                }
                $additionalAttributes = $frontendController->pSetup['jsFiles.'][$key . '.'] ?? [];
                $assetCollector->addJavaScriptFile($jsFile, $additionalAttributes);
            }
            $params['headerData'] = array_merge(
                $params['headerData'],
                [$assetCollector->buildInlineCssTag(), $assetCollector->buildJavaScriptIncludes()]
            );
        }
    }

    /**
     * Called via contentPostProc-all hook.
     *
     * If a page has uncacheable parts (COA_INT/USER_INT), this hook is called on the first hit, but before
     * the pageRenderer has hit the triggering of everything (see other two hooks). Because the pageRenderer hook
     * is called at a later stage at any time. So at this point, we collect all cacheable information properly and keep
     * it in the cached page information.
     *
     * If a page is fully cacheable (no COA_INT), the hook is called at the very end of the page generation,
     * which means, that pageRenderer has already triggered its hooks and this functionality is not really needed
     * as the pageRenderer hook has already dumped its information.
     *
     * If the page is fully cacheable the hook is not called on a "cached hit".
     *
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
     * @param array $params
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
