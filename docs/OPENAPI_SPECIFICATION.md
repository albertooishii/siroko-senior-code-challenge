# OpenAPI Specification - Siroko Cart & Checkout API

## API Base
- **Base URL**: `http://localhost:8080`
- **Version**: `v1`
- **Content-Type**: `application/json`

## Endpoints

### 1. Create Cart
```yaml
POST /api/carts
```
**Description**: Crear un nuevo carrito de compras

**Response 201**:
```json
{
  "id": 1,
  "items": [],
  "total": "0.00",
  "itemsCount": 0,
  "createdAt": "2025-09-05T10:30:00Z"
}
```

### 2. Get Cart
```yaml
GET /api/carts/{id}
```
**Parameters**:
- `id` (integer, path): ID del carrito

**Response 200**:
```json
{
  "id": 1,
  "items": [
    {
      "productId": 123,
      "quantity": 2,
      "unitPrice": "25.99",
      "subtotal": "51.98"
    }
  ],
  "total": "51.98",
  "itemsCount": 1,
  "createdAt": "2025-09-05T10:30:00Z"
}
```

**Response 404**:
```json
{
  "error": "Cart not found"
}
```

### 3. Add Item to Cart
```yaml
POST /api/carts/{id}/items
```
**Parameters**:
- `id` (integer, path): ID del carrito

**Request Body**:
```json
{
  "productId": 123,
  "quantity": 2
}
```

**Response 200**:
```json
{
  "id": 1,
  "items": [
    {
      "productId": 123,
      "quantity": 2,
      "unitPrice": "25.99",
      "subtotal": "51.98"
    }
  ],
  "total": "51.98",
  "itemsCount": 1
}
```

### 4. Update Item Quantity
```yaml
PUT /api/carts/{id}/items/{productId}
```
**Parameters**:
- `id` (integer, path): ID del carrito
- `productId` (integer, path): ID del producto

**Request Body**:
```json
{
  "quantity": 3
}
```

**Response 200**: Same as cart response

### 5. Remove Item from Cart
```yaml
DELETE /api/carts/{id}/items/{productId}
```
**Parameters**:
- `id` (integer, path): ID del carrito
- `productId` (integer, path): ID del producto

**Response 200**: Same as cart response

### 6. Checkout Cart
```yaml
POST /api/carts/{id}/checkout
```
**Parameters**:
- `id` (integer, path): ID del carrito

**Response 201**:
```json
{
  "orderId": 456,
  "cartId": 1,
  "totalAmount": "51.98",
  "status": "confirmed",
  "items": [
    {
      "productId": 123,
      "quantity": 2,
      "unitPrice": "25.99",
      "subtotal": "51.98"
    }
  ],
  "createdAt": "2025-09-05T10:35:00Z"
}
```

## Error Responses

### 400 Bad Request
```json
{
  "error": "Invalid request data",
  "details": "Quantity must be positive"
}
```

### 404 Not Found
```json
{
  "error": "Resource not found",
  "details": "Cart with ID 999 not found"
}
```

### 500 Internal Server Error
```json
{
  "error": "Internal server error"
}
```

## Data Models

### CartDTO
```json
{
  "id": "integer",
  "items": "CartItemDTO[]",
  "total": "string (formatted money)",
  "itemsCount": "integer",
  "createdAt": "string (ISO 8601)"
}
```

### CartItemDTO
```json
{
  "productId": "integer",
  "quantity": "integer", 
  "unitPrice": "string (formatted money)",
  "subtotal": "string (formatted money)"
}
```

### OrderDTO
```json
{
  "orderId": "integer",
  "cartId": "integer",
  "totalAmount": "string (formatted money)",
  "status": "string",
  "items": "OrderItemDTO[]",
  "createdAt": "string (ISO 8601)"
}
```

## Testing

Puedes probar la API con:

```bash
# Crear carrito
curl -X POST http://localhost:8080/api/carts

# Añadir item
curl -X POST http://localhost:8080/api/carts/1/items \
  -H "Content-Type: application/json" \
  -d '{"productId": 123, "quantity": 2}'

# Ver carrito
curl http://localhost:8080/api/carts/1

# Checkout
curl -X POST http://localhost:8080/api/carts/1/checkout
```
