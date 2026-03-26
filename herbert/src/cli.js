import { Command } from 'commander';
import { createRequire } from 'module';

const require = createRequire(import.meta.url);
const pkg = require('../package.json');

export function createCLI() {
  const program = new Command();

  program
    .name('herbert')
    .description('Thermostat controller for SwitchBot Meter and Meross smart plug')
    .version(pkg.version);

  program
    .command('run', { isDefault: true })
    .description('Run the thermostat control loop')
    .option('-l, --low <temp>', 'Low temperature threshold (F)', parseFloat)
    .option('-h, --high <temp>', 'High temperature threshold (F)', parseFloat)
    .option('-i, --interval <ms>', 'Scan interval in milliseconds', parseInt);

  program
    .command('scan')
    .description('Scan for SwitchBot Meter devices')
    .option('-t, --timeout <seconds>', 'Scan timeout', parseInt, 30);

  program
    .command('status')
    .description('Get current temperature and plug state');

  program
    .command('plug')
    .description('Control plug directly: on, off, or status')
    .argument('<action>', 'on, off, or status');

  program
    .command('temp')
    .description('Read temperature once and exit');

  return program;
}
