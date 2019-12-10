<?php
return [
    'frontend' => [
        'b13/assetcollector' => [
            'target' => \B13\Assetcollector\Middleware\InlineSvgInjector::class,
            'after' => [
                'typo3/cms-frontend/content-length-headers'
            ]
        ]
    ]
];
