# RecordWasCreatedForDetailAction

Replaces the `\In2code\In2publishCore\Controller\RecordController / beforeDetailViewRender` Signal.

## When

1. SimpleOverviewAndAjax is active (`factory.simpleOverviewAndAjax = TRUE`).
2. The details of a record in the Publish Overview Module are expanded.

## What

* `recordController`: The record controller which was used to execute the request.
* `record`: A fully resolved instance of the record to display the details for.

## Possibilities

Since the event was dispatched before the record will be assigned to the view, you can change the records properties,
related records or anything else which is accessible via public methods (Have a look at the `RecordInterface`
interface).
