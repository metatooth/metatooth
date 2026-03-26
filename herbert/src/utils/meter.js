const SWITCHBOT_COMPANY_ID = 0x0969;

export function parseMeterAd(peripheral) {
  const mfr = peripheral.advertisement?.manufacturerData;
  if (!mfr || mfr.length < 13) return null;
  if (mfr.readUInt16LE(0) !== SWITCHBOT_COMPANY_ID) return null;

  const tempFrac = (mfr[10] & 0x0f) / 10;
  const tempSign = (mfr[11] & 0x80) ? 1 : -1;
  const tempC = tempSign * ((mfr[11] & 0x7f) + tempFrac);
  const tempF = Math.round((tempC * 9 / 5 + 32) * 10) / 10;

  return {
    id: peripheral.id,
    address: peripheral.address,
    tempC,
    tempF,
    humidity: mfr[12] & 0x7f,
  };
}
