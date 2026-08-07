#!/usr/bin/env node

import { createCLI } from "./cli.js";
import { validate } from "./config.js";
import { createPlugs } from "./plugs.js";
import Thermostat from "./thermostat.js";
import logger from "./utils/logger.js";
import noble from "@stoprocent/noble";
import { parseMeterAd } from "./utils/meter.js";

async function main() {
  const program = createCLI();
  program.parse(process.argv);

  const command = program.args[0] || "run";

  switch (command) {
    case "run":
      await runThermostat(
        program.commands.find((c) => c.name() === "run")?.opts() || {},
      );
      break;
    case "scan":
      await scanDevices(
        program.commands.find((c) => c.name() === "scan")?.opts() || {},
      );
      break;
    case "status":
      await showStatus();
      break;
    case "plug":
      await controlPlug(program.args[1]);
      break;
    case "temp":
      await readTemp();
      break;
    case "cycle":
      await cyclePlug(
        program.commands.find((c) => c.name() === "cycle")?.opts() || {},
      );
      break;
    default:
      await runThermostat({});
  }
}

async function runThermostat(opts) {
  const errors = validate();
  if (errors.length > 0) {
    logger.error({ errors }, "Configuration errors");
    process.exit(1);
  }

  const thermostat = new Thermostat({
    lowThreshold: opts.low,
    highThreshold: opts.high,
    scanIntervalMs: opts.interval,
  });

  process.on("SIGINT", async () => {
    logger.info("Received SIGINT, shutting down...");
    await thermostat.stop();
    process.exit(0);
  });

  process.on("SIGTERM", async () => {
    logger.info("Received SIGTERM, shutting down...");
    await thermostat.stop();
    process.exit(0);
  });

  await thermostat.start();
}

async function scanDevices(opts) {
  logger.info("Scanning for SwitchBot Meter devices...");
  const devices = [];
  const timeoutMs = (opts.timeout || 30) * 1000;

  await noble.waitForPoweredOnAsync();

  noble.on("discover", (peripheral) => {
    const data = parseMeterAd(peripheral);
    if (!data) return;
    if (!devices.find((d) => d.id === data.id)) {
      devices.push(data);
      logger.info(data, "Found device");
    }
  });

  await noble.startScanningAsync([], true);

  setTimeout(async () => {
    noble.removeAllListeners("discover");
    await noble.stopScanningAsync().catch(() => {});
    console.log("\nDiscovered devices:");
    console.table(devices);
    process.exit(0);
  }, timeoutMs);
}

async function showStatus() {
  const errors = validate();
  if (errors.length > 0) {
    logger.error({ errors }, "Configuration errors");
    process.exit(1);
  }

  const plugs = createPlugs();
  for (const plug of plugs) {
    try {
      const power = await plug.getPower();
      console.log(`${plug.name}: ${power ? "ON" : "OFF"}`);
    } catch (err) {
      logger.error({ err, plug: plug.name }, "Failed to get plug status");
    }
  }

  await noble.waitForPoweredOnAsync();

  noble.on("discover", async (peripheral) => {
    const data = parseMeterAd(peripheral);
    if (!data) return;
    noble.removeAllListeners("discover");
    await noble.stopScanningAsync().catch(() => {});
    console.log(`Temperature: ${data.tempF.toFixed(1)}F (${data.tempC}C)`);
    console.log(`Humidity: ${data.humidity}%`);
    process.exit(0);
  });

  await noble.startScanningAsync([], true);

  setTimeout(() => {
    console.log("No temperature reading available");
    process.exit(1);
  }, 10000);
}

async function controlPlug(action) {
  const errors = validate();
  if (errors.length > 0) {
    logger.error({ errors }, "Configuration errors");
    process.exit(1);
  }

  const plugs = createPlugs();

  switch (action) {
    case "on":
      await Promise.all(plugs.map((p) => p.turnOn()));
      console.log("Plugs turned ON");
      break;
    case "off":
      await Promise.all(plugs.map((p) => p.turnOff()));
      console.log("Plugs turned OFF");
      break;
    case "status":
      for (const plug of plugs) {
        try {
          const power = await plug.getPower();
          console.log(`${plug.name}: ${power ? "ON" : "OFF"}`);
        } catch (err) {
          logger.error({ err, plug: plug.name }, "Failed to get plug status");
        }
      }
      break;
    default:
      console.error("Invalid action. Use: on, off, or status");
      process.exit(1);
  }
  process.exit(0);
}

async function readTemp() {
  await noble.waitForPoweredOnAsync();

  noble.on("discover", async (peripheral) => {
    const data = parseMeterAd(peripheral);
    if (!data) return;
    noble.removeAllListeners("discover");
    await noble.stopScanningAsync().catch(() => {});
    console.log(`${data.tempF.toFixed(1)}`);
    process.exit(0);
  });

  await noble.startScanningAsync([], true);

  setTimeout(() => {
    console.error("No temperature reading");
    process.exit(1);
  }, 10000);
}

async function cyclePlug(opts) {
  const errors = validate();
  if (errors.length > 0) {
    logger.error({ errors }, "Configuration errors");
    process.exit(1);
  }

  const plugs = createPlugs().filter((p) => p.name.startsWith("wyze:"));
  if (plugs.length === 0) {
    logger.error("No Wyze plug configured");
    process.exit(1);
  }

  const intervalMs = opts.interval || 3000;
  let running = true;

  process.on("SIGINT", () => {
    logger.info("Received SIGINT, stopping cycle...");
    running = false;
  });

  logger.info(
    { plugs: plugs.map((p) => p.name), intervalMs },
    "Cycling plug on and off",
  );

  while (running) {
    for (const plug of plugs) {
      await plug.turnOn();
      logger.info({ plug: plug.name }, "Plug ON");
    }
    await sleep(intervalMs);
    if (!running) break;

    for (const plug of plugs) {
      await plug.turnOff();
      logger.info({ plug: plug.name }, "Plug OFF");
    }
    await sleep(intervalMs);
  }

  process.exit(0);
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

main().catch((err) => {
  logger.error({ err }, "Fatal error");
  process.exit(1);
});
