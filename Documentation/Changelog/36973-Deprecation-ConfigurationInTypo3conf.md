# Deprecation: Configuration in typo3conf

Issue https://projekte.in2code.de/issues/36973

## Description

in2publish_core is configured using a YAML file. The file path has been configured to
typo3conf/AdditionalConfiguration folder by default. This location is considered
insecure because anyone can access the contents of the configuration file including database credentials,
if not protected by an .htaccess file.

## Impact

Deprecated configuration value:
The value of the extension configuration path `pathToConfiguration` must not include the value `typo3conf`

## Affected Installations

All instances configured to use `typo3conf` in the config file location.

## Migration

The new default value is `CONF:in2publish_core/`. The `FileProvider` will resolve
`CONF:` to the folder where the site configuration is stored. This path
is outside of the web root in composer installations.
