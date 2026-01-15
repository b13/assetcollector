<?php

declare(strict_types=1);

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

/**
 * Adding CSS files or CSS inline code from a Fluid template
 */
class CssViewHelper extends AbstractViewHelper
{
    public function __construct(private readonly AssetCollector $assetCollector)
    {
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument(
            'file',
            'string',
            'css file name',
            false
        );
        $this->registerArgument(
            'external',
            'boolean',
            'Specifies if the given CSS file should be loaded within link tag.'
        );
        $this->registerArgument(
            'media',
            'string',
            'Specifies the value for the media attribute. Default is "all".'
        );
    }

    public function render(): void
    {
        if (!empty($this->arguments['file'])) {
            if (!empty($this->arguments['external'])) {
                if (!empty($this->arguments['media'])) {
                    $this->assetCollector->addExternalCssFile($this->arguments['file'], $this->arguments['media']);
                } else {
                    $this->assetCollector->addExternalCssFile($this->arguments['file']);
                }
            } else {
                $this->assetCollector->addCssFile($this->arguments['file']);
            }
        } else {
            $this->assetCollector->addInlineCss($this->renderChildren());
        }
    }
}
