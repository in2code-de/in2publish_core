# Upgrading instructions for in2publish_core from v11 to v12

There has been a major rewrite of in2publish_core resulting in a significantly improved performance.
The changes are described in detail here:
* [**Breaking Changes v12**](../Developers/Changelog/25505-BreakingChanges-QueryAggregation.md):
Summary of the changes in in2publish_core v12 using QueryAggregation.


### Removed/replaced Events
There are a couple of events that have been removed without replacement. For others, an equivalent event is provided.
Please refer to [25505-BreakingChanges-QueryAggregation.md](../Developers/Changelog/25505-BreakingChanges-QueryAggregation.md) for details

### New configuration values
For file publishing there is a new, required configuration value for the path on the foreign instance where transient
 files are stored during file publishing

```
foreign:
# path of the var folder of the foreign TYPO3 CMS instance
varPath: /var/www/html/var
```

Please note that this path must be writable and on the same partition as the TYPO3 core.
