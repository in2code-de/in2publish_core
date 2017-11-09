# Configuration

The configuration is split into two parts:

* **[LocalConfiguration.yaml](LocalConfiguration.md)** configuration on stage to connect to the production server.
* **[ForeignConfiguration.yaml](ForeignConfiguration.md)** for the configuration on production to receive information from the stage server.

Please take the two example files from EXT:in2publish/Configuration/Yaml/, move them to a folder above the webroot (e.g. /var/www/PublishConfiguration/) and modify them.

Note: If you want to separate your configuration depending on the in2publish version, you could also use **LocalConfiguration_[version].yaml** and **ForeignConfiguration_[version].yaml** for a defined version. That could help you for your future deployments. E.g. LocalConfiguration_1.2.3.yaml

* [LocalConfiguration.yaml](LocalConfiguration.md)
* [ForeignConfiguration.yaml](ForeignConfiguration.md)
* [Overwrite Configuration](OverwriteConfiguration.md)

# Overwrite Configuration for Users or Pages

Any configuration of the LocalConfiguration.yaml except **database** and **sshConnection** can be overwritten by
PageTS config and UserTS config.

PageTS config will always be merged, overruling the configuration of the yaml file.
UserTS config must be enabled in the configuration file.
When UserTS config is enabled, it will overwrite the configuration after PageTS config was merged, so it always has priority.

PageTS and UserTs for in2publish starts with **tx_in2publish** followed by the configuration directive to overwrite.

Please note that you can only alter configuration that exists in the yaml file, so no new keys or values can be set.

Here is an example to disable the filter buttons for the publish overview module::

    tx_in2publish {
        view {
            records {
                filterButtons = FALSE
            }
        }
    }

---

**Continue with [Testing](4_Testing.md)**
