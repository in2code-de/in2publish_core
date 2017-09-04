# Command Controller Exit Codes

## List of exit codes

* `210`: No context has been defined for in2publish_core. Set IN2PUBLISH_CONTEXT before calling a command controller on the cli.
* `211`: The called command does is not available in the given context. (You are most probably on the wrong server).
* `220`: The table you tried to publish or import does not exist on the target database.
* `230`: The RPC/Envelope identified by the argument `uid` did not exist.
* `231`: No argument `uid` was given to identify an RPC/Envelope. The command must contain a `uid` like `rpc:execute 4`.
* `232`: Executing the RPC/Envelope failed. More information should be available in the logs.

## API stability

These exit codes SHOULD not change, but if they do it MUST be commited as breaking change (from now on),
but they are still not reliable because TYPO3 <= 8.7 will pass any code to the PHP function
exit() where integer overflow will occur and exception code overflow results might intersect
with exit codes denoted here. In TYPO3 >= v9 exit codes will be limited by symfony console to 255.

You can try to identify problems based on the return code but it is rather safe to parse the
command output (beware: TYPO3 does not strictly use stderr for error messages!)
