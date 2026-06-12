<?php

declare(strict_types=1);

namespace B13\Assetcollector\Tests\Unit;

/*
 * This file is part of TYPO3 CMS-based extension "assetcollector" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use B13\Assetcollector\AssetCollector;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AssetCollectorTest extends UnitTestCase
{
    #[Test]
    public function buildInlineCssTagTest(): void
    {
        $assetCollector = $this->getMockBuilder(AssetCollector::class)
            ->onlyMethods(['getUniqueInlineCss', 'getUniqueCssFiles'])
            ->getMock();
        $assetCollector->expects(self::once())->method('getUniqueCssFiles')->willReturn([]);
        $assetCollector->expects(self::once())->method('getUniqueInlineCss')->willReturn(['my-inline-css']);
        $cssTag = $assetCollector->buildInlineCssTag();
        self::assertStringContainsString('<style class="tx_assetcollector">my-inline-css', $cssTag);
    }

    #[Test]
    public function addReferencedIconsResolvesMissingIconFromRegistry(): void
    {
        $assetCollector = new AssetCollector();
        $assetCollector->addReferencedIcons(
            '<body><svg><use xlink:href="#icon-Extension"></use></svg></body>',
            static fn (): array => ['Extension' => 'EXT:assetcollector/Resources/Public/Icons/Extension.svg']
        );
        self::assertSame(
            ['EXT:assetcollector/Resources/Public/Icons/Extension.svg'],
            $assetCollector->getUniqueXmlFiles()
        );
    }

    #[Test]
    public function addReferencedIconsDoesNotInvokeRegistryWhenIconAlreadyCollected(): void
    {
        $assetCollector = new AssetCollector();
        $assetCollector->addXmlFile('EXT:assetcollector/Resources/Public/Icons/Extension.svg');
        $registryWasResolved = false;
        $assetCollector->addReferencedIcons(
            '<use xlink:href="#icon-Extension"></use>',
            static function () use (&$registryWasResolved): array {
                $registryWasResolved = true;
                return [];
            }
        );
        self::assertFalse($registryWasResolved, 'The registry provider must only be invoked when an icon is missing.');
        self::assertSame(
            ['EXT:assetcollector/Resources/Public/Icons/Extension.svg'],
            $assetCollector->getUniqueXmlFiles()
        );
    }

    #[Test]
    public function addReferencedIconsIgnoresIconsThatAreNotInTheRegistry(): void
    {
        $assetCollector = new AssetCollector();
        $assetCollector->addReferencedIcons(
            '<use xlink:href="#icon-unknown"></use>',
            static fn (): array => ['Extension' => 'EXT:assetcollector/Resources/Public/Icons/Extension.svg']
        );
        self::assertSame([], $assetCollector->getUniqueXmlFiles());
    }
}
