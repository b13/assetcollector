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
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Add SVGs
 */
class SvgViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'svg';

    protected AssetCollector $assetCollector;

    public function __construct(AssetCollector $assetCollector)
    {
        parent::__construct();
        $this->assetCollector = $assetCollector;
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument(
            'file',
            'string',
            'svg file name',
            false
        );
        $this->registerArgument(
            'name',
            'string',
            'SVG file name from TypoScript setup.',
            false
        );
    }

    public function render(): string
    {
        $file = '';
        if (!empty($this->arguments['name'])) {
            $file = $this->assetCollector->getTypoScriptValue((string)$this->arguments['name']);
        }
        if ($file === '' && !empty($this->arguments['file'])) {
            $file = $this->arguments['file'];
        }
        if ($file === '') {
            return '';
        }

        $this->assetCollector->addXmlFile($file);
        $iconIdentifier = $this->assetCollector->getIconIdentifierFromFileName($file);
        $content = '<use xlink:href="#icon-' . $iconIdentifier . '"></use>';

        $this->tag->forceClosingTag(true);
        $this->tag->setContent($content);

        return $this->tag->render();
    }
}
