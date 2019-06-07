<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all']['b13/assetcollector'] = \B13\Assetcollector\Hooks\AssetRenderer::class . '->collectInlineAssets';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess']['b13/assetcollector'] = \B13\Assetcollector\Hooks\AssetRenderer::class . '->insertInlineAssets';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess']['b13/assetcollector-inlinesvgmap'] = \B13\Assetcollector\Hooks\AssetRenderer::class . '->insertInlineSvgMap';

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['ac'][] = 'B13\Assetcollector\ViewHelpers';
