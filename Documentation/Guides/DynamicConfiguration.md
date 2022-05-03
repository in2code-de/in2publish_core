# Dynamic Configuration Guide

*(since in2publish_core 9.2.0)*

Dynamic Configuration Providers allow configurations values to source from a different origin, like environment
variables. The use of environment variables is good practice and widely adopted.
(see https://12factor.net/ which is not only good for SaaS but any deployable Application).

in2publish_core ships with the `env` provider by default. You can use this provider to reference environment variables
in your configuration. These references are replaced on the fly before the configuration values are cast to their type.

## Using Providers (with `env` example)

The syntax for providers is similar to the symfony config component.

Example: `%env(TYPO3_CONTEXT)%`\

* The percentage signs (`%`) at the beginning and end of the string indicate, that this value is dynamic.
* The part `env` before the opening round bracket (`)`) is the provider key.
* The contents between the round brackets will be passed to the actual provider and is therefore provider specific.
    * The value between the round brackets can contain any character expect a closing round bracket.
    * In this example the provider is `env`, which will return the value of the env var `TYPO3_CONTEXT`,
      e.g. "`Production/Stage`"

You can check if your syntax is correct with the regular expression
from `DynamicValuesPostProcessor::DYNAMIC_REFERENCE_PATTERN`.
(Or visit https://www.phpliveregex.com/p/wzP)

## Custom Provider

Your provider must implement the interface `DynamicValueProviderInterface`.

```php
<?php

/** @noinspection PhpInconsistentReturnPointsInspection */

namespace MyVendor\MyPackage\Config\PostProcessor\DynamicValueProvider;

use In2code\In2publishCore\Config\PostProcessor\DynamicValueProvider\DynamicValueProviderInterface;

class MyProvider implements DynamicValueProviderInterface
{
    /**
     * @param string $string
     * @return mixed
     */
    public function getValueFor(string $string)
    {
        // Return the value which must be returned according to $string
    }
}
```

Register your custom provider in your `ext_localconf.php`:

```php
use In2code\In2publishCore\Config\PostProcessor\DynamicValueProvider\DynamicValueProviderRegistry;
use MyVendor\MyPackage\Config\PostProcessor\DynamicValueProvider\MyProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$registry = GeneralUtility::makeInstance(DynamicValueProviderRegistry::class);
$registry->registerDynamicValue('myKey', MyProvider::class);
```

Use your provider in the configuration:

```yaml
workflow:
  states:
    mailNotify:
      sender:
        name: '%myKey(WORKFLOW_SENDER)%'
        email: '%myKey(WORKFLOW_EMAIL)%'
```

**Attention:** The dynamic value provider will be called multiple times per request with the same provider string.
If your dynamic value provider requires some more resources you should definitely consider at least a runtime caching
mechanism or even the usage of the caching framework.

## Type Casting

Example: The database port must be an integer.\
The type cast will happen after the string `%env(FOREIGN_DB_PORT)%` was replaced by the `EnvVarProvider`, so the actual
value (like `3306`) will be cast to integer.

## Wording

```text
%env(TYPO3_CONTEXT)%
^------------------^    = "Dynamic Configuration Reference" `%env(TYPO3_CONTEXT)%`
 ^-^                    = "Provider Key"                    `env`
     ^-----------^      = "Provider String"                 `TYPO3_CONTEXT`
^                  ^    = "Dynamic Reference Indicator"     `^`
```
