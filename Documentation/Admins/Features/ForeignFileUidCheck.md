# Foreign File UID Check

A direct file upload on Foreign can advance the `sys_file` UID sequence beyond Local. A later publication can then reuse a UID already used on Foreign and make TYPO3 show the wrong file.

The PublisherTest compares the `sys_file` AUTO_INCREMENT values on Local and Foreign. It makes two metadata queries and does not scan file rows. If the Foreign sequence is ahead, the test reports a warning and the collision risk in Publisher Tools. The Publish Overview and Publish Files modules do not display this warning.

The check is enabled by default. Disable it for everyone by setting this option in the local in2publish configuration:

```yaml
features:
  foreignFileUidCheck:
    enable: FALSE
```

The setting is enabled by default.
