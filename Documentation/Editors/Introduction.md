Hint:

> This manual is currently in progress

# Introduction to in2publish_core

## What is the Content Publisher?

The Content Publisher is a TYPO3 extension which provides a backend module to selectively publish content to another
TYPO3 instance.

## How to use the content publisher

The core version of in2publish does not integrate into the page or list module, so it won't change anything in the view
you are accustomed to.
If you want to publish content you have to open the backend module "Publish Overview".

Select the page from the page tree which you want to publish. The page will be shown on the right side of backend and
have a highlighting color based on the current state of the record.
The color codes are as follows:

* Grey: No changes between Local and Foreign
* Yellow: One or more properties changed
* Green: The page is new on Local. It was not published yet and does not exist on Foreign
* Blue: The page has been moved in the hierarchy
* Red: The page has been deleted on Local but still exists on Foreign

You can publish a page by clicking on the arrow in the middle of the row with the pages name.
