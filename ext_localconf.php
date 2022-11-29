<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}
if ((\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class))->getMajorVersion() < 12) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all']['b13/assetcollector'] = \B13\Assetcollector\Hooks\AssetRenderer::class . '->collectInlineAssets';
}
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']['b13/assetcollector'] = \B13\Assetcollector\Hooks\AssetRenderer::class . '->collectCssFiles';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess']['b13/assetcollector'] = \B13\Assetcollector\Hooks\AssetRenderer::class . '->insertAssets';
