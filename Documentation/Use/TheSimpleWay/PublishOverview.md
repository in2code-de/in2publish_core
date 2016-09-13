# Publish Overview Module

Opening the **Publish Overview** module allows editors and administrators see live changes between the stage and production system.
There is a single page tree that compares records on both servers.

![Module Overview details](_img/module_detail_overview.png) 

## Color Coding

* Grey: The page or a record on this page does not differ between stage and production
* Yellow: There is a change on this page.
* Green: This is a new page.
* Red: This page was deleted.
* Blue: This page has been moved within the pagetree.

## Filtering

Use the folder buttons on the bottom right of the module to filter the tree.
That is useful, e.g., if you want to see only pages, that have changed.
The settings will be kept in the user session as long as the user is logged in.

## See changes

Clicking on the **i**-icon opens details for this page.

![Arrow](_img/icon_info.png)

Basically there are two main areas. The first is the area with the page-buttons. This is always on top.
In the area underneath there is a line for each record that has been changed.
If there are fields with different content, it will be shown for the related record.

![Module Overview details open](_img/module_detail_overview_open.png)

* Page preview: Opens a new browser tab with the selected page on stage or production.
* Page history: Opens the TYPO3 history of the selected page.
* [Tablename] [HistoryIcon]: Opens the TYPO3 history of the selected record.
* [Tablename] [EditIcon]: Opens the TYPO3 edit view of the selected record.

## Publish

Pages with all related records can be published simply by clicking the arrow icon.
This triggers that this page on the production system gets the same records as the stage system.

![Arrow](_img/icon_arrow.png)

Note:

> The arrow-icon is only shown if
>
> * there are changes, and
> * the user has the right to publish, and
> * workflow is disabled or the page has the workflow state "ready to publish".

Note:

> If there is a relation from one record to another record on another page, it gets also published. E.g. a news record that has a relation to a content record on another page.
