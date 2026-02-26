import fs from 'fs';
import { parse } from '@fast-csv/parse';
import { Contact, Address, Organization } from '../lib/types';
import { findOrCreateContact,
         findOrCreateAddress,
         findOrCreateOrganization,
         addContactToOrganization } from '../lib/db.js';

async function handler(
  contact: string,
  phone: string,
  email: string,
  address1: string,
  city: string,
  statecode: string,
  postcode1: string,
  postcode2: string,
  organization: string,
  germ: string): Promise<void> {
    return await findOrCreateContact(contact, phone, email, germ)
      .then(async (cid) => {
        return await findOrCreateAddress(
          address1, city, statecode, postcode1, postcode2, germ)
          .then(async (aid) => {
            return await findOrCreateOrganization(organization, aid, germ)
              .then(async (oid) => {
                return await addContactToOrganization(oid, cid)
                  .then(async () => {
                    return;
                  });
              });
          });
      });
  }

async function handleFive(entry: Array<string>): Promise<void> {
  let org = 0;
  const addy = entry[1].split(',');
  if (addy.length === 2) {
    return await handler(
      entry[2].trim(), entry[3].trim(), entry[4].trim(),
      "", addy[0].trim(), addy[1].trim(), "", "",
      entry[0].trim(),
      entry.join(","));
  } else {
    console.log('unhandled 5', entry);
  }

  return;
}

async function handleSeven(entry: Array<string>): Promise<void> {
  const addy = entry[1].split(',');
  if (addy.length === 2) {
    return await handler(
      entry[2].trim(), entry[3].trim(), entry[4].trim(),
      "", addy[0].trim(), addy[1].trim(), "", "",
      entry[0].trim(),
      entry.join(","));
  }

  console.log('unhandled 7', entry);
  return;
}

async function handleEleven(entry: Array<string>): Promise<void> {
  const parts = entry[1].split(',');
  const num = parts.length;

  if (num > 1) {
    const statezip = parts[parts.length - 1].trim().split(' ');

    if (statezip.length === 2) {
      const state = statezip[0];
      let postcode = statezip[1];
      let postcode4 = '';

      if (postcode.match('-')) {
        const codes = postcode.split('-');
        [postcode, postcode4] = codes;
      }

      const addy = parts[0].split(' ');
      let city = addy[addy.length - 1];

      addy.pop();
      let address = addy.join(' ');

      if (num === 3) {
        parts.pop();
        const t = parts[1].split(' ');
        city = t[t.length - 1];

        t.pop();
        address = `${parts[0]} ${t.join(' ')}`;
      }

      return await handler(
        entry[2].trim(), entry[4].trim(), entry[5].trim(),
        address.trim(), city.trim(), state.trim(), postcode.trim(), postcode4.trim(),
        entry[0].trim(),
        entry.join(","));

    } else if (parts.length === 2) {


      return await handler(
        entry[2].trim(), entry[4].trim(), entry[5].trim(),
        "", parts[0].trim(), parts[1].trim(), "", "",
        entry[0].trim(),
        entry.join(","));

    } else {
      console.log('unhandled 11 statezip', entry);
    }
  } else {
    console.log('unhandled 11 parts', entry);
  }

  return;
}

async function handleTwelve(entry: Array<string>): Promise<void> {
  return await handler(
    entry[1].trim(), "", entry[7],
    entry[2].trim(), entry[3].trim(), entry[4].trim(), entry[5].trim(), entry[6].trim(),
    entry[0].trim(),
    entry.join(","));
}

async function handleThirtyThree(entry: Array<string>): Promise<void> {
  const parts = entry[1].split(',');
  const num = parts.length;

  if (num === 2) {
    let addy = parts[parts.length - 1].trim().split(' ');

    if (addy.length === 1) {
      const city = parts[0].trim();
      const state = parts[1].trim();

      return await handler(
        entry[2].trim(), "", entry[3].trim(),
        "", city.trim(), state.trim(), "", "",
        entry[0].trim(),
        entry.join(","));

    } else if (addy.length === 2) {
      const state = addy[0];
      const zip = addy[1];

      addy = parts[0].trim().split(' ');

      const city = addy[addy.length - 1];

      addy.pop();
      const address = addy.join(' ');

      return await handler(
        entry[2].trim(), "", entry[3].trim(),
        address.trim(), city.trim(), state.trim(), zip.trim(), "",
        entry[0].trim(),
        entry.join(","));

    } else {
      console.log('unhandled 33 state zip', entry);
    }
  } else {
    console.log('unhandled 33', entry);
  }

  return;
}

export default async function etl(path: string) {
  console.log(path);

  const rows: Array<Array<string>> = [];

  await fs.createReadStream(path)
    .pipe(parse())
    .on('data', async (row) => {
      rows.push(row);
    })
    .on('end', async (rowCount: number) => {
      console.log(`Parsed ${rowCount} rows`);
      for (const row in rows) {
        if (rows[row].length === 5) {
          await handleFive(rows[row]);
        } else if (rows[row].length === 7) {
          await handleSeven(rows[row]);
        } else if (rows[row].length === 11) {
          await handleEleven(rows[row]);
        } else if (rows[row].length === 12) {
          await handleTwelve(rows[row]);
        } else if (rows[row].length === 33) {
          await handleThirtyThree(rows[row]);
        } else {
          console.log('unhandled', rows[row].length, rows[row]);
        }
      }

    });

}
