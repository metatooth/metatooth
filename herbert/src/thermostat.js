import noble from '@stoprocent/noble';
import { MerossSmartPlug } from 'meross-local';
import { config } from './config.js';
import logger from './utils/logger.js';
import { parseMeterAd } from './utils/meter.js';

export default class Thermostat {
  constructor(options = {}) {
    this.lowThreshold = options.lowThreshold || config.thermostat.lowThreshold;
    this.highThreshold = options.highThreshold || config.thermostat.highThreshold;
    this.scanIntervalMs = options.scanIntervalMs || config.scanIntervalMs;

    this.meross = new MerossSmartPlug(config.meross.address, config.meross.key);
    this.nobleReady = noble.waitForPoweredOnAsync();

    this.currentTemp = null;
    this.plugState = null;
    this.isRunning = false;
  }

  async start() {
    logger.info({
      lowThreshold: this.lowThreshold,
      highThreshold: this.highThreshold,
      scanIntervalMs: this.scanIntervalMs,
    }, 'Starting thermostat');

    await this.nobleReady;
    this.isRunning = true;

    await this.updatePlugState();
    await this.runControlLoop();
  }

  async stop() {
    logger.info('Stopping thermostat');
    this.isRunning = false;
    noble.removeAllListeners('discover');
    await noble.stopScanningAsync().catch(() => {});
  }

  async runControlLoop() {
    while (this.isRunning) {
      try {
        const temp = await this.readTemperature();
        if (temp !== null) {
          this.currentTemp = temp;
          await this.evaluateAndControl(temp);
        }
      } catch (err) {
        logger.error({ err }, 'Error in control loop');
      }

      await this.sleep(this.scanIntervalMs);
    }
  }

  async readTemperature() {
    return new Promise((resolve) => {
      let resolved = false;

      const done = (value) => {
        if (resolved) return;
        resolved = true;
        clearTimeout(timeout);
        noble.removeAllListeners('discover');
        noble.stopScanningAsync().catch(() => {});
        resolve(value);
      };

      const timeout = setTimeout(() => {
        logger.warn('BLE scan timeout - no meter found');
        done(null);
      }, 10000);

      noble.removeAllListeners('discover');
      noble.on('discover', (peripheral) => {
        const data = parseMeterAd(peripheral);
        if (!data) return;
        if (config.switchbot.deviceId && peripheral.id !== config.switchbot.deviceId) return;

        logger.debug({ ...data, deviceId: peripheral.id }, 'Temperature reading');
        done(data.tempF);
      });

      noble.startScanningAsync([], true).catch((err) => {
        logger.error({ err }, 'BLE scan failed');
        done(null);
      });
    });
  }

  async evaluateAndControl(tempF) {
    logger.info({ tempF, plugState: this.plugState }, 'Evaluating temperature');

    if (tempF < this.lowThreshold && this.plugState !== 'on') {
      logger.info({ tempF, threshold: this.lowThreshold }, 'Temperature below low threshold - turning ON');
      await this.turnPlugOn();
    } else if (tempF > this.highThreshold && this.plugState !== 'off') {
      logger.info({ tempF, threshold: this.highThreshold }, 'Temperature above high threshold - turning OFF');
      await this.turnPlugOff();
    } else {
      logger.debug({ tempF, plugState: this.plugState }, 'Temperature in range - no action');
    }
  }

  async turnPlugOn() {
    try {
      await this.meross.turnOn();
      this.plugState = 'on';
      logger.info('Plug turned ON');
    } catch (err) {
      logger.error({ err }, 'Failed to turn plug ON');
    }
  }

  async turnPlugOff() {
    try {
      await this.meross.turnOff();
      this.plugState = 'off';
      logger.info('Plug turned OFF');
    } catch (err) {
      logger.error({ err }, 'Failed to turn plug OFF');
    }
  }

  async updatePlugState() {
    try {
      const power = await this.meross.getPower();
      this.plugState = power ? 'on' : 'off';
      logger.info({ plugState: this.plugState }, 'Initial plug state');
    } catch (err) {
      logger.error({ err }, 'Failed to get plug state');
      this.plugState = null;
    }
  }

  celsiusToFahrenheit(c) {
    return (c * 9 / 5) + 32;
  }

  sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  getStatus() {
    return {
      currentTemp: this.currentTemp,
      plugState: this.plugState,
      lowThreshold: this.lowThreshold,
      highThreshold: this.highThreshold,
      isRunning: this.isRunning,
    };
  }
}
