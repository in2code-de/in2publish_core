# Configuration

The configuration is split into two parts:

* **[LocalConfiguration.yaml](../../Configuration/Yaml/LocalConfiguration.yaml.example)** configuration on Local (Stage) to connect to the production server.
* **[ForeignConfiguration.yaml](../../Configuration/Yaml/ForeignConfiguration.yaml.example)** for the configuration on Foreign (production).

As of in2publish_core 7.0 you don't need to copy the whole file anymore.
You just need to create the file and set values that differ from the default configuration. (Create the file at the location defined in the extension configuration of in2publish_core).

Note:
> If you want to separate your configuration depending on the in2publish version, you could also use **LocalConfiguration_[version].yaml** and **ForeignConfiguration_[version].yaml** for a defined version. That could help you for your future deployments. E.g. LocalConfiguration_1.2.3.yaml
> Since 7.0 you don't have to provide the full version number. You can omit the patch version (last number) or the patch and minor version (last two nubers).

# Overwrite Configuration for Users or Pages

Some configurations can be overwritten by PageTS config and UserTS config (applys only to Local).

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

**Continue with [Testing](4_Testing.md)**
