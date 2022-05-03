# Custom Admin Tools

You can create your own entries in the Publish Tools module. Everything you need is an ActionController which ist tagged
with `'in2publish_core.admin_tool'`.

The tag has the following properties:

* `title`: The title that identifies the tool and is shown in the Publish Tools Module.The value is either the
  title or a splitLabel.
* `description`: The description that identifies the tool and is shown in the Publish Tools Module. The value is either
  the title or a splitLabel.
* `actions`: An comma separated list of controller action names (The methods of your controller which should be
  callable). The first
  action is the default action which will be called when the Tool was selected.
* (optional) `condition`: A condition that has to evaluate to true. The condition parts are delimited by `:` (colons)
    * `CONF`: A dot-path to the configuration value e.g. `CONF:features.remoteCacheControl.enableTool`
    * `EXTCONF`: This condition has three parts. `EXTCONF`, an extension key and a path to the extension's config
      e.g. `EXTCONF:in2publish:managedSettings`
* (optional) `before`: A comma separated list of services which must appear after this service.
* (optional) `after`: A comma separated list of services which must appear before this service.

Example `Services.yaml` (taken from the Enterprise Edition):

```yaml
services:
  In2code\In2publish\Features\RemoteCacheControl\Controller\RemoteCacheController:
    tags:
      - name: 'in2publish_core.admin_tool'
        title: 'LLL:EXT:in2publish/Resources/Private/Language/locallang.xlf:moduleselector.remote_cache_control'
        description: 'LLL:EXT:in2publish/Resources/Private/Language/locallang.xlf:moduleselector.remote_cache_control.description'
        actions: [ 'listOptions', 'clearFrontend', 'clearSystem', 'clearAll' ]
        condition: 'CONF:features.remoteCacheControl.enableTool'
```
