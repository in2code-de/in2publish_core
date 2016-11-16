# JavaScript in in2publish_core

In in2publish_core we try to use as less as possible JavaScript to accomplish our goals but still build a good UI with good UX.

There are a few JavaScript files, which are used in different places:

* BackendModule.js contains JavaScript used in all backend modules
* DateTimePicker.js builds around the pikaday JavaScript library and is required for especially enterprise features.
* Overall.js required for eneterprise features
* PageModule.js contains most of the workflow UI functionalities
* PageModuleJquery.js wraps around jQuery for TYPO3 lower than 7.6
* VersionCheck.min.js is included in the Tools module to show you if you use a current version of in2publish.

Most of the JavaScript actually belongs to the enterprise version and will be removed eventually.
