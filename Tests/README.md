# Unit Tests

## About failure messages

A failure message which begins with "[!!!]" indicates a breaking change.
You have to include "[!!!]" at the beginning of every commit that changes one of those tests in order to make it green again.

## Testing

Run `./Build/Scripts/runTests.sh -s composerInstall` initially.
Run `./Build/Scripts/runTests.sh` to run all tests
