# Feature: TreatRemovedAndDeletedAsDifference

By default records that have been soft-deleted are ignored by the publisher.

However, it may be useful to show records that have been permanently removed on one side (e.g. by a recycler), but are
still present with a deleted flag on the other side (soft-deleted).

If this feature is enabled, these records are shown as difference view in the OverviewModule as shown in the screenshot.

![Deleted records in the Publish Overview Module](_img/treat_removed_and_deleted_as_difference.png)
