# SimplifiedOverviewAndPublishing

_in2publish_core 10.2_

This feature replaces and combines the Community Edition feature "SimpleOverviewAndAjax" and the Enterprise Edition
feature "SimplePublishing".

The Content Publisher for TYPO3 works as accurately as possible. However, this accuracy also costs a lot of time and
computation power. In some cases this process can take so much time that the tool is no longer usable. To make it
possible that editors can still publish their content there is a feature that makes the process of comparison a bit less
accurate and combines many operations. This results in a sufficiently accurate overview of the changed records with an
incomparably faster process.

You can enable this feature by changing the `factory.finder` setting in your LocalConfiguration.yaml:

```yaml
factory:
  finder: 'In2code\In2publishCore\Features\SimplifiedOverviewAndPublishing\ShallowRecordFinder'
```

**IMPORTANT:**

Before you start activating and using this feature you need to be aware of some aspects. This new method still compares
the selected pages and all records that live on those pages. However, unlike the normal process, this one does not track
any relations other than media files.

In the example of a content item that has categories, this means that the change to the category **selection** will be
published, but the selected category will no longer be. So if the category has been renamed, you need to publish the
category itself.

Of course, this also applies to all other types of relations. As a rule of thumb you can remember that only what you see
directly on the page when you open the list module is published. Linked records that are located on other pages or in
folders are not published and have to be searched and published by yourself.
