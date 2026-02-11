-- Create users table
CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  locator VARCHAR(255),
  type VARCHAR(255) DEFAULT 'User',
  email VARCHAR(255),
  password_digest VARCHAR(255),
  name VARCHAR(255),
  last_logged_in_at TIMESTAMP,
  confirmation_token VARCHAR(255),
  confirmation_redirect_url VARCHAR(255),
  confirmed_at TIMESTAMP,
  confirmation_sent_at TIMESTAMP,
  reset_password_token VARCHAR(255),
  reset_password_redirect_url VARCHAR(255),
  reset_password_sent_at TIMESTAMP,
  failed_attempts INTEGER DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted BOOLEAN DEFAULT FALSE,
  deleted_at TIMESTAMP
);

-- Create api_keys table
CREATE TABLE IF NOT EXISTS api_keys (
  id SERIAL PRIMARY KEY,
  api_key VARCHAR(255),
  active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create access_tokens table
CREATE TABLE IF NOT EXISTS access_tokens (
  id SERIAL PRIMARY KEY,
  user_id INTEGER REFERENCES users(id),
  api_key_id INTEGER REFERENCES api_keys(id),
  token VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted BOOLEAN DEFAULT FALSE,
  deleted_at TIMESTAMP
);

-- Create addresses table
CREATE TABLE IF NOT EXISTS addresses (
  id SERIAL PRIMARY KEY,
  user_id INTEGER REFERENCES users(id),
  name VARCHAR(255),
  address1 VARCHAR(255),
  address2 VARCHAR(255),
  city VARCHAR(255),
  state VARCHAR(255),
  zip_code VARCHAR(255),
  country VARCHAR(255),
  phone VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted BOOLEAN DEFAULT FALSE,
  deleted_at TIMESTAMP
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
  id SERIAL PRIMARY KEY,
  user_id INTEGER REFERENCES users(id),
  bill_id INTEGER REFERENCES addresses(id),
  ship_id INTEGER REFERENCES addresses(id),
  locator VARCHAR(255),
  status VARCHAR(255),
  shipped_impression_kit_at TIMESTAMP,
  received_impression_kit_at TIMESTAMP,
  shipped_custom_night_guard_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted BOOLEAN DEFAULT FALSE,
  deleted_at TIMESTAMP
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
  id SERIAL PRIMARY KEY,
  name VARCHAR(255),
  description TEXT,
  price DECIMAL(10, 2),
  sku VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted BOOLEAN DEFAULT FALSE,
  deleted_at TIMESTAMP
);

-- Create order_items table
CREATE TABLE IF NOT EXISTS order_items (
  id SERIAL PRIMARY KEY,
  order_id INTEGER REFERENCES orders(id),
  product_id INTEGER REFERENCES products(id),
  quantity INTEGER,
  price DECIMAL(10, 2),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted BOOLEAN DEFAULT FALSE,
  deleted_at TIMESTAMP
);

-- Create assets table
CREATE TABLE IF NOT EXISTS assets (
  id SERIAL PRIMARY KEY,
  user_id INTEGER REFERENCES users(id),
  locator VARCHAR(255),
  name VARCHAR(255),
  url VARCHAR(255),
  asset_type VARCHAR(255),
  mime_type VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted BOOLEAN DEFAULT FALSE,
  deleted_at TIMESTAMP
);

-- Create plans table
CREATE TABLE IF NOT EXISTS plans (
  id SERIAL PRIMARY KEY,
  locator VARCHAR(255),
  name VARCHAR(255),
  description TEXT,
  price DECIMAL(10, 2),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted BOOLEAN DEFAULT FALSE,
  deleted_at TIMESTAMP
);

-- Create revisions table
CREATE TABLE IF NOT EXISTS revisions (
  id SERIAL PRIMARY KEY,
  plan_id INTEGER REFERENCES plans(id),
  locator VARCHAR(255),
  version INTEGER,
  number INTEGER,
  location VARCHAR(255),
  name VARCHAR(255),
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted BOOLEAN DEFAULT FALSE,
  deleted_at TIMESTAMP
);
