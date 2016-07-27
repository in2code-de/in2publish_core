<?php
$EM_CONF[$_EXTKEY] = array(
    'title' => 'in2publish Core',
    'description' => 'Content publishing extension to connect stage and production server',
    'category' => 'plugin',
    'version' => '5.0.0',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearcacheonload' => 1,
    'author' => 'Alex Kellner, Oliver Eglseder, Thomas Scheibitz',
    'author_email' => 'alexander.kellner@in2code.de, oliver.eglseder@in2code.de, thomas.scheibitz@in2code.de',
    'author_company' => 'in2code.de',
    'constraints' => array(
        'depends' => array(
            'typo3' => '6.2.0-7.99.99',
            'extbase' => '6.2.0-7.99.99',
            'fluid' => '6.2.0-7.99.99',
            'cms' => '',
            'php' => '5.3.0-7.99.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
);
