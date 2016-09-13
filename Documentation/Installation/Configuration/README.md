# Configuration

The configuration is split into two parts:

* **[LocalConfiguration.yaml](LocalConfiguration.md)** configuration on stage to connect to the production server.
* **[ForeignConfiguration.yaml](ForeignConfiguration.md)** for the configuration on production to receive information from the stage server.

Please take the two example files from EXT:in2publish/Configuration/Yaml/, move them to a folder above the webroot (e.g. /var/www/PublishConfiguration/) and modify them.

Note: If you want to separate your configuration depending on the in2publish version, you could also use **LocalConfiguration_[version].yaml** and **ForeignConfiguration_[version].yaml** for a defined version. That could help you for your future deployments. E.g. LocalConfiguration_1.2.3.yaml

* [LocalConfiguration.yaml](LocalConfiguration.md)
* [ForeignConfiguration.yaml](ForeignConfiguration.md)
* [Overwrite Configuration](OverwriteConfiguration.md)
