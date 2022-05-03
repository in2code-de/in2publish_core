# RecordWasCreatedForDetailAction

Replaces the `\In2code\In2publishCore\Controller\RecordController / beforeDetailViewRender` Signal.

## When

Every time in `\In2code\In2publishCore\Controller\RecordController::detailAction`.

## What

* `recordController`: The instance of the RecordController executing the request.
* `record`: The record found for the current request.

## Possibilities

Use this event if you want to observe the record before it will be rendered by the view.
