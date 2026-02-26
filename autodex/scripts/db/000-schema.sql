begin;

drop table if exists events;
drop table if exists event_types;
drop table if exists organization_contacts;
drop table if exists organizations;
drop table if exists addresses;
drop table if exists contacts;

create table contacts (
  id serial primary key,
  contact varchar(255),
  email varchar(255),
  phone varchar(255),
  createdat timestamp default current_timestamp,
  updatedat timestamp default current_timestamp,
  deleted boolean default false,
  deletedat timestamp,
  germ varchar(255)
);

create table addresses (
  id serial primary key,
  address1 varchar(255),
  address2 varchar(255),
  city varchar(255),
  statecode varchar(255),
  postcode1 varchar(255),
  postcode2 varchar(255),
  countrycode varchar(255),
  createdat timestamp default current_timestamp,
  updatedat timestamp default current_timestamp,
  deleted boolean default false,
  deletedat timestamp,
  germ varchar(255)
);

create table organizations (
  id serial primary key,
  organization varchar(255),
  addressid integer references addresses(id),
  createdat timestamp default current_timestamp,
  updatedat timestamp default current_timestamp,
  deleted boolean default false,
  deletedat timestamp,
  germ varchar(255)
);

create table organization_contacts (
  organizationid integer references organizations(id),
  contactid integer references contacts(id),
  createdat timestamp default current_timestamp,
  deleted boolean default false,
  deletedat timestamp,
  primary key(organizationid, contactid)
);

create table event_types (
  eventtype varchar(255) primary key
);

insert into event_types (eventtype) values ('phone'), ('e-mail'), ('voicemail');

create table events (
  id serial primary key,
  eventtype varchar(255) references event_types(eventtype),
  description varchar(255),
  createdat timestamp default current_timestamp,
  updatedat timestamp default current_timestamp,
  deleted boolean default false,
  deletedat timestamp
);

commit;
