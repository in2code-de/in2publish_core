# Extension Installation

The installation requires the following steps on both the »Local« and
»Foreign« system:

1. Install the extension in TYPO3

  - Use the Extension Manager, Composer or a copy of the Git repository
  - Please take a look at the "in2code GitHub tutorial" if you need more details

2. Activate the Extension
3. Set the path to the configuration files of the extension
  - The configuration files are created next in the chapter "configuration"
  - TYPO3 < 9: Extensions > "in2publish_core" > Button Configure
  - TYPO3 >= 9: Settings > Extension Configuration > Button Configure
    Extensions > "in2publish_core"
  - Change the value `pathToConfiguration` pointing to the configuration file
    folder, yet to be created
4. Make sure the environment variable `IN2PUBLISH_CONTEXT` is set,
   otherwise the extension modules are not visible
  - See [Preparation](1_Preparation.md)

## New modules on Local

![Publisher backend modules](_img/modules.png)

If the environment configuration is correct, then you will see three or four
new backend modules on the »Local« system, based on your version of the
Content Publisher and it's installed expansions.

»Foreign« won't show any modules, since all module actions are on »Local« only.

## New command controllers in scheduler module on Foreign

![Command controller list](_img/command_controller.png)

On »Foreign« you will see a couple of new command controllers in the
scheduler module.

---

**Continue with [Configuration](3_Configuration.md)**
