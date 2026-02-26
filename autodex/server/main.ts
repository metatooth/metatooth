import cors from 'cors';
import express from 'express';
import {
  Request, Response, NextFunction,
} from 'express';
import favicon from 'serve-favicon';
import http from 'http';
import path from 'path';
import { fileURLToPath } from 'url';

import { query } from '../lib/db.js';
import { Contact, Address, Organization } from '../lib/types.js';

const filename = fileURLToPath(import.meta.url);
const dirname = path.dirname(filename);

const port = process.env.API_PORT || 8888;

const app = express();

app.use(cors());

app.use(favicon(path.join(dirname, 'favicon.ico')));

app.use((err: any, req: Request, res: Response, next: NextFunction) => {
  res.status(err.status || 500);
  res.json({
    message: err.message,
    error: err,
  });
  next();
});

app.get('/', (req, res) => {
  res.send('OK');
});

app.get('/contacts', async (req, res) => {
  const { rows } = await query<Contact>(
    'SELECT c.id as cid, c.contact, c.phone, c.email, \
o.id as oid, o.organization, \
a.id as aid, a.address1, a.city, a.statecode, a.postcode1, a.postcode2, a.countrycode \
FROM contacts c \
JOIN organization_contacts oc ON c.id = oc.contactid \
JOIN organizations o ON o.id = oc.organizationid \
JOIN addresses a ON a.id = o.addressid \
WHERE c.deleted <> true', []);
  res.status(200).json(rows);
});

app.get('/addresses', async (req, res) => {
  const { rows } = await query<Address>('SELECT * FROM addresses WHERE deleted <> true', []);
  res.status(200).json(rows);
});

app.get('/organizations', async (req, res) => {
  const { rows } = await query<Organization>('SELECT * FROM organizations WHERE o.deleted <> true', []);
  res.status(200).json(rows);
});

app.delete('/contacts/:id', async (req, res) => {
  const { id } = req.params;
  query<Organization>('UPDATE contacts SET deleted = true, deletedat = CURRENT_TIMESTAMP WHERE id = $1', [id]);
  res.status(204).json({});
});

process.env.TZ = 'ETC/Utc';

const server = http.createServer(app);
server.listen(port);
console.log('http server listening on %d', port);
