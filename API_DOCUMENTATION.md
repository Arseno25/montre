# Montre API Documentation

## Overview
This documentation provides information about the Montre API endpoints, their usage, and examples.

## Authentication
The API uses Laravel Sanctum for authentication. Most endpoints require authentication via Bearer token.

### Get Authentication Token
```http
POST /api/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "your_password"
}
```

Response:
```json
{
    "status": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com"
        },
        "token": "your_auth_token"
    }
}
```

### Register New User
```http
POST /api/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "user@example.com",
    "password": "your_password",
    "password_confirmation": "your_password"
}
```

## Categories

### List Categories
```http
GET /api/categories
Authorization: Bearer your_auth_token
```

Response:
```json
{
    "status": true,
    "data": [
        {
            "id": 1,
            "name": "Food & Beverage",
            "type": "expense",
            "transactions_count": 5,
            "budgets_count": 1
        }
    ]
}
```

### Create Category
```http
POST /api/categories
Authorization: Bearer your_auth_token
Content-Type: application/json

{
    "name": "Food & Beverage",
    "type": "expense"
}
```

### Get Category Details
```http
GET /api/categories/{id}
Authorization: Bearer your_auth_token
```

### Update Category
```http
PUT /api/categories/{id}
Authorization: Bearer your_auth_token
Content-Type: application/json

{
    "name": "Updated Name",
    "type": "expense"
}
```

### Delete Category
```http
DELETE /api/categories/{id}
Authorization: Bearer your_auth_token
```

## Transactions

### List Transactions
```http
GET /api/transactions
Authorization: Bearer your_auth_token
```

Query Parameters:
- `start_date`: Filter by start date (YYYY-MM-DD)
- `end_date`: Filter by end date (YYYY-MM-DD)
- `category_id`: Filter by category
- `type`: Filter by type (income/expense)

Response:
```json
{
    "status": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "user_id": 1,
                "category_id": 1,
                "type": "expense",
                "description": "Lunch",
                "amount": 15.50,
                "transaction_date": "2024-03-20T12:00:00Z",
                "category": {
                    "id": 1,
                    "name": "Food & Beverage"
                }
            }
        ],
        "per_page": 15,
        "total": 50
    }
}
```

### Create Transaction
```http
POST /api/transactions
Authorization: Bearer your_auth_token
Content-Type: application/json

{
    "category_id": 1,
    "type": "expense",
    "description": "Lunch",
    "amount": 15.50,
    "transaction_date": "2024-03-20"
}
```

### Get Transaction Details
```http
GET /api/transactions/{id}
Authorization: Bearer your_auth_token
```

### Update Transaction
```http
PUT /api/transactions/{id}
Authorization: Bearer your_auth_token
Content-Type: application/json

{
    "category_id": 1,
    "type": "expense",
    "description": "Updated description",
    "amount": 15.50,
    "transaction_date": "2024-03-20"
}
```

### Delete Transaction
```http
DELETE /api/transactions/{id}
Authorization: Bearer your_auth_token
```

### Get Transaction Summary
```http
GET /api/transactions/summary
Authorization: Bearer your_auth_token
```

Query Parameters:
- `start_date`: Required, start date (YYYY-MM-DD)
- `end_date`: Required, end date (YYYY-MM-DD)

## Budgets

### List Budgets
```http
GET /api/budgets
Authorization: Bearer your_auth_token
```

Query Parameters:
- `period`: Filter by period
- `category_id`: Filter by category
- `start_date`: Filter by start date (YYYY-MM-DD)
- `end_date`: Filter by end date (YYYY-MM-DD)

Response:
```json
{
    "status": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "user_id": 1,
                "category_id": 1,
                "period": "monthly",
                "start_date": "2024-03-01T00:00:00Z",
                "end_date": "2024-03-31T23:59:59Z",
                "amount": 500.00,
                "category": {
                    "id": 1,
                    "name": "Food & Beverage",
                    "type": "expense"
                }
            }
        ],
        "per_page": 10,
        "total": 5
    }
}
```

### Create Budget
```http
POST /api/budgets
Authorization: Bearer your_auth_token
Content-Type: application/json

{
    "category_id": 1,
    "period": "monthly",
    "start_date": "2024-03-01",
    "end_date": "2024-03-31",
    "amount": 500.00
}
```

Response:
```json
{
    "status": true,
    "message": "Budget created successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "category_id": 1,
        "period": "monthly",
        "start_date": "2024-03-01T00:00:00Z",
        "end_date": "2024-03-31T23:59:59Z",
        "amount": 500.00,
        "created_at": "2024-03-20T10:00:00Z",
        "updated_at": "2024-03-20T10:00:00Z",
        "category": {
            "id": 1,
            "name": "Food & Beverage",
            "type": "expense"
        }
    }
}
```

### Get Budget Details
```http
GET /api/budgets/{id}
Authorization: Bearer your_auth_token
```

Response:
```json
{
    "status": true,
    "data": {
        "budget": {
            "id": 1,
            "user_id": 1,
            "category_id": 1,
            "period": "monthly",
            "start_date": "2024-03-01T00:00:00Z",
            "end_date": "2024-03-31T23:59:59Z",
            "amount": 500.00,
            "category": {
                "id": 1,
                "name": "Food & Beverage",
                "type": "expense"
            }
        },
        "statistics": {
            "spent": 350.25,
            "remaining": 149.75,
            "progress_percentage": 70.05
        }
    }
}
```

### Update Budget
```http
PUT /api/budgets/{id}
Authorization: Bearer your_auth_token
Content-Type: application/json

{
    "category_id": 1,
    "period": "monthly",
    "start_date": "2024-03-01",
    "end_date": "2024-03-31",
    "amount": 600.00
}
```

Response:
```json
{
    "status": true,
    "message": "Budget updated successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "category_id": 1,
        "period": "monthly",
        "start_date": "2024-03-01T00:00:00Z",
        "end_date": "2024-03-31T23:59:59Z",
        "amount": 600.00,
        "category": {
            "id": 1,
            "name": "Food & Beverage",
            "type": "expense"
        }
    }
}
```

### Delete Budget
```http
DELETE /api/budgets/{id}
Authorization: Bearer your_auth_token
```

Response:
```json
{
    "status": true,
    "message": "Budget deleted successfully"
}
```

## Reminders

### List Reminders
```
