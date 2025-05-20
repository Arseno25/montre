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
- `active`: Filter active budgets (true/false)
- `category_id`: Filter by category

Response:
```json
{
    "status": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "category_id": 1,
                "amount": 500.00,
                "start_date": "2024-03-01",
                "end_date": "2024-03-31",
                "spent": 350.25,
                "remaining": 149.75,
                "progress": 70.05,
                "category": {
                    "id": 1,
                    "name": "Food & Beverage"
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
    "amount": 500.00,
    "start_date": "2024-03-01",
    "end_date": "2024-03-31",
    "description": "Monthly food budget"
}
```

### Get Budget Details
```http
GET /api/budgets/{id}
Authorization: Bearer your_auth_token
```

### Update Budget
```http
PUT /api/budgets/{id}
Authorization: Bearer your_auth_token
Content-Type: application/json

{
    "category_id": 1,
    "amount": 600.00,
    "start_date": "2024-03-01",
    "end_date": "2024-03-31",
    "description": "Updated monthly food budget"
}
```

### Delete Budget
```http
DELETE /api/budgets/{id}
Authorization: Bearer your_auth_token
```

## Reminders

### List Reminders
```http
GET /api/reminders
Authorization: Bearer your_auth_token
```

Query Parameters:
- `is_completed`: Filter by completion status (true/false)
- `start_date`: Filter by start date (YYYY-MM-DD)
- `end_date`: Filter by end date (YYYY-MM-DD)
- `upcoming`: Show only upcoming reminders (true/false)
- `category_id`: Filter by category

Response:
```json
{
    "status": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "title": "Pay Rent",
                "description": "Monthly rent payment",
                "due_date": "2024-04-01T00:00:00Z",
                "is_completed": false,
                "category": {
                    "id": 1,
                    "name": "Housing"
                },
                "transaction": null
            }
        ],
        "per_page": 15,
        "total": 10
    }
}
```

### Create Reminder
```http
POST /api/reminders
Authorization: Bearer your_auth_token
Content-Type: application/json

{
    "title": "Pay Rent",
    "description": "Monthly rent payment",
    "due_date": "2024-04-01",
    "category_id": 1
}
```

### Get Reminder Details
```http
GET /api/reminders/{id}
Authorization: Bearer your_auth_token
```

### Update Reminder
```http
PUT /api/reminders/{id}
Authorization: Bearer your_auth_token
Content-Type: application/json

{
    "title": "Pay Rent",
    "description": "Updated description",
    "due_date": "2024-04-01",
    "category_id": 1,
    "is_completed": false
}
```

### Delete Reminder
```http
DELETE /api/reminders/{id}
Authorization: Bearer your_auth_token
```

### Mark Reminder as Complete
```http
PATCH /api/reminders/{id}/complete
Authorization: Bearer your_auth_token
```

### Get Upcoming Reminders
```http
GET /api/reminders/upcoming
Authorization: Bearer your_auth_token
```

## Response Format
All API responses follow this general format:

Success Response:
```json
{
    "status": true,
    "message": "Optional success message",
    "data": {
        // Response data
    }
}
```

Error Response:
```json
{
    "status": false,
    "message": "Error message",
    "errors": {
        // Validation errors if any
    }
}
```

## Error Codes
- 200: Success
- 201: Created
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Validation Error
- 500: Server Error

## Authentication Headers
For protected routes, include the authentication token in the request header:
```http
Authorization: Bearer your_auth_token
``` 
