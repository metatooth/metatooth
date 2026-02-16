# Metatooth API Documentation

## Overview

The Metatooth API is a RESTful web service for managing dental orders, products, and customer information. It provides comprehensive endpoints for order management, product catalog, user management, and address management.

## Base URL

```
https://api.metatooth.com
```

## Authentication

All API endpoints (except user registration) require authentication using API keys and access tokens.

### Authentication Header Format

```
Authorization: Metaspace-Token api_key=<api_key_id>:<api_key_secret>, access_token=<user_id>:<token>
```

### Example Request

```bash
curl -H "Authorization: Metaspace-Token api_key=1:abc123, access_token=42:xyz789" \
  https://api.metatooth.com/orders
```

## Response Format

All responses are JSON formatted with a `data` root key for successful responses:

```json
{
  "data": [
    {
      "id": 1,
      "user_id": 42,
      "status": "pending",
      "created_at": "2024-01-15T10:30:00-05:00"
    }
  ]
}
```

## Error Responses

Error responses include an `error` key with details:

```json
{
  "error": {
    "message": "Invalid parameters for resource Hash.",
    "invalid_params": {
      "user_id": ["user_id is missing"]
    }
  }
}
```

## Endpoints

### Orders

#### List Orders

```
GET /orders
```

Retrieves all orders for the authenticated user. Supports date range filtering.

**Query Parameters:**
- `from` (optional): Start date for filtering (ISO 8601 format). Defaults to 30 days ago.
- `to` (optional): End date for filtering (ISO 8601 format). Defaults to tomorrow.

**Response:** `200 OK`

```json
{
  "data": [
    {
      "id": 1,
      "user_id": 42,
      "bill_id": 5,
      "ship_id": 6,
      "status": "pending",
      "locator": "abc123",
      "shipped_impression_kit_at": null,
      "received_impression_kit_at": null,
      "shipped_custom_night_guard_at": null,
      "created_at": "2024-01-15T10:30:00-05:00",
      "updated_at": "2024-01-15T10:30:00-05:00",
      "deleted": false
    }
  ]
}
```

#### Create Order

```
POST /orders
```

Creates a new order for the authenticated user.

**Request Body:**

```json
{
  "data": {
    "shipped_impression_kit_at": "2024-01-20T00:00:00"
  }
}
```

**Response:** `200 OK`

```json
{
  "data": {
    "id": 101,
    "user_id": 42,
    "status": "pending",
    "created_at": "2024-01-15T10:30:00-05:00"
  }
}
```

#### Get Order

```
GET /orders/:id
```

Retrieves a specific order by ID.

**Response:** `200 OK`

#### Update Order

```
PUT /orders/:id
```

Updates an existing order. Only specific fields can be updated.

**Updatable Fields:**
- `shipped_impression_kit_at`
- `received_impression_kit_at`
- `shipped_custom_night_guard_at`

**Request Body:**

```json
{
  "data": {
    "shipped_impression_kit_at": "2024-01-20T14:30:00"
  }
}
```

**Response:** `200 OK`

#### Delete Order

```
DELETE /orders/:id
```

Soft-deletes an order (marks as deleted without removing from database).

**Response:** `204 No Content`

### Order Items

#### List Order Items

```
GET /orders/:order_id/items
```

Retrieves all items in a specific order.

**Response:** `200 OK`

#### Add Item to Order

```
POST /orders/:order_id/items
```

Adds a new item to an order.

**Request Body:**

```json
{
  "data": {
    "product_id": 5,
    "quantity": 2,
    "price": 99.99
  }
}
```

**Response:** `201 Created`

#### Update Order Item

```
PUT /orders/:order_id/items/:id
```

Updates an item in an order.

**Updatable Fields:**
- `product_id`
- `quantity`
- `price`

**Response:** `200 OK`

#### Remove Item from Order

```
DELETE /orders/:order_id/items/:id
```

Removes an item from an order.

**Response:** `204 No Content`

### Products

#### List Products

```
GET /products
```

Retrieves all available products.

**Response:** `200 OK`

#### Create Product

```
POST /products
```

Creates a new product.

**Request Body:**

```json
{
  "data": {
    "name": "Night Guard",
    "description": "Custom night guard",
    "price": 199.99,
    "sku": "NG-001"
  }
}
```

**Response:** `201 Created`

#### Get Product

```
GET /products/:id
```

Retrieves a specific product.

**Response:** `200 OK`

#### Update Product

```
PUT /products/:id
```

Updates a product.

**Response:** `200 OK`

#### Delete Product

```
DELETE /products/:id
```

Soft-deletes a product.

**Response:** `204 No Content`

### Users

#### Register User

```
POST /users
```

Creates a new user account. This endpoint does not require authentication.

**Request Body:**

```json
{
  "data": {
    "email": "user@example.com",
    "name": "John Doe",
    "password": "secure_password"
  }
}
```

**Response:** `201 Created`

#### Get User

```
GET /users/:id
```

Retrieves user information.

**Response:** `200 OK`

#### Update User

```
PUT /users/:id
```

Updates user information.

**Response:** `200 OK`

#### Delete User

```
DELETE /users/:id
```

Soft-deletes a user account.

**Response:** `204 No Content`

### Addresses

#### List Addresses

```
GET /addresses
```

Retrieves all addresses for the authenticated user.

**Response:** `200 OK`

#### Create Address

```
POST /addresses
```

Creates a new address for the user.

**Request Body:**

```json
{
  "data": {
    "name": "Home",
    "address1": "123 Main St",
    "city": "Anytown",
    "state": "MA",
    "zip_code": "12345",
    "country": "USA"
  }
}
```

**Response:** `201 Created`

#### Update Address

```
PUT /addresses/:id
```

Updates an existing address.

**Response:** `200 OK`

#### Delete Address

```
DELETE /addresses/:id
```

Soft-deletes an address.

**Response:** `204 No Content`

### Plans

#### List Plans

```
GET /plans
```

Retrieves all available treatment plans.

**Response:** `200 OK`

#### Create Plan

```
POST /plans
```

Creates a new treatment plan.

**Request Body:**

```json
{
  "data": {
    "name": "Standard Care",
    "description": "Basic treatment plan",
    "price": 499.99
  }
}
```

**Response:** `201 Created`

#### Update Plan

```
PUT /plans/:id
```

Updates a plan.

**Response:** `200 OK`

#### Delete Plan

```
DELETE /plans/:id
```

Soft-deletes a plan.

**Response:** `204 No Content`

## Status Codes

- `200 OK` - Request successful
- `201 Created` - Resource created successfully
- `204 No Content` - Request successful, no content to return
- `400 Bad Request` - Invalid request parameters
- `401 Unauthorized` - Missing or invalid authentication
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation errors in request data
- `500 Internal Server Error` - Server error

## Validation

All endpoints validate input according to contract specifications:

### Order Validation
- `user_id` (required): Must be an integer
- `shipped_impression_kit_at` (optional): Must be a valid date/time

### OrderItem Validation
- `quantity` (required): Must be an integer

### Product Validation
- `name` (required): Must be a string
- `price` (optional): Must be a number

### User Validation
- `email` (required): Must be a valid email address
- `name` (required): Must be a string
- `password` (required): Must be at least 6 characters

## Rate Limiting

Currently, there are no rate limits on API endpoints. However, rate limiting may be implemented in future versions.

## Pagination

Pagination is not currently implemented. List endpoints return all matching records.

## Timestamps

All timestamps are returned in ISO 8601 format with timezone information:

```
2024-01-15T10:30:00-05:00
```

## Examples

### Create an Order and Add Items

```bash
# Create order
ORDER_ID=$(curl -s -X POST \
  -H "Authorization: Metaspace-Token api_key=1:abc, access_token=42:xyz" \
  -H "Content-Type: application/json" \
  -d '{"data":{"status":"pending"}}' \
  https://api.metatooth.com/orders | jq '.data.id')

# Add item to order
curl -X POST \
  -H "Authorization: Metaspace-Token api_key=1:abc, access_token=42:xyz" \
  -H "Content-Type: application/json" \
  -d '{"data":{"product_id":5,"quantity":1,"price":99.99}}' \
  https://api.metatooth.com/orders/$ORDER_ID/items
```

## Support

For API support, please contact support@metatooth.com or visit https://metatooth.com

## Changelog

### Version 1.0.0
- Initial API release
- Complete CRUD operations for orders, products, and users
- Order item management
- Address management
- Plan management
- Token-based authentication
