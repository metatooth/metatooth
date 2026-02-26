import pkg from 'pg';
const { Pool } = pkg;
import { Contact, Address, Organization } from "./types";

const pool = new Pool({
  connectionString: process.env.DATABASE_URL,
  max: 30,
  ssl: { rejectUnauthorized: false },
});

export async function query<T>(text: string, params: Array<string>): Promise<pkg.QueryResult<T>> {
  // const start = Date.now();
  const res = await pool.query<T>(text, params);
  // const duration = Date.now() - start;
  // console.log("executed query", { text, duration, rows: res.rowCount });
  return res;
}

export async function findOrCreateContact(
  contact: string,
  email: string,
  phone: string,
  germ: string
): Promise<number> {
  return await query<Contact>(
    "SELECT * FROM contacts WHERE contact = $1 AND email = $2 AND phone = $3",
    [contact, email, phone]).then(async (check) => {
      if (check.rows.length === 0) {
        return await query<Contact>(
          "INSERT INTO contacts (contact, email, phone, germ) \
VALUES ($1, $2, $3, $4) RETURNING id",
          [contact, email, phone, germ]).then(async (result) => {
            return result.rows[0].id;
          });
      } else {
        return check.rows[0].id;
      }
    });
}

export async function findOrCreateAddress(
  address1: string,
  city: string,
  statecode: string,
  postcode1: string,
  postcode2: string,
  germ: string
): Promise<number> {
  return await query<Address>(
    "SELECT * FROM addresses WHERE address1 = $1 AND \
city = $2 AND statecode = $3 AND postcode1 = $4 AND postcode2 = $5",
    [address1, city, statecode, postcode1, postcode2])
    .then(async (check) => {
      if (check.rows.length === 0) {
        return await query<Address>(
          "INSERT INTO addresses (address1, address2, city, statecode, postcode1, postcode2, countrycode, germ) \
VALUES ($1, '', $2, $3, $4, $5, 'USA', $6) RETURNING id",
          [address1, city, statecode, postcode1, postcode2, germ])
          .then(async (result) => {
            return result.rows[0].id;
          });
      } else {
        return check.rows[0].id;
      }
    });
}

export async function findOrCreateOrganization(
  organization: string,
  addressid: number,
  germ: string
): Promise<number> {
  return await query<Organization>(
    "SELECT * FROM organizations WHERE organization = $1",
    [organization]).then(async (check) => {
      if (organization === "" || check.rows.length === 0) {
        return await query<Organization>("INSERT INTO organizations (organization, addressid) VALUES ($1, $2) RETURNING id", [organization, `${addressid}`])
          .then(async (result) => {
            return result.rows[0].id;
          });
      } else {
        return check.rows[0].id;
      }
    });
}

export async function addContactToOrganization(
  organizationid: number,
  contactid: number
): Promise<boolean> {
  return await query<number>("INSERT INTO organization_contacts (organizationid, contactid) VALUES ($1, $2)", [`${organizationid}`, `${contactid}`])
    .then(() => {
      return true;
    })
    .catch(() => {
      return false;
    });
}
