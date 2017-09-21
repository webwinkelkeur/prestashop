const parseArgs = require('./tests/args-parser');
const runner = require('./tests/runner');
runner.run(parseArgs(process.argv, runner.defaultParams));

