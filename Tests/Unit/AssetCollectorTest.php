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
}
