# API Overview

**FitTrack Backend API** - RESTful JSON API

## Base URL
```
/api/v1
```

All endpoints prefixed with `/api/v1`.

## Authentication
**Laravel Sanctum** - Token-based authentication

**Headers**:
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

## Response Format

### Success Response
```json
{
  "data": {
    // Resource data or collection
  },
  "meta": {
    // Pagination, counts, etc
  },
  "links": {
    // HATEOAS links
  }
}
```

### Error Response
```json
{
  "message": "Error message",
  "errors": {
    "field_name": [
      "Error detail"
    ]
  }
}
```

## HTTP Status Codes

### Success Codes
- **200 OK**: Successful GET, PUT, PATCH
- **201 Created**: Successful POST
- **204 No Content**: Successful DELETE

### Client Error Codes
- **400 Bad Request**: Malformed request
- **401 Unauthorized**: Authentication required or failed
- **403 Forbidden**: Authenticated but insufficient permissions
- **404 Not Found**: Resource doesn't exist
- **422 Unprocessable Entity**: Validation failed

### Server Error Codes
- **500 Internal Server Error**: Unexpected server error

## Endpoint Groups

### Authentication
- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/logout`
- `GET /auth/user`

### Profiles
- `POST /profiles/trainer`
- `GET /profiles/trainer/{slug}`
- `PATCH /profiles/trainer`
- `POST /profiles/trainee`
- `GET /profiles/trainee`
- `PATCH /profiles/trainee`

### Gyms
- `POST /gyms`
- `GET /gyms`
- `GET /gyms/{slug}`
- `PATCH /gyms/{id}`
- `DELETE /gyms/{id}`
- `POST /gyms/{id}/equipment`
- `DELETE /gyms/{id}/equipment/{equipmentId}`
- `GET /gyms/{id}/compatible-workouts`

### Equipment (Read-only)
- `GET /equipment`
- `GET /equipment/{id}`

### Exercises
- `POST /exercises`
- `GET /exercises`
- `GET /exercises/{id}`
- `PATCH /exercises/{id}`
- `DELETE /exercises/{id}`
- `POST /exercises/{id}/media`

### Workouts
- `POST /workouts`
- `GET /workouts`
- `GET /workouts/{id}`
- `PATCH /workouts/{id}`
- `DELETE /workouts/{id}`
- `POST /workouts/{id}/exercises`
- `GET /workouts/{id}/compatible-gyms`

### Training Plans
- `POST /training-plans`
- `GET /training-plans`
- `GET /training-plans/{id}`
- `PATCH /training-plans/{id}`
- `DELETE /training-plans/{id}`

### Workout Sessions
- `POST /sessions`
- `GET /sessions`
- `GET /sessions/{id}`
- `PATCH /sessions/{id}`
- `POST /sessions/{id}/exercises/{exerciseLogId}/sets`

### Progress & Analytics
- `GET /progress/dashboard`
- `GET /progress/snapshots`
- `GET /progress/personal-records`

### Commerce
- `POST /purchases/workouts`
- `POST /purchases/training-plans`
- `POST /subscriptions/gyms`
- `POST /contracts/trainers`

## Pagination

For list endpoints, use query parameters:
```
GET /api/v1/workouts?page=2&per_page=20
```

**Response includes**:
```json
{
  "data": [...],
  "meta": {
    "current_page": 2,
    "total": 150,
    "per_page": 20,
    "last_page": 8
  },
  "links": {
    "first": "/api/v1/workouts?page=1",
    "prev": "/api/v1/workouts?page=1",
    "next": "/api/v1/workouts?page=3",
    "last": "/api/v1/workouts?page=8"
  }
}
```

## Filtering & Searching

**Query Parameters**:
- `?search={term}` - Full-text search
- `?filter[field]={value}` - Filter by field
- `?sort={field}` - Sort by field
- `?sort=-{field}` - Sort descending

**Example**:
```
GET /api/v1/exercises?filter[difficulty]=beginner&filter[type]=system&sort=-created_at
```

## Includes (Eager Loading)

Load relationships:
```
GET /api/v1/workouts/{id}?include=exercises,creator
```

## Versioning

API versioned via URL prefix: `/api/v1`

Breaking changes will increment version: `/api/v2`

## Rate Limiting

**Unauthenticated**: 60 requests/minute
**Authenticated**: 300 requests/minute

Rate limit headers in response:
```
X-RateLimit-Limit: 300
X-RateLimit-Remaining: 295
X-RateLimit-Reset: 1640000000
```

## CORS

Configured for frontend domains in `config/cors.php`.

## API Documentation

Full API documentation available at:
- Postman Collection: `/docs/postman`
- OpenAPI/Swagger: `/docs/api` (future)

## Testing

All endpoints have corresponding feature tests in:
```
tests/Feature/Api/
├── Auth/
├── Profiles/
├── Gyms/
├── Exercises/
├── Workouts/
├── TrainingPlans/
├── Sessions/
└── Progress/
```

## Backend-Only Reminder

This is a **backend API only**. No views, blade templates, or frontend assets.

All responses are JSON. Frontend team consumes this API.
