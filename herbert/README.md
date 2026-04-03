# Herbert - Smart Thermostat Controller

Control your heater using a SwitchBot Meter and Meross smart plug.

## Prerequisites

- Linux with Bluetooth support
- Node.js >= 18.0.0
- Bluetooth libraries:
  ```bash
  sudo apt-get install bluetooth bluez libbluetooth-dev libudev-dev
  ```

## Setup

1. Install dependencies:
   ```bash
   npm install
   ```

2. Copy `.env` and configure:
   ```bash
   # Required
   MEROSS_PLUG_ADDRESS=192.168.1.100  # Your Meross plug IP
   MEROSS_KEY=your_key_here           # Your Meross device key

   # Optional
   THERMOSTAT_LOW_THRESHOLD=68        # Turn on below this temp (F)
   THERMOSTAT_HIGH_THRESHOLD=72       # Turn off above this temp (F)
   SCAN_INTERVAL_MS=30000             # Polling interval
   LOG_LEVEL=info                     # debug, info, warn, error
   ```

3. Run `npm run scan` to find your SwitchBot Meter device ID (optional)

## Usage

### Run Thermostat (default)
```bash
npm start
# or with options
node src/index.js run --low 65 --high 70 --interval 60000
```

### Scan for Devices
```bash
npm run scan
```

### Check Status
```bash
npm run status
```

### Control Plug Directly
```bash
node src/index.js plug on
node src/index.js plug off
node src/index.js plug status
```

### One-shot Temperature Reading
```bash
node src/index.js temp
```

## How It Works

The thermostat uses hysteresis to prevent rapid on/off cycling:

- Turn plug **ON** when temperature drops **below** low threshold (default: 68F)
- Turn plug **OFF** when temperature rises **above** high threshold (default: 72F)
- **No action** when temperature is between thresholds

## Notes

- May require `sudo` for Bluetooth access on some systems
- SwitchBot Meter broadcasts temperature via BLE advertisements
- Meross plug is controlled over local network (no cloud required)

## Getting the Meross Key

Clone the meross-login tool into the `meross-login/` directory and use it to authenticate:

```bash
git clone https://github.com/jixunmoe/meross-login.git meross-login
cd meross-login
npm ci --prod
node meross generate-config   # creates config.json
node meross login             # prompts for credentials, writes user.json
```

Your `MEROSS_KEY` is the `key` field in the resulting `user.json`.
