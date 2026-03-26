#!/usr/bin/env node

import { createCLI } from './cli.js';
import { config, validate } from './config.js';
import Thermostat from './thermostat.js';
import logger from './utils/logger.js';
import noble from '@stoprocent/noble';
import { MerossSmartPlug } from 'meross-local';
import { parseMeterAd } from './utils/meter.js';

async function main() {
  const program = createCLI();
  program.parse(process.argv);

  const command = program.args[0] || 'run';

  switch (command) {
    case 'run':
      await runThermostat(program.commands.find(c => c.name() === 'run')?.opts() || {});
      break;
    case 'scan':
      await scanDevices(program.commands.find(c => c.name() === 'scan')?.opts() || {});
      break;
    case 'status':
      await showStatus();
      break;
    case 'plug':
      await controlPlug(program.args[1]);
      break;
    case 'temp':
      await readTemp();
      break;
    default:
      await runThermostat({});
  }
}

async function runThermostat(opts) {
  const errors = validate();
  if (errors.length > 0) {
    logger.error({ errors }, 'Configuration errors');
    process.exit(1);
  }

  const thermostat = new Thermostat({
    lowThreshold: opts.low,
    highThreshold: opts.high,
    scanIntervalMs: opts.interval,
  });

  process.on('SIGINT', async () => {
    logger.info('Received SIGINT, shutting down...');
    await thermostat.stop();
    process.exit(0);
  });

  process.on('SIGTERM', async () => {
    logger.info('Received SIGTERM, shutting down...');
    await thermostat.stop();
    process.exit(0);
  });

  await thermostat.start();
}

async function scanDevices(opts) {
  logger.info('Scanning for SwitchBot Meter devices...');
  const devices = [];
  const timeoutMs = (opts.timeout || 30) * 1000;

  await noble.waitForPoweredOnAsync();

  noble.on('discover', (peripheral) => {
    const data = parseMeterAd(peripheral);
    if (!data) return;
    if (!devices.find(d => d.id === data.id)) {
      devices.push(data);
      logger.info(data, 'Found device');
    }
  });

  await noble.startScanningAsync([], true);

  setTimeout(async () => {
    noble.removeAllListeners('discover');
    await noble.stopScanningAsync().catch(() => {});
    console.log('\nDiscovered devices:');
    console.table(devices);
    process.exit(0);
  }, timeoutMs);
}

async function showStatus() {
  const errors = validate();
  if (errors.length > 0) {
    logger.error({ errors }, 'Configuration errors');
    process.exit(1);
  }

  const meross = new MerossSmartPlug(config.meross.address, config.meross.key);
  try {
    const power = await meross.getPower();
    console.log(`Plug state: ${power ? 'ON' : 'OFF'}`);
  } catch (err) {
    logger.error({ err }, 'Failed to get plug status');
  }

  await noble.waitForPoweredOnAsync();

  noble.on('discover', async (peripheral) => {
    const data = parseMeterAd(peripheral);
    if (!data) return;
    noble.removeAllListeners('discover');
    await noble.stopScanningAsync().catch(() => {});
    console.log(`Temperature: ${data.tempF.toFixed(1)}F (${data.tempC}C)`);
    console.log(`Humidity: ${data.humidity}%`);
    process.exit(0);
  });

  await noble.startScanningAsync([], true);

  setTimeout(() => {
    console.log('No temperature reading available');
    process.exit(1);
  }, 10000);
}

async function controlPlug(action) {
  const errors = validate();
  if (errors.length > 0) {
    logger.error({ errors }, 'Configuration errors');
    process.exit(1);
  }

  const meross = new MerossSmartPlug(config.meross.address, config.meross.key);

  switch (action) {
    case 'on':
      await meross.turnOn();
      console.log('Plug turned ON');
      break;
    case 'off':
      await meross.turnOff();
      console.log('Plug turned OFF');
      break;
    case 'status':
      const power = await meross.getPower();
      console.log(`Plug is ${power ? 'ON' : 'OFF'}`);
      break;
    default:
      console.error('Invalid action. Use: on, off, or status');
      process.exit(1);
  }
  process.exit(0);
}

async function readTemp() {
  await noble.waitForPoweredOnAsync();

  noble.on('discover', async (peripheral) => {
    const data = parseMeterAd(peripheral);
    if (!data) return;
    noble.removeAllListeners('discover');
    await noble.stopScanningAsync().catch(() => {});
    console.log(`${data.tempF.toFixed(1)}`);
    process.exit(0);
  });

  await noble.startScanningAsync([], true);

  setTimeout(() => {
    console.error('No temperature reading');
    process.exit(1);
  }, 10000);
}

main().catch((err) => {
  logger.error({ err }, 'Fatal error');
  process.exit(1);
});
