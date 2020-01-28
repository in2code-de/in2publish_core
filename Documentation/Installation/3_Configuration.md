# Configuration

The configuration is split into three parts:
* **Extension Configuration** Which defines configuration values so basic they can't be set any later.
* **[LocalConfiguration.yaml](../../Configuration/Yaml/LocalConfiguration.yaml.example)** configuration on Local (Stage) to connect to the production server and configure the modules and behaviour.
* **[ForeignConfiguration.yaml](../../Configuration/Yaml/ForeignConfiguration.yaml.example)** for the configuration on Foreign (production).

As of in2publish_core 7.0 you don't need to copy the whole file anymore.
You just need to create the file and set values that differ from the default configuration. (Create the file at the location defined in the extension configuration of in2publish_core).

Note:
> If you want to separate your configuration depending on the in2publish version, you could also use **LocalConfiguration_[version].yaml** and **ForeignConfiguration_[version].yaml** for a version specific configuration.
> That could help you for your future deployments. E.g. LocalConfiguration_1.2.3.yaml
> Since 7.0 you don't have to provide the full version number. You can omit the patch version (last number: LocalConfiguration_1.2.yaml) or the patch and minor version (last two numbers: LocalConfiguration_1.yaml).

## Configuration (provided by the Configuration Providers) is merged recursively

The configuration arrays provided by the available Configuration Providers are generally merged recursively following the following rules:

* The value of items having an identical ALPHANUMERIC key will be REPLACED
* The value of items having an identical NUMERIC key will be ADDED   
* Setting a configuration value to "[__UNSET](#unset)" will remove this configuration from the resulting configuration array

### Special treatment of "definition" configuration keys

As of in2publish_core 7.1 every configuration key named "definition" is (compared to the rules noted above) treated specially:

* The value of items having an identical NUMERIC key will be REPLACED

# Extension Configuration

The extension configuration is split in 2 parts. Basic and Local.
Basic must be configured on both sides, whereas "Local" needs to be set only on Local.

## Basic

    pathToConfiguration = typo3conf/AdditionalConfiguration/
    logLevel = 5

* pathToConfiguration:
  Defines the path where the LocalConfiguration.yaml file on Local and the ForeignConfiguration.yaml on Foreign.
  This can be set individually on both sides.
* logLevel:
  Defines the minimum severity of logs to be persisted into the database.

## Local

    disableUserConfig = 0
    adapter.remote = ssh
    adapter.transmission = ssh

* disableUserConfig:
  Disables the UserTsProvider. If checked no UserTS will be merged in the in2publish_core configuration.
* adapter.remote & adapter.transmission:
  Adapter identifier of the implementation. in2publish_core comes with ssh as default. Other packages like the HTTP-Adapter (in2publish_http) are available from in2code.

## <a name="unset"></a>Removing default values

in2publish_core's configuration container supports not just overriding default values but also removing them.
Say, you want to remove `be_users` and `be_groups` from the list of default `excludeRelatedTables`.
First you need to know which keys these values have. Go to the Tools Module to inspect the configuration:

![Tools Module with expanded excludeRelatedTables](_img/95_tools_show_config.png)

You see that `be_groups` has the index `0` and `be_users` has `1`.
Now you create or edit your LocalConfiguration.yaml file and add the following section:

```YAML
excludeRelatedTables:
  0: __UNSET
  1: __UNSET
```

Now the values do not appear anymore in the configuration

![Tools Module with expanded excludeRelatedTables without be_users and be_groups](_img/95_tools_config_unset_cropped.png)

You can unset any value by it's index.
This feature is also available in PageTS und UserTs as well as any other
configuration provider (Like the configuration wizard in in2publish).

Configuration provider configs are merged following the order of priority.
This means you can not unset a value in your LocalConfiguration.yaml that
has been set in PageTS or UserTS, but you can overwrite YAML and PageTS
values in your UserTS configuration because it comes after those two.

# Overwrite Configuration for Users or Pages

Some configurations can be overwritten by PageTS config and UserTS config (applies only to Local).

PageTS config will always be merged, overruling the configuration of the yaml file.
UserTS config can be disabled in the extension configuration.
When the UserTS config is enabled, it will overwrite the configuration after PageTS config was merged, so it always has priority.

PageTS and UserTs for in2publish starts with **tx_in2publish** followed by the configuration directive to overwrite.

Here is an example to disable the filter buttons for the publish overview module::

    tx_in2publish {
        view {
            records {
                filterButtons = FALSE
            }
        }
    }

---

**Following settings can be overridden by PageTS and UserTS:**
   
 * Debug Settings (debug.*)
 * Factory recursion settigns (factory.*recursion)
 * Simple Overview and Ajax (factory.simpleOverviewAndAjax)
 * Publish Files Module folder file limit (factory.fal.folderFileLimit)
 * File Preview Domain (Usefull in PageTS) (filePreviewDomainName)
 * View a) filter buttons b) breadcrumb c) titleField (view.*)
 
**Follwing settings are accessed before any page or user is resolved or must not be changed by UserTS/PageTS:**
 
 * Foreign Instance Settings (foreign.*)
 * Enabled Modules (module.*)
 * SSH Connection (sshConnection.*)
 * ignoreFieldsForDifferenceView
 * TCA Processors (tca.*)
 * Tasks (tasks.*)
 * Backup settings (backup.*)
 * Factory FAL settings (factory.fal.* except factory.fal.folderFileLimit)

**Continue with [Testing](4_Testing.md)**
