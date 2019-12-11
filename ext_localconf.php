<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']['b13/assetcollector'] = \B13\Assetcollector\Hooks\AssetRenderer::class . '->collectCssFiles';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess']['b13/assetcollector'] = \B13\Assetcollector\Hooks\AssetRenderer::class . '->insertAssets';
