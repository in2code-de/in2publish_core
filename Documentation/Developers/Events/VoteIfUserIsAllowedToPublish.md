# VoteIfUserIsAllowedToPublish

Replaces the `\In2code\In2publishCore\Controller\AbstractController / VoteIfUserIsAllowedToPublish` Signal. This voting
event is special compared to others. It defaults to "yes" if nothing has been voted. You need at least one "no" vote to
make the voting result negative. A draw will always turn out positive.

This is important if you want to override the permissionToPublish voting from in2publish. It will always vot yes if the
user is allowed to publish. If you want to deny publishing access you need to vote at least 2 times "no" to overrule
the "yes".

## When

* During view rendering, this method decides if the publish button will be rendered or not
* Each time a record is going to be published

## What

Nothing, because the decision has to be based solely on the current user.

## Possibilities

You can listen on this event to allow or deny the right to publish based on users, and therefore on their groups and
other attributes.

### Example

This example shows how to deny publishing to a user which does not have a specific attribute set:

```php
use In2code\In2publishCore\Event\VoteIfUserIsAllowedToPublish;

class UserPublishingDecider
{
    public function __invoke(VoteIfUserIsAllowedToPublish $event): void
    {
        if ($GLOBALS['BE_USER']->user['tx_mypublisher_allow_publishing'] !== true) {
            $event->voteNo();
        }
    }
}
```
