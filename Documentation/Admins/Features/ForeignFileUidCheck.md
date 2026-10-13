# Foreign File UID Check

A direct file upload on Foreign can advance the `sys_file` UID sequence beyond Local. A later publication can then reuse a UID already used on Foreign and make TYPO3 show the wrong file.

The Publish Overview and Publish Files modules compare the `sys_file` AUTO_INCREMENT values when opened. The check makes two metadata queries and does not scan file rows. If the Foreign sequence is ahead, the modules show a warning with both values and the collision risk.

A backend user can accept the warning. The UID check is then skipped for that user. To disable the check and warning for everyone, set this option in the local in2publish configuration:

```yaml
features:
  foreignFileUidCheck:
    enable: FALSE
```

The setting is enabled by default.
