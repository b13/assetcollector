<?php

namespace B13\Assetcollector\Tests\Functional\Functional;

/*
 * This file is part of TYPO3 CMS-based extension "assetcollector" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class InlineCssTest extends AbstractFrontendTest
{
    /**
     * @test
     */
    public function scriptTagForInlineCssIsRendered(): void
    {
        $this->importCSVDataSet(ORIGINAL_ROOT . 'typo3conf/ext/assetcollector/Tests/Functional/Frontend/Fixtures/inline_css.csv');
        $response = $this->executeFrontendRequestWrapper(new InternalRequest('http://localhost/'));
        $expected = '<style class="tx_assetcollector">h1{color:red;}';
        $body = (string)$response->getBody();
        self::assertStringContainsString($expected, $body);
    }
}
