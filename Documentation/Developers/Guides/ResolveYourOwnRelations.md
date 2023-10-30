# How to resolve your own relations

TYPO3 is extensible by nature, and so is the Content Publisher. You are not limited to the TCA to create relations
between records, files or even virtual objects. The Content Publisher provides an API, so you can resolve relations to
virtually anything.

## Custom TCA handling

The full customization walkthrough includes a lot of steps. You can, of course, always return a type provided by
in2publish_core (Your custom Preprocessor returns anm existing Resolver, the custom Resolver returns an existing Demand,
and so on), but the list below contains all possible steps.

1. Create a PreProcessor which implements `\In2code\In2publishCore\Component\Core\PreProcessing\TcaPreProcessor` which
   returns your Resolver.
2. Create a Resolver which implements `\In2code\In2publishCore\Component\Core\Resolver\Resolver` and returns your
   Demand.
3. Create a Demand which implements `\In2code\In2publishCore\Component\Core\Demand\Type\Demand`.
4. Create a DemandResolver which implements `\In2code\In2publishCore\Component\Core\DemandResolver\DemandResolver` and
   handles your Demand.
5. (If you query a database table) Create a DatabaseRecordFactory which
   implements `\In2code\In2publishCore\Component\Core\Record\Factory\DatabaseRecordFactory` and returns your
   DatabaseRecord.
6. (If you query a database table) Create a Record which
   extends `\In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord`.

The PreProcessor will be called at the very beginning. Its job is to identify the TCA configuration and to configure a
Resolver, which can handle the actual Record. Once called, the PreProcessor will not be used for the rest of the
request.

The Resolver is asked to identify all possible relation targets (other tables, files, etc.) based on actual records.
Since the Resolver was configured for a specific TCA column, it knows where to search and passes that information on as
Demand object.

Demand types aggregate the search for records. This is mostly multiple `SELECT * FROM table WHERE uid = X` database
queries to one `SELECT * FROM table WHERE uid IN(x, y, z)` or the gathering of information about files. Demand objects
are used to reduce queries and RCE calls (Command executions on foreign), as they are the most expensive operation the
Content Publisher requires.

DemandResolver know how to handle and execute specific Demand types. Each Demand type must have a DemandResolver, which
can handle it, or the Demand will be discarded silently. DemandResolver group queries or requests into chunks that make
sense, retrieve the requested information and converts them to Records using the RecordFactory. The RecordFactory can
only handle specific types like File and Database Records as of now, but DatabaseRecords can be sup-typed.
