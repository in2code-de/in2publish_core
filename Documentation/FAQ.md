FAQ
===

Publisher backend modules are not visible?
------------------------------------------

Please check if you have set the environment variables on your server for the stage system via htaccess:

```
SetEnv IN2PUBLISH_CONTEXT Local
```

or AdditionalConfiguration.php:

```
putenv('IN2PUBLISH_CONTEXT=Local');
```

See https://github.com/in2code-de/in2publish_core/blob/master/Documentation/Installation/Preparation.md#os-requirements
for details

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

Scheduler: Can't call commandController from cli or cronjob but it works in the scheduler module?
-------------------------------------------------------------------------------------------------

You have to add the environment variable for all CLI calls of commandControllers
Example call with environment variable (for the stage system):

    IN2PUBLISH_CONTEXT=Local ./typo3/cli_dispatch.phpsh extbase status:version

Where can i get the Foreign Key Fingerprint
-------------------------------------------

Have a look here: [How to get the foreign key fingerprint](Installation/Configuration/LocalConfiguration.md#how-to-get-the-foreign-key-fingerprint)

How do i enable SSH Daemon on my Mac?
-------------------------------------

Enable login for all users (not recommended) or just the user you configured in sshConnection.username

Newer versions of OS X:
Got to "System Preferences" -> "Sharing" -> enable "Remote Login"

Older versions of OS X:
Go to "System Preferences" -> "Internet & Networking" -> "Sharing" -> enable "Remote Login"

Can i use files with umlauts and special characters?
-----------------------------------------------------

No.
Since file names will be passed as arguments on the command line and we are very strict about that for safety reasons, we support only file and folder names that are accepted by TYPO3 when the option `UTF8filesystem` is set to false.
You can convert your existing files with the EnvironmentCommandController.
