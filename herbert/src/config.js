import 'dotenv/config';

export const config = {
  meross: {
    address: process.env.MEROSS_PLUG_ADDRESS,
    key: process.env.MEROSS_KEY,
  },
  thermostat: {
    lowThreshold: parseFloat(process.env.THERMOSTAT_LOW_THRESHOLD) || 68,
    highThreshold: parseFloat(process.env.THERMOSTAT_HIGH_THRESHOLD) || 72,
  },
  switchbot: {
    deviceId: process.env.SWITCHBOT_DEVICE_ID || null,
  },
  scanIntervalMs: parseInt(process.env.SCAN_INTERVAL_MS, 10) || 30000,
  logLevel: process.env.LOG_LEVEL || 'info',
};

export function validate() {
  const errors = [];
  if (!config.meross.address) errors.push('MEROSS_PLUG_ADDRESS is required');
  if (!config.meross.key) errors.push('MEROSS_KEY is required');
  if (config.thermostat.lowThreshold >= config.thermostat.highThreshold) {
    errors.push('LOW_THRESHOLD must be less than HIGH_THRESHOLD');
  }
  return errors;
}
