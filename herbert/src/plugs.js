import { MerossSmartPlug } from 'meross-local';
import Wyze from 'wyze-node';
import { config } from './config.js';

export class MerossPlug {
  constructor({ address, key }) {
    this.client = new MerossSmartPlug(address, key);
    this.name = `meross:${address}`;
  }

  async turnOn() { await this.client.turnOn(); }
  async turnOff() { await this.client.turnOff(); }
  async getPower() { return this.client.getPower(); }
}

export class WyzePlug {
  constructor({ email, password, keyId, apiKey, mac }) {
    this.client = new Wyze({ username: email, password, keyId, apiKey });
    this.mac = mac;
    this.name = `wyze:${mac}`;
    this._device = null;
  }

  async _getDevice() {
    if (!this._device) {
      const devices = await this.client.getDeviceList();
      this._device = devices.find(d => d.mac === this.mac);
      if (!this._device) throw new Error(`Wyze device not found: ${this.mac}`);
    }
    return this._device;
  }

  async turnOn() {
    const device = await this._getDevice();
    await this.client.turnOn(device);
  }

  async turnOff() {
    const device = await this._getDevice();
    await this.client.turnOff(device);
  }

  async getPower() {
    const device = await this._getDevice();
    const propertyList = await this.client.getPropertyList(device.mac, device.product_model);
    const prop = propertyList.find(p => p.pid === 'P3');
    return prop?.value === '1';
  }
}

export function createPlugs() {
  const plugs = [];
  if (config.meross.address && config.meross.key) {
    plugs.push(new MerossPlug(config.meross));
  }
  if (config.wyze.email && config.wyze.password && config.wyze.mac) {
    plugs.push(new WyzePlug(config.wyze));
  }
  return plugs;
}
