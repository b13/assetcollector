<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']['b13/assetcollector'] = \B13\Assetcollector\Hooks\AssetRenderer::class . '->collectCssFiles';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess']['b13/assetcollector'] = \B13\Assetcollector\Hooks\AssetRenderer::class . '->insertAssets';

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_assetcollector'] ?? null)) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_assetcollector'] = ['groups' => ['pages']];
}

// Holds the icon registry (identifier => SVG file) so the inline SVG sprite can
// be rebuilt for cached pages even when the page-cache-bound "tx_assetcollector"
// cache is gone. Lives in the "system" group on purpose: it must survive a
// frontend ("pages") cache flush, otherwise it could vanish together with the
// very entry it is meant to recover from.
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_assetcollector_registry'] ?? null)) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_assetcollector_registry'] = ['groups' => ['system']];
}
