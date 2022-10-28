<?php

/**
 * @var array $EM_CONF
 * @var string $_EXTKEY
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'in2publish Core',
    'description' => 'Content publishing extension to connect stage and production server',
    'category' => 'plugin',
    'version' => '11.0.4',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'author' => 'Alex Kellner, Oliver Eglseder, Thomas Scheibitz, Stefan Busemann',
    'author_email' => 'service@in2code.de',
    'author_company' => 'in2code.de',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.99.99',
            'typo3' => '11.5.0-11.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
