# 60149 Change Replace Spyc with symfony/yaml

Issue https://projekte.in2code.de/issues/60149

## Description

in2publish_core uses a YAML file for configuration since it was first develop in 2015. YAML has not been established as
a configuration language back then, so it was necessary to ship a YAML parser with the extension. Time flew by and some
TYPO3 versions later, TYPO3 uses YAML for site configuration, the Form Framework, CKE Editor configuration, and more.
We have not required any YAML parser via composer, because we also supported TYPO3 in non-composer installations.

So eventually, TYPO3 requires a YAML parser, too. We can rely on the implementation shipped with TYPO3 to reduce the
code we have to maintain, which we did in in2publish_core v12.3.0. We are now using the YAML parser shipped with TYPO3.

## Impact

symfony/yaml does not support multiple documents and a specific list type. You will have to change your configuration or
else you will experience exceptions and probably unwanted side effects.

## Affected Installations

Possibly all, probably only those who do not have the Content Publisher Enterprise Edition installed.

## Migration

1. Remove the "end of directives marker" `---` from any Content Publisher configuration YAML file.
2. Replace block collections with arrays (see Example #1 Replace block collections with arrays)

## Examples

### #1 Replace block collections with arrays
Before:
```yaml
    permission:
      definition:
        2:
          3
          4
          5
          6
          7
          8
```
After
```yaml
    permission:
      definition:
        2: [3,4,5,6,7,8]
```
