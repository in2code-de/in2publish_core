
Overwrite Configuration for Users or Pages
==========================================

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

