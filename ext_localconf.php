<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']['b13/assetcollector'] = \B13\Assetcollector\Hooks\AssetRenderer::class . '->collectCssFiles';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess']['b13/assetcollector'] = \B13\Assetcollector\Hooks\AssetRenderer::class . '->insertAssets';

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_assetcollector'] ?? null)) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_assetcollector'] = ['groups' => ['pages']];
}
