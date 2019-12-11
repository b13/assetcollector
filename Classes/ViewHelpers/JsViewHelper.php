<?php
declare(strict_types = 1);
namespace B13\Assetcollector\ViewHelpers;

/*
 * This file is part of TYPO3 CMS-based extension "assetcollector" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use B13\Assetcollector\AssetCollector;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class JsViewHelper extends AbstractViewHelper
{

    /**
     * @var AssetCollector
     */
    protected $assetCollector;

    /**
     * @param AssetCollector $assetCollector
     */
    public function injectAssetCollector(AssetCollector $assetCollector): void
    {
        $this->assetCollector = $assetCollector;
    }

    /**
     * @return void
     * @api
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument(
            'file',
            'string',
            'JavaScript file name',
            false
        );
        $this->registerArgument(
            'additionalAttributes',
            'array',
            '',
            false,
            []
        );
    }

    /**
     * @return void
     */
    public function render(): void
    {
        if (!empty($this->arguments['file'])) {
            $this->assetCollector->addJavaScriptFile($this->arguments['file'], $this->arguments['additionalAttributes']);
        } else {
            // @todo
            // $this->assetCollector->addInlineJavaScript($this->renderChildren());
        }
    }
}
