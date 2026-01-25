# 🧪 API Testing Guide

Quick test examples for all endpoints.

## Base URL
```
LOCAL: http://localhost/buildconnect-php
PRODUCTION: https://your-railway-url.up.railway.app
```

## 1. Authentication

### Register Client
```bash
curl -X POST http://localhost/buildconnect-php/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "client@test.com",
    "password": "password123",
    "full_name": "Test Client",
    "phone": "+254700123456",
    "role": "client"
  }'
```

### Register Contractor
```bash
curl -X POST http://localhost/buildconnect-php/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "contractor@test.com",
    "password": "password123",
    "full_name": "John Builder",
    "phone": "+254700654321",
    "role": "contractor",
    "business_name": "BuildPro Ltd",
    "category": "Plumbing",
    "location": "Nairobi, Kenya",
    "years_of_experience": 5,
    "bio": "Professional plumber with 5 years experience"
  }'
```

### Login
```bash
curl -X POST http://localhost/buildconnect-php/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@buildconnect.com",
    "password": "admin123"
  }'
```

**Save the token from response!**

## 2. Categories

### List All Categories
```bash
curl http://localhost/buildconnect-php/api/categories/list
```

## 3. Contractors

### List All Contractors
```bash
curl http://localhost/buildconnect-php/api/contractors/list
```

### Search Contractors
```bash
curl "http://localhost/buildconnect-php/api/contractors/list?category=Plumbing&search=Build"
```

### Get Contractor Profile
```bash
curl http://localhost/buildconnect-php/api/contractors/profile?id=1
```

### Upload Documents (Contractor Only)
```bash
curl -X POST http://localhost/buildconnect-php/api/contractors/upload \
  -H "Authorization: Bearer YOUR_CONTRACTOR_TOKEN" \
  -F "kra_pin=@/path/to/kra.pdf" \
  -F "business_permit=@/path/to/permit.pdf"
```

## 4. Admin

### Get Dashboard (Admin Only)
```bash
curl http://localhost/buildconnect-php/api/admin/dashboard \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### Approve Contractor (Admin Only)
```bash
curl -X POST http://localhost/buildconnect-php/api/admin/verify \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "contractor_id": 1,
    "action": "approve"
  }'
```

### Reject Contractor (Admin Only)
```bash
curl -X POST http://localhost/buildconnect-php/api/admin/verify \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "contractor_id": 1,
    "action": "reject",
    "reason": "Missing required documents"
  }'
```

## 5. Service Requests

### Create Request (Client Only)
```bash
curl -X POST http://localhost/buildconnect-php/api/requests/create \
  -H "Authorization: Bearer YOUR_CLIENT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "contractor_id": 1,
    "category": "Plumbing",
    "title": "Fix kitchen sink leak",
    "description": "Need urgent repair of leaking kitchen sink",
    "location": "Nairobi, Westlands"
  }'
```

### List Requests (Auth Required)
```bash
curl http://localhost/buildconnect-php/api/requests/list \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Accept Request (Contractor Only)
```bash
curl -X POST http://localhost/buildconnect-php/api/requests/respond \
  -H "Authorization: Bearer YOUR_CONTRACTOR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "request_id": 1,
    "action": "accept"
  }'
```

### Reject Request (Contractor Only)
```bash
curl -X POST http://localhost/buildconnect-php/api/requests/respond \
  -H "Authorization: Bearer YOUR_CONTRACTOR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "request_id": 1,
    "action": "reject"
  }'
```

## 6. Notifications

### Get Notifications
```bash
curl http://localhost/buildconnect-php/api/notifications/get \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Mark as Read
```bash
curl -X POST http://localhost/buildconnect-php/api/notifications/get \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "notification_id": 1
  }'
```

### Mark All as Read
```bash
curl -X POST http://localhost/buildconnect-php/api/notifications/get \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}'
```

## 🧪 Postman Collection

Import this JSON into Postman:

```json
{
  "info": {
    "name": "BuildConnect API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Auth",
      "item": [
        {
          "name": "Register",
          "request": {
            "method": "POST",
            "url": "{{base_url}}/api/auth/register",
            "body": {
              "mode": "raw",
              "raw": "{\"email\":\"test@test.com\",\"password\":\"password123\",\"full_name\":\"Test User\",\"phone\":\"+254700000000\",\"role\":\"client\"}"
            }
          }
        },
        {
          "name": "Login",
          "request": {
            "method": "POST",
            "url": "{{base_url}}/api/auth/login",
            "body": {
              "mode": "raw",
              "raw": "{\"email\":\"admin@buildconnect.com\",\"password\":\"admin123\"}"
            }
          }
        }
      ]
    }
  ],
  "variable": [
    {
      "key": "base_url",
      "value": "http://localhost/buildconnect-php"
    },
    {
      "key": "token",
      "value": ""
    }
  ]
}
```

## Expected Responses

### Successful Response
```json
{
  "message": "Success message",
  "data": {...}
}
```

### Error Response
```json
{
  "error": "Error message"
}
```

### Authentication Response
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "role": "client"
  }
}
```

## Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `405` - Method Not Allowed
- `500` - Server Error

---

**Tip:** Replace `http://localhost/buildconnect-php` with your actual API URL!
