import "dotenv/config";

export const config = {
  meross: {
    address: process.env.MEROSS_PLUG_ADDRESS,
    key: process.env.MEROSS_KEY,
  },
  wyze: {
    email: process.env.WYZE_EMAIL,
    password: process.env.WYZE_PASSWORD,
    keyId: process.env.WYZE_KEY_ID,
    apiKey: process.env.WYZE_API_KEY,
    mac: process.env.WYZE_DEVICE_MAC,
  },
  thermostat: {
    lowThreshold: parseFloat(process.env.THERMOSTAT_LOW_THRESHOLD) || 68,
    highThreshold: parseFloat(process.env.THERMOSTAT_HIGH_THRESHOLD) || 72,
  },
  switchbot: {
    deviceId: process.env.SWITCHBOT_DEVICE_ID || null,
  },
  scanIntervalMs: parseInt(process.env.SCAN_INTERVAL_MS, 10) || 30000,
  logLevel: process.env.LOG_LEVEL || "info",
};

export function validate() {
  const errors = [];
  const hasMeross = config.meross.address && config.meross.key;
  const hasWyze = config.wyze.email && config.wyze.password && config.wyze.mac;
  if (!hasMeross && !hasWyze) {
    errors.push(
      "At least one plug must be configured: set MEROSS_PLUG_ADDRESS + MEROSS_KEY, or WYZE_EMAIL + WYZE_PASSWORD + WYZE_DEVICE_MAC",
    );
  }
  if (config.wyze.email && (!config.wyze.keyId || !config.wyze.apiKey)) {
    errors.push("WYZE_KEY_ID and WYZE_API_KEY are required when using Wyze");
  }
  if (config.thermostat.lowThreshold >= config.thermostat.highThreshold) {
    errors.push("LOW_THRESHOLD must be less than HIGH_THRESHOLD");
  }
  return errors;
}
