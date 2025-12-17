# API Testing Guide

## Quick Start - MVP Flow

This guide shows how to test the basic MVP flow: register, login, view workouts, and start tracking a workout session.

### Base URL
```
http://localhost:8000/api/v1
```

### Test Credentials

**Trainee User:**
- Email: `trainee@example.com`
- Password: `password`

**Trainer User:**
- Email: `trainer@example.com`
- Password: `password`

---

## 1. Authentication

### Register New User
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "newuser@example.com",
    "password": "password123"
  }'
```

**Response:**
```json
{
  "data": {
    "user": {
      "id": "uuid",
      "email": "newuser@example.com"
    },
    "token": "your-auth-token"
  }
}
```

### Login
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "trainee@example.com",
    "password": "password"
  }'
```

**Response:**
```json
{
  "data": {
    "user": {
      "id": "uuid",
      "email": "trainee@example.com"
    },
    "token": "your-auth-token"
  }
}
```

**Important:** Save the token for authenticated requests!

### Get Current User
```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Logout
```bash
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 2. Browse Workouts (Public)

### List All Published Workouts
```bash
curl -X GET http://localhost:8000/api/v1/workouts
```

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Full Body Strength",
      "description": "A comprehensive full-body workout...",
      "difficulty": "intermediate",
      "estimated_duration_minutes": 60,
      "total_exercises": 3,
      "total_sets": 11,
      "pricing_type": "free",
      "status": "published",
      "creator": {
        "id": "uuid",
        "display_name": "Coach John"
      },
      "created_at": "2025-12-17T22:04:19.000000Z"
    }
  ]
}
```

### View Specific Workout
```bash
curl -X GET http://localhost:8000/api/v1/workouts/{workout_id}
```

**Response includes exercises:**
```json
{
  "data": {
    "id": "uuid",
    "name": "Full Body Strength",
    "exercises": [
      {
        "id": "uuid",
        "sort_order": 1,
        "sets": 4,
        "target_reps": 8,
        "rest_seconds": 120,
        "exercise": {
          "id": "uuid",
          "name": "Barbell Bench Press",
          "description": "...",
          "primary_muscle_groups": ["Chest", "Triceps"],
          "difficulty": "intermediate",
          "performance_type": "repetition"
        }
      }
    ]
  }
}
```

---

## 3. Workout Sessions (Authenticated)

**Note:** You must have a trainee profile to track workouts. The seeded user `trainee@example.com` already has one.

### Start a Workout Session
```bash
curl -X POST http://localhost:8000/api/v1/sessions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "workout_id": "WORKOUT_UUID"
  }'
```

**Response:**
```json
{
  "data": {
    "id": "session-uuid",
    "workout": {
      "id": "workout-uuid",
      "name": "Full Body Strength"
    },
    "status": "started",
    "started_at": "2025-12-17T22:10:00.000000Z",
    "completed_at": null,
    "total_duration_minutes": null,
    "total_volume_kg": null,
    "created_at": "2025-12-17T22:10:00.000000Z"
  },
  "message": "Workout session started successfully"
}
```

### View Session Details
```bash
curl -X GET http://localhost:8000/api/v1/sessions/{session_id} \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### List My Sessions
```bash
curl -X GET http://localhost:8000/api/v1/sessions \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Complete a Session
```bash
curl -X POST http://localhost:8000/api/v1/sessions/{session_id}/complete \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "data": {
    "id": "session-uuid",
    "workout": {
      "id": "workout-uuid",
      "name": "Full Body Strength"
    },
    "status": "completed",
    "started_at": "2025-12-17T22:10:00.000000Z",
    "completed_at": "2025-12-17T22:45:00.000000Z",
    "total_duration_minutes": 35,
    "total_volume_kg": 0,
    "created_at": "2025-12-17T22:10:00.000000Z"
  },
  "message": "Workout session completed successfully"
}
```

---

## 4. Exercise and Set Logging (Authenticated)

**Note:** Exercise and set logs can only be created while a session is in progress. Once a session is completed, logs become immutable.

### Create Exercise Log
```bash
curl -X POST http://localhost:8000/api/v1/sessions/{session_id}/exercises \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "exercise_id": "EXERCISE_UUID",
    "sort_order": 1,
    "notes": "Feeling strong today"
  }'
```

**Response:**
```json
{
  "data": {
    "id": "exercise-log-uuid",
    "exercise": {
      "id": "exercise-uuid",
      "name": "Barbell Bench Press"
    },
    "sort_order": 1,
    "status": "pending",
    "started_at": null,
    "completed_at": null,
    "notes": "Feeling strong today",
    "sets": []
  },
  "message": "Exercise log created successfully"
}
```

### Start an Exercise
```bash
curl -X POST http://localhost:8000/api/v1/sessions/{session_id}/exercises/{exercise_log_id}/start \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Log a Set
```bash
curl -X POST http://localhost:8000/api/v1/sessions/{session_id}/exercises/{exercise_log_id}/sets \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "set_number": 1,
    "target_reps": 8,
    "actual_reps": 8,
    "weight_kg": 80,
    "rpe": 7,
    "is_warmup": false,
    "is_failure": false,
    "notes": "Good form"
  }'
```

**For duration-based exercises:**
```json
{
  "set_number": 1,
  "target_duration_seconds": 60,
  "actual_duration_seconds": 65,
  "rpe": 8
}
```

**For distance-based exercises:**
```json
{
  "set_number": 1,
  "target_distance_meters": 400,
  "actual_distance_meters": 400,
  "rpe": 6
}
```

**Response:**
```json
{
  "data": {
    "id": "set-log-uuid",
    "set_number": 1,
    "target_reps": 8,
    "actual_reps": 8,
    "target_duration_seconds": null,
    "actual_duration_seconds": null,
    "weight_kg": 80.0,
    "rpe": 7,
    "is_warmup": false,
    "is_failure": false,
    "notes": "Good form"
  },
  "message": "Set logged successfully"
}
```

### Update a Set
```bash
curl -X PATCH http://localhost:8000/api/v1/sessions/{session_id}/exercises/{exercise_log_id}/sets/{set_log_id} \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "actual_reps": 10,
    "rpe": 8
  }'
```

### Delete a Set
```bash
curl -X DELETE http://localhost:8000/api/v1/sessions/{session_id}/exercises/{exercise_log_id}/sets/{set_log_id} \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Complete an Exercise
```bash
curl -X POST http://localhost:8000/api/v1/sessions/{session_id}/exercises/{exercise_log_id}/complete \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "notes": "Completed all sets with good form"
  }'
```

### Skip an Exercise
```bash
curl -X POST http://localhost:8000/api/v1/sessions/{session_id}/exercises/{exercise_log_id}/skip \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "notes": "Shoulder pain, skipping today"
  }'
```

---

## Complete Flow Example

### Step 1: Login
```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"trainee@example.com","password":"password"}' \
  | jq -r '.data.token')

echo "Token: $TOKEN"
```

### Step 2: Get Available Workouts
```bash
WORKOUT_ID=$(curl -s -X GET http://localhost:8000/api/v1/workouts \
  | jq -r '.data[0].id')

echo "Workout ID: $WORKOUT_ID"
```

### Step 3: Start a Session
```bash
SESSION_ID=$(curl -s -X POST http://localhost:8000/api/v1/sessions \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"workout_id\":\"$WORKOUT_ID\"}" \
  | jq -r '.data.id')

echo "Session ID: $SESSION_ID"
```

### Step 4: View Session
```bash
curl -X GET http://localhost:8000/api/v1/sessions/$SESSION_ID \
  -H "Authorization: Bearer $TOKEN" | jq
```

### Step 5: Log Exercises and Sets

First, get the workout details to know which exercises to log:
```bash
WORKOUT_DETAILS=$(curl -s -X GET http://localhost:8000/api/v1/workouts/$WORKOUT_ID)
FIRST_EXERCISE_ID=$(echo $WORKOUT_DETAILS | jq -r '.data.exercises[0].exercise.id')

echo "First Exercise ID: $FIRST_EXERCISE_ID"
```

Create an exercise log:
```bash
EXERCISE_LOG_ID=$(curl -s -X POST http://localhost:8000/api/v1/sessions/$SESSION_ID/exercises \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"exercise_id\":\"$FIRST_EXERCISE_ID\",\"sort_order\":1}" \
  | jq -r '.data.id')

echo "Exercise Log ID: $EXERCISE_LOG_ID"
```

Start the exercise:
```bash
curl -X POST http://localhost:8000/api/v1/sessions/$SESSION_ID/exercises/$EXERCISE_LOG_ID/start \
  -H "Authorization: Bearer $TOKEN" | jq
```

Log sets:
```bash
# Set 1
curl -X POST http://localhost:8000/api/v1/sessions/$SESSION_ID/exercises/$EXERCISE_LOG_ID/sets \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"set_number":1,"target_reps":8,"actual_reps":8,"weight_kg":80,"rpe":7}' | jq

# Set 2
curl -X POST http://localhost:8000/api/v1/sessions/$SESSION_ID/exercises/$EXERCISE_LOG_ID/sets \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"set_number":2,"target_reps":8,"actual_reps":8,"weight_kg":80,"rpe":8}' | jq

# Set 3
curl -X POST http://localhost:8000/api/v1/sessions/$SESSION_ID/exercises/$EXERCISE_LOG_ID/sets \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"set_number":3,"target_reps":8,"actual_reps":7,"weight_kg":80,"rpe":9,"notes":"Tough last rep"}' | jq
```

Complete the exercise:
```bash
curl -X POST http://localhost:8000/api/v1/sessions/$SESSION_ID/exercises/$EXERCISE_LOG_ID/complete \
  -H "Authorization: Bearer $TOKEN" | jq
```

### Step 6: Complete Session
```bash
curl -X POST http://localhost:8000/api/v1/sessions/$SESSION_ID/complete \
  -H "Authorization: Bearer $TOKEN" | jq
```

**Note:** Once a session is completed, exercise and set logs become immutable and cannot be updated or deleted.

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "message": "You must have a trainee profile to start a workout session"
}
```

### 404 Not Found
```json
{
  "message": "No query results for model [App\\Domain\\Training\\Workout] uuid"
}
```

### 422 Validation Error
```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": [
      "The email has already been taken."
    ]
  }
}
```

---

## Next Steps

### Completed Features ✅
1. ✅ **Authentication**: Register, login, logout
2. ✅ **Workout Browsing**: List and view published workouts
3. ✅ **Session Tracking**: Start, view, and complete workout sessions
4. ✅ **Exercise Logging**: Log exercises within sessions with start/complete/skip
5. ✅ **Set Logging**: Track individual sets with reps, weight, RPE, and notes

### To add more features:
1. **Progress Tracking**: View workout history, volume trends, and progress over time
2. **Personal Records**: Automatically track and display PRs for exercises
3. **Profile Management**: Update trainee profile (weight, goals, measurements)
4. **Workout Creation**: Allow trainers to create and publish custom workouts
5. **Training Plans**: Multi-week structured programs
6. **Gym Management**: Gym owners can manage equipment and trainers

### Current Database:
- 60 equipment items (preset catalog)
- 56 system exercises
- 2 test users (trainee + trainer)
- 2 published workouts ("Full Body Strength" and "Push Day")
