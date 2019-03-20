<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'in2publish Core',
    'description' => 'Content publishing extension to connect stage and production server',
    'category' => 'plugin',
    'version' => '8.0.1',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearcacheonload' => 1,
    'author' => 'Alex Kellner, Oliver Eglseder, Thomas Scheibitz, Stefan Busemann',
    'author_email' => 'service@in2code.de',
    'author_company' => 'in2code.de',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-9.5.99',
            'php' => '7.0.0-7.3.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
