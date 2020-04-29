# Command Controller Exit Codes

## List of exit codes

* `220`: The table you tried to publish or import does not exist on the target database.
* `221`: An error ocurred while backing up the table on remote.
* `230`: The RPC/Envelope identified by the argument `uid` did not exist.
* `231`: No argument `uid` was given to identify an RPC/Envelope. The command must contain a `uid` like `rpc:execute 4`.
* `232`: Executing the RPC/Envelope failed. More information should be available in the logs.
* `240`: At least one test failed, so in2publish_core is not ready to be used.
* `250`: No site configuration could be found for the given page id.

## API stability

These exit codes SHOULD not change, but if they do it MUST be commited as breaking change (from now on),
but they are still not reliable because TYPO3 <= 8.7 will pass any code to the PHP function
exit() where integer overflow will occur and exception code overflow results might intersect
with exit codes denoted here. In TYPO3 >= v9 exit codes will be limited by symfony console to 255.

You can try to identify problems based on the return code but it is rather safe to parse the
command output (beware: TYPO3 does not strictly use stderr for error messages!)
