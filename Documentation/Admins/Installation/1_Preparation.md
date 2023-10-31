# Preparation

The Content Publisher publishes content from one TYPO3 instance to another, completely independent TYPO3 instance. We
call the TYPO3 where the editors create and review content "Local" and the other TYPO3, where users view the content and
interact with the webpage "Foreign". Please keep these names in mind as we are using them throughout the whole docs.

## TYPO3 Requirements

As the preface states, you need to separate TYPO3 instances. They must have the same extensions in the same version
installed, but they need to have their own file system and their own database. It is the Content Publisher's task to
transfer content from "Local" to "Foreign", to update files in the file system and write data to the other database.

We recommend using the same project repository to deploy these instances, and to deploy them concurrently, so that
differences in the installed versions of the core and extensions and differences in the database schema are prohibited.

Since the Content Publisher is written with security in mind, everything is prepared for a specific server setup. The "
Local" TYPO3 should be hosted in your Intranet or any network which is protected from public access. It should not be
reachable for normal users on the internet. "Foreign" would typically be hosted in
a [demilitarized zone (DMZ)](https://en.wikipedia.org/wiki/DMZ_(computing)). With this setup, it is possible to shut
down the Backend on "Foreign" and keep out outsider attackers from trying to hack your public TYPO3 Backend.

The Content Publisher requires that the environment variable `IN2PUBLISH_CONTEXT` is set to `Local` on Local
and `Foreign` on "Foreign". Please keep in mind, that PHP distinguishes between WEB and CLI requests. The env var has to
be present on the CLI, too, or else something may break. This is especially important for cronjobs and anything you do
on the CLI manually. Simply prefix your command with the context, and you should be fine,
e.g. `IN2PUBLISH_CONTEXT=Local php ./vendor/bin/typo3 scheduler:run`. For the WEB context it should be
sufficient to set the env var via `.htaccess`. You can also set the env var either in the `AdditionalConfiguration.php`
or better, on the host or container if you are running on docker/podman or in the cloud.

## System Requirements

The Content Publisher has the same minimum PHP, Database and Browser requirements
as [TYPO3's System Requirements](https://get.typo3.org/version/11#system-requirements).

On top of TYPO3's System Requirements, there are adapter specific requirements. The Content Publisher ships with the
default `ssh` adapter, which ensures encrypted and authenticated communication between the two Content Publisher
instances.

Additional PHP extensions for the default `ssh` RCE and TATAPI adapter:

| Dependency   | Version  |
|--------------|----------|
| php-ext ssh2 | > = 0.13 |

The PHP ssh2 extension only supports SSH RSA Keypairs in PEM format. It should have a length of 4096 for maximum
security, it can (should) contain a comment, but it must not have any other feature.
Use `ssh-keygen -m PEM -t rsa -b 4096` to create the keys.

If you can not install php-ext ssh2 on your system, or it does not work you can install a different adapter. Currently,
we offer following adapters which do not need the PHP libssh bindings. Please
contact [info@in2code.de](mailto:info@in2code.de) for more information about these adapters:

* in2publish_seclib: Drop-in replacement for the default adapter, that uses phpseclib as SSH2 implementation.
* in2publish_native: Drop-in replacement for the default adapter, that uses the system's `ssh` binary.
* in2publish_http: Secure end-to-end hybrid encrypted and signed communication.
* in2publish_local: (Only TATAPI) Publish files via an existing network mount (NFS/SMB/...).

PHP configuration values:

| Name            | Value |
|-----------------|-------|
| allow_url_fopen | On    |

Hint:

> php_ssh2 0.11 does not include the function ssh2_sftp_chmod. You can download a newer version
> here: [PECL SSH2 Download](https://pecl.php.net/package/ssh2)
> Most versions on package based server software are below 0.12, so the function has been made optional.
> In this case you have to take care of file permissions yourself.

## Network Requirements

The Content Publisher expects the Foreign database to exist on another server, but it still needs to access it. In most
shared or managed hosting environments it is common, that database access is not restricted to IPs or `localhost`. In
enterprise architectures it is very common to lock down the access to a database to the host that uses that database. In
that case, you have to open up access to Foreign's database from Local. There are different possibilities, depending on
your security guidelines.

* Add Local's IP to the hosts allowed for the user (`bind-address` in mysqld.cnf and user host limitation. Ask your
  system administrator if you are unsure how to achieve this).
* Create a permanent SSH-based port forwarding from Local to Foreign with e.g. `autossh`. Example to create a
  port-forwarding: `autossh -M 0 -o "ServerAliveInterval 60" -o "ServerAliveCountMax 3" -N -f -i /path/to/.ssh/id_rsa -L 3307:127.0.0.1:3306 ssh-user@example.com`
  You can now use `localhost:3307` to access the database on Foreign.

## OS User Requirements (only SSH-based adapters)

You have to understand that your webserver is a process that runs under a specific user. If you have PHP-FPM installed,
the PHP process might run under the same or a different user. This can be tricky when both processes have to access
files written by a process that has a different user. Usually, you won't run into problems. The Content Publisher
however connects to foreign using SSH and starts TYPO3 console commands. These commands now run under the user of the
SSH session, which can be a completely different user. Files created will be created with a different owner and group
that the webserver and PHP process run as and file access problems might occur.

To mitigate problems there are these strategies:

* Allow the SSH login for the same user that PHP is running under (or your webserver in case of non PHP-FPM). This
  solution will mitigate all permission problems, but possibly open up the user's SSH login for attacks.
* Give the Foreign user the same primary group as the PHP/webserver process user and ensure the users `umask` is
  always `002`, so that files and folders will be created with read and write permission for all group members.

## Examples

### How to create the Public and Private Key pair

Hint:

> You need sudo or at least the permission to execute ssh-keygen as the webserver's user

```BASH
# 1. log in to local
ssh ssh-user@example.com

# 2. change to the webserver's user
sudo su -s /usr/bin/bash - www-data

# 3. create key pair (you should always generate a strong key! Always adapt the command to the last best practices)
ssh-keygen -m PEM -t rsa -b 4096

# 4. follow the instructions. you might define a password to encrypt the private key,
#    but you have to write it into the LocalConfiguration.yaml as unencrypted plain text.
```

### How to create a valid user on Foreign

Hint:

> This is only for guidance. Please contact your system administrator if you are not sure what you are doing.

Using the webserver's user (assuming the user's name is `www-data`):

```BASH
# 1. Login on foreign
ssh ssh-user@www.example.com

# 2. Enable login for the user with a shell (example for www-data)
usermod -s /usr/bin/bash www-data

# 3. Set the home directory if not set (example for www-data)
usermod -d /var/www/websites www-data

# 4. Create an .ssh folder inside the home directory
mkdir /var/www/websites/.ssh

# 5. Create an authorized_keys file inside the .ssh folder and paste
#    the public key from Local into it (you can use vi/vim instead of nano)
nano /var/www/websites/.ssh/authorized_keys

# 6. Login on Local::
exit
ssh ssh-user@stage.example.com

# Note: local-host is the hostname of the server where Local is, not your localhost

# 7. Change your user to the web-process user (repeat step 1 if you
#    cannot login, or define a login shell when changing users)::
sudo su -s /usr/bin/bash - www-data

# 8. Test the login to foreign::
ssh www-data@www.example.com
```

If this does not work please contact your server administrator, or someone that knows how to get this stuff working.

### Install libssh2 and ssh2 (PHP module) on DF Managed Server

Disclaimer:

> This is only for guidance. Please contact your system administrator if you are not familiar with compiling modules.
> This guide comes WITHOUT ANY WARRANTY

Taken from https://www.df.eu/forum/threads/68032-Installationsprobleme-ssh2-so
Walkthrough for domainFactory ManagedServer and target PHP version 7

```BASH
# 1. Login via ssh
ssh ssh-user@stage.example.com

# 2. Switch to the directory where you want to download the resources
mkdir -p php_modules/sources/ && cd php_modules/sources/

# 3. Download all required sources

# 3.1 Get the latest version from http://www.libssh2.org/
wget https://www.libssh2.org/download/libssh2-1.7.0.tar.gz

# 3.2 Get the latest version from http://pecl.php.net/package/ssh2
wget https://pecl.php.net/get/ssh2-1.1.2.tgz

# 4. Unpack the resources:
tar xfz libssh2-1.7.0.tar.gz
tar xfz ssh2-1.1.2.tgz

# 5. Enter the unpacked libssh folder and compile the module.
#    Keep the version in the folder name.
cd libssh2-1.7.0/
./configure --prefix=$HOME/php_modules/libssh2-1.7.0/
make && make install

# 6. Enter the unpacked ssh2 folder and compile the module.
cd ../ssh2-1.1.2/

phpize7

./configure --with-php-config=/usr/local/bin/php7-config \
  --with-ssh2=$HOME/php_modules/libssh2-1.7.0/

make

# 7. Enter your target directory and add a php.ini file
cd $HOME/webseiten/my_website/stage/webroot/typo3
printf "extension_dir=$HOME/php_modules/sources/ssh2-1.1.2/modules/\nextension=ssh2.so" > php.ini
```

The ssh2 functions should be available immediately, as well as the ssh2:// wrapper

---

**Continue with [Installation](2_Installation.md)**
