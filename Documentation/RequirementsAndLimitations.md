# Requirements

This is a more general overview over all requirements and limitations.
You will find more detailed information and hints in each section of the documentation that handles the specific part.

## Operating System / OS Software

in2publish_core depends on various common system properties and software for faultless operations.
The most basic dependency is the operating system or short "OS". We've built and tested in2publish on various LINUX und
UNIX based OS to ensure it works in most cases.
Because there are so many different OS out there we can't develop and test in2publish_core on each, so there are a few
requirements to the OS that ensure in2publish_core will not run into problems in this level.

* All requirements from the TYPO3 version you are using (inherited)
* UNIX/Linux based OS like Ubuntu, Debian, Gentoo or even Mac OS (Windows Server or Windows + XAMPP for example is
  neither tested nor actively supported)
* SSH2 on Local, directly executable via `ssh` as the webservers user (Only if you are using an SSH-based adapter)
* SSH-Server on Foreign, authentication via public key (Only if you are using an SSH-based adapter)
* Shell on Foreign for the SSH user (`/bin/bash` in `/etc/passwd`) (Only if you are using an SSH-based adapter)
* Persistent SSH port forwarding (if the database on foreign is not directly accessible)
* Environment variables CLI: These must be able to set via `export` or directly beforehand a command
* Environment variables Web: These must be set via SetEnv in the .htaccess or virtual host or before any part (e.g.
  ext_tables or ext_localconf) of in2publish_core is loaded.

If you don't know if your servers match all of these minimum requirements please ask your hoster or system admin.

## Server Software

The servers software is what distinguishes your PC from a Mailserver, Webserver or a DNS.
The 3 main parts for web applications like TYPO3 are the web server software, the file system and a database.
They have to match certain criteria to provide an environment suitable for in2publish.

* Web server apache >= 2.2 or nginx<sup>1</sup>
* PHP (See [composer.json](https://github.com/in2code-de/in2publish_core/blob/master/composer.json) for the currently
  required PHP version)
* PHP Extension [ssh2](https://pecl.php.net/package/ssh2) >= 0.11 (recommended >= 0.12) (Only if you are using an
  SSH-based adapter)
* MySQL >= 5.6 or MariaDB<sup>2</sup> >= 10.1
* A current [TYPO3 LTS](https://typo3.org/typo3-cms/roadmap/) version (not ELTS!).

## PHP settings

PHP is the most used language for programming web pages and also the language in2publish_core is written in.
Because it's so widely spread and popular there are a lot of configurations and extensions available to match
application requirements.

Notice that all these numbers are dependent on the size of your TYPO3.
If you have hundreds of thousands of pages and content elements you might have to multiply everything by factor of 2 or
even 4.

For small TYPO3 instances with a couple handful of pages everything should be fine if you meet TYPO3s minimum
requirements. The more pages, content, extensions and relations you have, the higher the requirement of TYPO3 as well as
in2publish_core. In fact in2publish_core resource requirements might outstrip TYPO3s by far, the more rows you have in
your database and the more relations you have defined in your TCA.

# Limitations

The overview and publishing process requires more resources than a normal backend or frontend request, because the
content publisher needs to find all related records for a page, compare them and decide if they are publishable.
Not every difficult or big task can be made faster (or possible) with more RAM, faster disk I/O and better CPUs,
resulting in some inevitable limitations.
Here's a list of currently known limitations, but without claim of completeness:

* Foreign side relations. Bidirectional relations have an owning and a foreign side. in2publish_core can only resolve
  relations and hence publish relations from the owning side or else it will run into a loop.
* User generated Content: You can have user generated on foreign and editorial content on stage on the same table if
  properly configured. It's possible to publish editorial contents, but not to retrieve user generated content. Also
  read [FAQ: User generated Content](FAQ.md#how-do-i-handle-user-generated-content)
* Multi Head / Multi Target publishing: in2publish-core can not diff against or publish to more than a single target.
  You can chain instances and publish from A to B and B to C, but not from A to B and C at the same time.
* Moved/Renamed folders can not be detected. The folder on foreign will be marked as deleted, the one on local will be
  shown marked new. If you delete the folder from foreign all files within will be removed with their parent folder.
  Publish files in the Publish Files Module from the renamed folder on local to move them.
* Sorting of records: If a record is published from stage to live, the current value of the column "sorting" is
  published. This can lead to duplicate or different sorting values between stage and live. Background: If the sorting
  of a record within a page is changed, all sorting values in all affected records might get changed. As we want to
  publish only a single record of this page, we prevent the publication of all affected records.

---

#### Footnotes:

1. We do not actively develop or test on nginx but we have at least 2 reports of in2publish_core running without
   problems on nginx.
2. If you use a Galera Cluster, please
   notice [Galera Known Limitations](https://mariadb.com/kb/en/mariadb/mariadb-galera-cluster-known-limitations/). TYPO3
   MM Tables tend to not have PK fields, which will result in inconsistency across your DB bricks.
