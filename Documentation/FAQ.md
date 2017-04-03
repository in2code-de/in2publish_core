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

How to delete caches of a page in production?
---------------------------------------------

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

Scheduler: Can't call commandController from the cli or cronjob but it works in the scheduler module?
-----------------------------------------------------------------------------------------------------

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

How can i publish content from Live to Stage?
---------------------------------------------

You can't (kind of).
If you want to transfer user generated content from Live to Stage you can use the TableCommandController (available in the Scheduler for example) or write your own scripts.
But be aware that the table:import command overwrites one table with another. It does not keep relations (it does not even resolve them) and it does not merge data. It is just a simple and stupid table copy task.

How do i handle user generated content?
---------------------------------------

**CAUTION: in2publish_core does not handle user generated content. If record UIDs match any data will be overwritten!**

The TYPO3 core does not provide a frontend user image upload or any other plugin that enables frontend users to create, update or remove data, therefore any implementation is a 3rd party extension.
Please note that we can not provide generic ("out-of-the-box") support for any 3rd party extension.
If you have problems with a specific 3rd party extension you can contact [service@in2code.de](mailto:service@in2code.de) to ask for a consulting/support quote.

First of all: Ensure all tables that are affected by user generated content are included in `excludeRelatedTables`.
Some tables like `sys_file`, `sys_file_reference` and `sys_file_metadata`, however, must not be included in `excludeRelatedTables` or publishing content with images will not work.
Since these tables are also affected by frontend user interaction on live (if there is an image upload or a forum or something like that [3rd party!]) there will be conflicts.
These conflicts will lead to user content in published content or vice versa. **Be aware that unhandled conflicts might lead to data disclosure!**

**There is, however an experimental feature**, which allows you to have user generated content without running into UID conflicts.
First of all set `factory.fal.reserveSysFileUids` to FALSE. The you have to set the auto_increment of the tables `sys_file`, `sys_file_reference` and `sys_file_metadata` to a high value.
There is still one thing you must know: When using this feature UIDs of sys_file records may be rewritten, so you might loose any data from the foreign instance related to that record.
This should only happen if there are two indices for the exact same file with different UIDs, so this case is rather rare.
