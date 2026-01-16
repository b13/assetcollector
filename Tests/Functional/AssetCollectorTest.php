<?php

namespace B13\Assetcollector\Tests\Functional;

/*
 * This file is part of TYPO3 CMS-based extension "assetcollector" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use B13\Assetcollector\AssetCollector;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class AssetCollectorTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/assetcollector'];
    protected array $coreExtensionsToLoad = ['core'];

    #[Test]
    public function buildXmlTagBuildsXmlTagForExistingFile(): void
    {
        $assetCollector = new AssetCollector();
        $assetCollector->addXmlFile('EXT:assetcollector/Resources/Public/Icons/Extension.svg');
        $content = $assetCollector->buildInlineXmlTag();
        self::assertStringContainsString('<title>EXT:assetcollector</title', $content);
    }

    #[Test]
    public function buildXmlTagIsEmptyForNoneExistingFile(): void
    {
        $assetCollector = new AssetCollector();
        $assetCollector->addXmlFile('foo/not-exists.svg');
        $content = $assetCollector->buildInlineXmlTag();
        self::assertSame('', $content);
    }
}
