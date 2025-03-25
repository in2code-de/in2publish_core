<?php

/**
 * @var array $EM_CONF
 * @var string $_EXTKEY
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'in2publish Core',
    'description' => 'Content publishing extension to connect stage and production server',
    'category' => 'plugin',
    'version' => '13.0.0',
    'state' => 'stable',
    'author' => 'Alex Kellner, Oliver Eglseder, Thomas Scheibitz, Stefan Busemann',
    'author_email' => 'service@in2code.de',
    'author_company' => 'in2code.de',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.4.99',
            'typo3' => '13.4.0-13.4.99',
            'in2publish_core' => '13.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
