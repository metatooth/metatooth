export interface Contact {
  id: number;
  germ: string;
  contact: string;
  email: string;
  phone: string;
  createdat: Date;
  updatedat: Date;
  deleted: Boolean;
  deletedat: Date;
  [key: string]: any;
}

export interface Address {
  id: number;
  germ: string;
  address1: string;
  address2: string;
  city: string;
  statecode: string;
  postcode1: string;
  postcode2: string;
  countrycode: string;
  createdat: Date;
  updatedat: Date;
  deleted: Boolean;
  deletedat: Date;
  [key: string]: any;
}

export interface Organization {
  id: number;
  germ: string;
  organization: string;
  address: Address;
  contact: Contact;
  createdat: Date;
  updatedat: Date;
  deleted: Boolean;
  deletedat: Date;
  [key: string]: any;
}
