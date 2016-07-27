FAQ
===

How to delete cache of a page in production?
--------------------------------------------

If you publish a page, frontend-caches of the same page on production will be cleaned.
Special case: For sysfolders, e.g. with news-records, you  want to clean the cache of another page than the sysfolder
that was published. In this case you can use clearCacheCmd in Page TSConfig on the stage (!) system:

    # Put this page TSConfig on a news sysfolder. Any publish of this sysfolder will clean FE-cache of PID 12 and 23
    TCEMAIN.clearCacheCmd = 12,23

    # Clean all caches if a page was published
    TCEMAIN.clearCacheCmd = all

    #  Will clear all pages cache
    TCEMAIN.clearCacheCmd = pages

See https://docs.typo3.org/typo3cms/TSconfigReference/PageTsconfig/TCEmain/Index.html#clearcachecmd for the original
documentation

Scheduler: Can't call commandController from cli but in scheduler backend module works?
---------------------------------------------------------------------------------------

You have to add the environment variable for all CLI calls of commandControllers
Example call with environment variable (for the stage system):

    IN2PUBLISH_CONTEXT=Local ./typo3/cli_dispatch.phpsh extbase status:version
