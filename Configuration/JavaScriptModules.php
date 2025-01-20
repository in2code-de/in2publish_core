<?php

return [
    'dependencies' => [
        'backend',
        'core',
    ],
    'tags' => [
        'backend.contextmenu',
    ],
    'imports' => [
        '@in2code/in2publish_core/' => 'EXT:in2publish_core/Resources/Public/JavaScript/',
        '@in2code/in2publish_core/backend-module.js' => 'EXT:in2publish_core/Resources/Public/JavaScript/BackendModule.js',
        '@in2code/in2publish_core/backend-enhancements.js' => 'EXT:in2publish_core/Resources/Public/JavaScript/BackendEnhancements.js',
        '@in2code/in2publish_core/confirmation-modal.js' => 'EXT:in2publish_core/Resources/Public/JavaScript/ConfirmationModal.js',
        '@in2code/in2publish_core/context-menu-actions.js' => 'EXT:in2publish_core/Resources/Public/JavaScript/context-menu-actions.js',
        '@in2code/in2publish_core/context-menu-publish-entry.js' => 'EXT:in2publish_core/Resources/Public/JavaScript/ContextMenuPublishEntry.js',
        '@in2code/in2publish_core/loading-overlay.js' => 'EXT:in2publish_core/Resources/Public/JavaScript/LoadingOverlay.js',
        '@typo3/core/event/debounce-event.js' => 'EXT:core/Resources/Public/JavaScript/event/debounce-event.js',
        '@typo3/backend/modal.js' => 'EXT:backend/Resources/Public/JavaScript/modal.js',
        '@typo3/backend/input/clearable.js' => 'EXT:backend/Resources/Public/JavaScript/input/clearable.js'
    ],
];