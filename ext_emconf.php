<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'in2publish Core',
    'description' => 'Content publishing extension to connect stage and production server',
    'category' => 'plugin',
    'version' => '5.11.1',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearcacheonload' => 1,
    'author' => 'Alex Kellner, Oliver Eglseder, Thomas Scheibitz',
    'author_email' => 'alexander.kellner@in2code.de, oliver.eglseder@in2code.de, thomas.scheibitz@in2code.de',
    'author_company' => 'in2code.de',
    'constraints' => [
        'depends' => [
            'typo3' => '7.6.0-8.99.99',
            'php' => '5.5.0-7.1.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
