<?php

declare(strict_types=1);

namespace B13\Assetcollector\Listener;

/*
 * This file is part of TYPO3 CMS-based extension "assetcollector" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use B13\Assetcollector\AssetCollector;
use B13\Assetcollector\Hooks\AssetRenderer;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;

class AfterCacheableContentIsGenerated
{
    protected AssetRenderer $assetRenderer;
    protected AssetCollector $assetCollector;

    public function __construct(AssetRenderer $assetRenderer, AssetCollector $assetCollector)
    {
        $this->assetRenderer = $assetRenderer;
        $this->assetCollector = $assetCollector;
    }

    public function __invoke(AfterCacheableContentIsGeneratedEvent $event)
    {
        $frontendController = $event->getController();
        $this->assetRenderer->collectInlineAssets([], $frontendController);
        $event->getController()->content = str_ireplace(
            '</body>',
            $this->assetCollector->buildInlineXmlTag() . '</body>',
            $event->getController()->content
        );
        $event->enableCaching();
    }
}
