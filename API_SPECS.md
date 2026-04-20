# KerjaIn API Specification v1

> **Base URL**: `http://localhost:8000/api/v1`  
> **Auth**: Bearer Token (Laravel Sanctum)  
> **Content-Type**: `application/json` (kecuali upload file: `multipart/form-data`)

---

## Response Format

### Success Response
```json
{
    "success": true,
    "message": "Description of what happened",
    "data": { ... }
}
```

### Paginated Response
```json
{
    "success": true,
    "message": "Data retrieved successfully",
    "data": [ ... ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 100,
        "last_page": 7
    }
}
```

### Error Response
```json
{
    "success": false,
    "message": "What went wrong",
    "error_code": "ERROR_CODE",
    "errors": { ... }
}
```

### Error Codes
| Code | HTTP | Description |
|------|------|-------------|
| `VALIDATION_ERROR` | 422 | Input validation failed |
| `UNAUTHENTICATED` | 401 | Missing or invalid token |
| `FORBIDDEN` | 403 | Insufficient permissions |
| `PROFILE_INCOMPLETE` | 403 | Profile not completed yet |
| `NOT_FOUND` | 404 | Resource not found |
| `RATE_LIMITED` | 429 | Too many requests |
| `COOLDOWN_ACTIVE` | 429 | Assessment cooldown (5 min) |
| `INTERNAL_ERROR` | 500 | Server error |

### Rate Limits
| Group | Limit | Applies To |
|-------|-------|------------|
| `api` | 60/min | All endpoints |
| `auth` | 10/min | Login, register, password reset |
| `ai` | 5/min | AI-powered endpoints |

---

# 1. AUTHENTICATION

## 1.1 Register

**`POST /register`** — Register new student or professional account

**Rate Limit**: `auth` (10/min)

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "student"
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name` | string | yes | min:2, max:100 |
| `email` | string | yes | valid email, unique |
| `password` | string | yes | min:8, confirmed |
| `password_confirmation` | string | yes | must match password |
| `role` | string | yes | in: `student`, `professional` |

**Response: `201 Created`**
```json
{
    "success": true,
    "message": "Registration successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "student",
            "avatar": null,
            "is_profile_completed": false,
            "created_at": "2026-04-20T00:00:00Z"
        },
        "token": "1|abc123...",
        "is_profile_completed": false
    }
}
```

**Error Cases:**
- `422` — Validation failed (email taken, password too short, invalid role)
- `429` — Rate limited
- `500` — Server error

---

## 1.2 Login

**`POST /login`** — Authenticate user and get token

**Rate Limit**: `auth` (10/min)

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `email` | string | yes | valid email |
| `password` | string | yes | min:1 |

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "student",
            "avatar": "https://...",
            "is_profile_completed": true
        },
        "token": "2|xyz456...",
        "is_profile_completed": true
    }
}
```

**Error Cases:**
- `401` — Invalid credentials
- `422` — Validation failed
- `429` — Rate limited

---

## 1.3 Logout

**`POST /logout`** — Revoke current token

**Auth**: Required

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

---

## 1.4 Get Current User

**`GET /me`** — Get authenticated user info with profile

**Auth**: Required

**Response: `200 OK`**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "student",
            "avatar": "https://...",
            "phone_number": "08123456789",
            "industry": "Technology",
            "linkedin_url": "https://linkedin.com/in/john",
            "is_profile_completed": true,
            "provider": null,
            "created_at": "2026-04-01T00:00:00Z",
            "student_profile": {
                "school_name": "SMK Negeri 1 Jakarta",
                "bio": "Passionate web developer",
                "instagram_url": "https://instagram.com/john",
                "youtube_url": null,
                "tiktok_url": null
            },
            "professional_profile": null
        }
    }
}
```

---

## 1.5 Forgot Password

**`POST /forgot-password`** — Request password reset link

**Rate Limit**: `auth` (10/min)

**Request Body:**
```json
{
    "email": "john@example.com"
}
```

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Password reset link sent to your email"
}
```

**Error Cases:**
- `422` — Email not found / validation failed

---

## 1.6 Reset Password

**`POST /reset-password`** — Reset password with token

**Rate Limit**: `auth` (10/min)

**Request Body:**
```json
{
    "token": "reset_token_here",
    "email": "john@example.com",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Password reset successfully"
}
```

---

## 1.7 Google OAuth

**`GET /auth/google`** — Redirect to Google for authentication

**Response**: `302 Redirect` → Google consent screen

---

## 1.8 Google OAuth Callback

**`GET /auth/google/callback`** — Handle Google callback

**Response**: `302 Redirect` → `{FRONTEND_URL}/auth/callback?token={token}&is_profile_completed={bool}`

---

# 2. PROFILE

## 2.1 Complete Profile

**`POST /complete-profile`** — Complete profile after registration (mandatory)

**Auth**: Required  
**Note**: This is the only endpoint accessible when `is_profile_completed = false`

### Request Body (Student):
```json
{
    "name": "John Doe",
    "phone_number": "08123456789",
    "industry": "Technology",
    "linkedin_url": "https://linkedin.com/in/john",
    "school_name": "SMK Negeri 1 Jakarta",
    "bio": "Passionate about web development and backend engineering",
    "instagram_url": "https://instagram.com/john",
    "youtube_url": null,
    "tiktok_url": null
}
```

### Request Body (Professional):
```json
{
    "name": "Jane Smith",
    "phone_number": "08567891234",
    "industry": "Technology",
    "linkedin_url": "https://linkedin.com/in/jane",
    "company_name": "PT Tech Indonesia",
    "social_media_links": {
        "twitter": "https://twitter.com/jane",
        "website": "https://techindonesia.com"
    }
}
```

### Validation Rules

**Shared fields:**
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name` | string | yes | min:2, max:100 |
| `phone_number` | string | yes | min:10, max:15 |
| `industry` | string | yes | min:2, max:100 |
| `linkedin_url` | string | no | valid URL, nullable |

**Student-specific:**
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `school_name` | string | yes | min:2, max:200 |
| `bio` | string | yes | min:10, max:1000 |
| `instagram_url` | string | no | valid URL, nullable |
| `youtube_url` | string | no | valid URL, nullable |
| `tiktok_url` | string | no | valid URL, nullable |

**Professional-specific:**
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `company_name` | string | yes | min:2, max:200 |
| `social_media_links` | object | no | JSON object, nullable |

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Profile completed successfully",
    "data": {
        "user": { ... },
        "is_profile_completed": true
    }
}
```

**Error Cases:**
- `422` — Validation failed
- `400` — Profile already completed

---

## 2.2 Get Profile

**`GET /profile`** — Get own profile

**Auth**: Required

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Profile retrieved successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "student",
            "avatar": "https://...",
            "phone_number": "08123456789",
            "industry": "Technology",
            "linkedin_url": "https://linkedin.com/in/john",
            "is_profile_completed": true,
            "provider": "google"
        },
        "profile": {
            "school_name": "SMK Negeri 1 Jakarta",
            "bio": "Passionate about web development",
            "instagram_url": "https://instagram.com/john",
            "youtube_url": null,
            "tiktok_url": null
        }
    }
}
```

---

## 2.3 Update Profile

**`PUT /profile`** — Update own profile

**Auth**: Required

**Request Body**: Same fields as Complete Profile (all optional for update)

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Profile updated successfully",
    "data": {
        "user": { ... },
        "profile": { ... }
    }
}
```

---

## 2.4 Upload Avatar

**`POST /profile/avatar`** — Upload profile photo

**Auth**: Required  
**Content-Type**: `multipart/form-data`

**Request Body:**
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `avatar` | file | yes | image (jpeg, png, webp), max:2MB |

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Avatar uploaded successfully",
    "data": {
        "avatar_url": "https://localhost:8000/storage/avatars/1_avatar.webp"
    }
}
```

---

# 3. STUDENT — INTEREST ASSESSMENT

## 3.1 Start Interest Assessment

**`POST /student/interest/start`** — Begin AI-driven career interest assessment

**Auth**: Required (student)  
**Rate Limit**: `ai` (5/min)

**Request Body:** _(none required)_

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Interest assessment started",
    "data": {
        "session_id": 1,
        "question": "Halo! Saya akan membantu Anda menemukan karir yang cocok. Pertama, ceritakan tentang jurusan atau bidang yang sedang Anda pelajari di sekolah.",
        "question_number": 1,
        "total_questions": 10
    }
}
```

---

## 3.2 Submit Interest Answer

**`POST /student/interest/answer`** — Submit answer to current question

**Auth**: Required (student)  
**Rate Limit**: `ai` (5/min)

**Request Body:**
```json
{
    "session_id": 1,
    "answer": "Saya jurusan RPL dan sangat tertarik dengan backend development"
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `session_id` | integer | yes | exists in interest_sessions |
| `answer` | string | yes | min:5, max:1000 |

**Response (Next Question): `200 OK`**
```json
{
    "success": true,
    "message": "Answer submitted",
    "data": {
        "is_complete": false,
        "question": "Apa yang paling Anda sukai dari backend development? Misalnya database, API, atau server management?",
        "question_number": 2,
        "total_questions": 10
    }
}
```

**Response (Assessment Complete): `200 OK`**
```json
{
    "success": true,
    "message": "Interest assessment completed",
    "data": {
        "is_complete": true,
        "result": {
            "interest_field": "Technology",
            "interest_subfield": "Backend Development",
            "recommended_role": "Backend Developer",
            "level": "Beginner"
        }
    }
}
```

**Error Cases:**
- `404` — Session not found
- `400` — Session already completed
- `429` — Rate limited / AI rate limit

---

## 3.3 Get Career Recommendations

**`GET /student/career-recommendations`** — Get recommendations based on assessment

**Auth**: Required (student)

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Career recommendations retrieved",
    "data": {
        "recommendations": [
            {
                "id": 1,
                "career_role": {
                    "id": 1,
                    "name": "Backend Developer",
                    "description": "Builds server-side logic, databases, and APIs"
                },
                "match_score": 85,
                "has_roadmap": true
            },
            {
                "id": 2,
                "career_role": {
                    "id": 2,
                    "name": "Fullstack Developer",
                    "description": "Combines frontend and backend skills"
                },
                "match_score": 72,
                "has_roadmap": false
            }
        ]
    }
}
```

**Error Cases:**
- `400` — Interest assessment not completed yet

---

# 4. STUDENT — ROADMAP

## 4.1 Generate Roadmap

**`POST /student/roadmap/generate`** — Generate learning roadmap from career recommendation

**Auth**: Required (student)  
**Rate Limit**: `ai` (5/min)

**Request Body:**
```json
{
    "career_recommendation_id": 1
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `career_recommendation_id` | integer | yes | exists in career_recommendations |

**Response: `201 Created`**
```json
{
    "success": true,
    "message": "Roadmap generated successfully",
    "data": {
        "user_roadmap": {
            "id": 1,
            "is_active": true,
            "roadmap": {
                "id": 1,
                "career_role": "Backend Developer",
                "nodes": [
                    {
                        "id": 1,
                        "skill_name": "JavaScript",
                        "description": "Ability to write clean, modern JavaScript code including ES6+ features, async/await, and DOM manipulation",
                        "order_index": 1,
                        "is_completed": false,
                        "is_locked": false,
                        "can_take_assessment": true,
                        "last_assessment_at": null,
                        "best_score": null,
                        "resources": [
                            {
                                "title": "JavaScript Full Course - freeCodeCamp",
                                "url": "https://www.youtube.com/watch?v=...",
                                "type": "video"
                            }
                        ]
                    },
                    {
                        "id": 2,
                        "skill_name": "Node.js",
                        "description": "Build server-side applications using Node.js runtime",
                        "order_index": 2,
                        "is_completed": false,
                        "is_locked": true,
                        "can_take_assessment": false,
                        "resources": [...]
                    }
                ]
            }
        }
    }
}
```

**Error Cases:**
- `404` — Career recommendation not found
- `400` — Already has active roadmap (must complete or reset first)
- `429` — AI rate limited

---

## 4.2 Get Active Roadmap

**`GET /student/roadmap`** — Get user's active roadmap with progress

**Auth**: Required (student)

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Roadmap retrieved successfully",
    "data": {
        "roadmap": {
            "id": 1,
            "career_role": "Backend Developer",
            "nodes": [
                {
                    "id": 1,
                    "skill_name": "JavaScript",
                    "description": "Ability to write clean, modern JavaScript code",
                    "order_index": 1,
                    "is_completed": true,
                    "is_locked": false,
                    "can_take_assessment": true,
                    "last_assessment_at": "2026-04-15T10:00:00Z",
                    "best_score": 92,
                    "attempts_count": 2,
                    "resources": [...]
                },
                {
                    "id": 2,
                    "skill_name": "Node.js",
                    "description": "Build server-side applications",
                    "order_index": 2,
                    "is_completed": false,
                    "is_locked": false,
                    "can_take_assessment": true,
                    "last_assessment_at": null,
                    "best_score": null,
                    "attempts_count": 0,
                    "resources": [...]
                },
                {
                    "id": 3,
                    "skill_name": "REST API",
                    "description": "Design and build RESTful APIs",
                    "order_index": 3,
                    "is_completed": false,
                    "is_locked": true,
                    "can_take_assessment": false,
                    "last_assessment_at": null,
                    "best_score": null,
                    "attempts_count": 0,
                    "resources": [...]
                }
            ]
        },
        "progress": {
            "total_nodes": 5,
            "completed_nodes": 1,
            "percentage": 20.0
        }
    }
}
```

**Error Cases:**
- `404` — No active roadmap found

---

# 5. STUDENT — SELF ASSESSMENT

## 5.1 Start Assessment

**`POST /student/assessment/start`** — Start self assessment for a specific roadmap node

**Auth**: Required (student)  
**Rate Limit**: `ai` (5/min)

**Request Body:**
```json
{
    "roadmap_node_id": 2
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `roadmap_node_id` | integer | yes | must belong to user's active roadmap |

**Business Rules:**
1. Node must be **unlocked** (previous node completed, or first node)
2. If last failed attempt was < 5 minutes ago → reject with `COOLDOWN_ACTIVE`
3. AI generates 5-10 multiple choice questions about the skill

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Assessment started",
    "data": {
        "attempt_id": 5,
        "skill_name": "Node.js",
        "questions": [
            {
                "id": 1,
                "question": "What is the event loop in Node.js?",
                "options": {
                    "A": "A loop that handles DOM events",
                    "B": "A mechanism that handles async operations using callbacks",
                    "C": "A method for iterating over arrays",
                    "D": "A database connection pool"
                }
            },
            {
                "id": 2,
                "question": "Which module is used to create an HTTP server in Node.js?",
                "options": {
                    "A": "fs",
                    "B": "path",
                    "C": "http",
                    "D": "url"
                }
            }
        ],
        "total_questions": 5,
        "pass_threshold": 85
    }
}
```

**Error Cases:**
- `404` — Roadmap node not found or doesn't belong to user
- `403` — Node is locked (previous node not completed)
- `429` — Cooldown active. Response includes `retry_after`:
```json
{
    "success": false,
    "message": "Assessment cooldown active. Please wait before retrying.",
    "error_code": "COOLDOWN_ACTIVE",
    "errors": {
        "retry_after": "2026-04-20T01:05:00Z",
        "remaining_seconds": 180
    }
}
```

---

## 5.2 Submit Assessment

**`POST /student/assessment/submit`** — Submit assessment answers

**Auth**: Required (student)

**Request Body:**
```json
{
    "attempt_id": 5,
    "answers": [
        { "question_id": 1, "selected_answer": "B" },
        { "question_id": 2, "selected_answer": "C" },
        { "question_id": 3, "selected_answer": "A" },
        { "question_id": 4, "selected_answer": "D" },
        { "question_id": 5, "selected_answer": "B" }
    ]
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `attempt_id` | integer | yes | must belong to user, not yet submitted |
| `answers` | array | yes | must contain all questions |
| `answers.*.question_id` | integer | yes | valid question ID |
| `answers.*.selected_answer` | string | yes | in: A, B, C, D |

**Response (PASSED — score ≥ 85%): `200 OK`**
```json
{
    "success": true,
    "message": "Congratulations! You passed the assessment",
    "data": {
        "is_passed": true,
        "score": 92,
        "total_correct": 4,
        "total_questions": 5,
        "feedback": "Excellent understanding of Node.js core concepts! You demonstrated strong knowledge in event loop, modules, and async patterns.",
        "skill_name": "Node.js",
        "next_node": {
            "id": 3,
            "skill_name": "REST API",
            "is_locked": false
        }
    }
}
```

**Response (FAILED — score < 85%): `200 OK`**
```json
{
    "success": true,
    "message": "Assessment not passed. Keep learning!",
    "data": {
        "is_passed": false,
        "score": 60,
        "total_correct": 3,
        "total_questions": 5,
        "feedback": "You need to improve your understanding of the event loop and stream handling. Review the async programming section and try again.",
        "skill_name": "Node.js",
        "retry_after": "2026-04-20T01:05:00Z",
        "remaining_seconds": 300
    }
}
```

---

## 5.3 Assessment History

**`GET /student/assessment/history`** — Get all assessment attempts

**Auth**: Required (student)

**Query Parameters:**
| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 15 | Items per page |

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Assessment history retrieved",
    "data": [
        {
            "id": 5,
            "skill_name": "Node.js",
            "roadmap_node_id": 2,
            "score": 92,
            "total_correct": 4,
            "total_questions": 5,
            "is_passed": true,
            "completed_at": "2026-04-20T01:00:00Z"
        },
        {
            "id": 4,
            "skill_name": "Node.js",
            "roadmap_node_id": 2,
            "score": 60,
            "total_correct": 3,
            "total_questions": 5,
            "is_passed": false,
            "completed_at": "2026-04-19T23:30:00Z"
        }
    ],
    "meta": { "current_page": 1, "per_page": 15, "total": 2, "last_page": 1 }
}
```

---

## 5.4 Node Assessment History

**`GET /student/assessment/history/{nodeId}`** — Attempts for a specific node

**Auth**: Required (student)

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Node assessment history retrieved",
    "data": {
        "node": {
            "id": 2,
            "skill_name": "Node.js",
            "is_completed": true,
            "best_score": 92
        },
        "attempts": [
            {
                "id": 5,
                "score": 92,
                "is_passed": true,
                "feedback": "Excellent understanding...",
                "completed_at": "2026-04-20T01:00:00Z"
            },
            {
                "id": 4,
                "score": 60,
                "is_passed": false,
                "feedback": "Need improvement in...",
                "completed_at": "2026-04-19T23:30:00Z"
            }
        ]
    }
}
```

---

# 6. STUDENT — PROGRESS TRACKER

## 6.1 Get Overall Progress

**`GET /student/progress`** — Overall learning progress summary

**Auth**: Required (student)

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Progress retrieved successfully",
    "data": {
        "roadmap_completion": 40,
        "total_nodes": 5,
        "completed_nodes": 2,
        "skills": [
            {
                "node_id": 1,
                "skill_name": "JavaScript",
                "is_completed": true,
                "is_locked": false,
                "best_score": 92,
                "attempts_count": 2,
                "completed_at": "2026-04-15"
            },
            {
                "node_id": 2,
                "skill_name": "Node.js",
                "is_completed": true,
                "is_locked": false,
                "best_score": 88,
                "attempts_count": 1,
                "completed_at": "2026-04-18"
            },
            {
                "node_id": 3,
                "skill_name": "REST API",
                "is_completed": false,
                "is_locked": false,
                "best_score": null,
                "attempts_count": 0,
                "completed_at": null
            },
            {
                "node_id": 4,
                "skill_name": "Docker",
                "is_completed": false,
                "is_locked": true,
                "best_score": null,
                "attempts_count": 0,
                "completed_at": null
            },
            {
                "node_id": 5,
                "skill_name": "CI/CD",
                "is_completed": false,
                "is_locked": true,
                "best_score": null,
                "attempts_count": 0,
                "completed_at": null
            }
        ]
    }
}
```

---

## 6.2 Get Node Progress Detail

**`GET /student/progress/{nodeId}`** — Detailed progress for a specific node

**Auth**: Required (student)

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Node progress retrieved",
    "data": {
        "node_id": 2,
        "skill_name": "Node.js",
        "description": "Build server-side applications using Node.js runtime",
        "is_completed": true,
        "is_locked": false,
        "best_score": 92,
        "attempts_count": 2,
        "completed_at": "2026-04-18",
        "can_take_assessment": true,
        "resources": [
            {
                "title": "Node.js Tutorial - freeCodeCamp",
                "url": "https://youtube.com/watch?v=...",
                "type": "video"
            }
        ],
        "recent_attempts": [
            {
                "id": 5,
                "score": 92,
                "is_passed": true,
                "completed_at": "2026-04-18T09:00:00Z"
            }
        ]
    }
}
```

---

# 7. STUDENT — JOB LISTINGS

## 7.1 List Jobs

**`GET /student/jobs`** — Browse all open job listings with filters

**Auth**: Required (student)

**Query Parameters:**
| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `search` | string | — | Search title & description |
| `industry` | string | — | Filter by industry |
| `type` | string | — | Comma-separated: `internship,full_time,part_time,contract,daily_worker` |
| `site` | string | — | Comma-separated: `wfo,wfh,hybrid` |
| `sort` | string | `newest` | `newest`, `oldest`, `match_score`, `applicants_most`, `applicants_least` |
| `page` | integer | 1 | Page number |
| `per_page` | integer | 15 | Items per page (max: 50) |

**Example:** `GET /student/jobs?industry=Technology&type=internship,full_time&site=hybrid&sort=match_score&page=1`

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Job listings retrieved",
    "data": [
        {
            "id": 1,
            "title": "Backend Developer Intern",
            "description": "Looking for a passionate backend developer intern...",
            "company_name": "PT Tech Indonesia",
            "company_avatar": "https://...",
            "employment_type": "internship",
            "site_type": "hybrid",
            "industry": "Technology",
            "location": "Jakarta, Indonesia",
            "required_skills": ["Node.js", "REST API", "MySQL"],
            "match_score": 73,
            "total_applicants": 12,
            "is_applied": false,
            "created_at": "2026-04-10T00:00:00Z"
        },
        {
            "id": 2,
            "title": "Frontend Developer",
            "description": "Join our frontend team...",
            "company_name": "PT Digital Kreatif",
            "company_avatar": "https://...",
            "employment_type": "full_time",
            "site_type": "wfh",
            "industry": "Technology",
            "location": "Remote",
            "required_skills": ["React", "TypeScript", "CSS"],
            "match_score": 45,
            "total_applicants": 8,
            "is_applied": true,
            "created_at": "2026-04-08T00:00:00Z"
        }
    ],
    "meta": { "current_page": 1, "per_page": 15, "total": 45, "last_page": 3 }
}
```

---

## 7.2 Job Detail

**`GET /student/jobs/{id}`** — Get job detail with match score

**Auth**: Required (student)

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Job detail retrieved",
    "data": {
        "id": 1,
        "title": "Backend Developer Intern",
        "description": "Looking for a passionate backend developer intern to join our team. You will work on building REST APIs, integrating third-party services, and learning modern backend technologies.",
        "company": {
            "name": "PT Tech Indonesia",
            "avatar": "https://...",
            "industry": "Technology"
        },
        "employment_type": "internship",
        "site_type": "hybrid",
        "industry": "Technology",
        "location": "Jakarta, Indonesia",
        "status": "open",
        "required_skills": ["Node.js", "REST API", "MySQL", "Docker"],
        "match_score": 73,
        "match_details": {
            "matched_skills": [
                { "skill": "Node.js", "score": 88 },
                { "skill": "REST API", "score": 0 },
                { "skill": "MySQL", "score": 92 }
            ],
            "unmatched_skills": ["Docker"]
        },
        "total_applicants": 12,
        "is_applied": false,
        "created_at": "2026-04-10T00:00:00Z"
    }
}
```

---

## 7.3 Apply to Job

**`POST /student/jobs/{id}/apply`** — Apply to a job listing

**Auth**: Required (student)

**Request Body:** _(none required — single click apply)_

**Response: `201 Created`**
```json
{
    "success": true,
    "message": "Application submitted successfully",
    "data": {
        "application_id": 15,
        "job_id": 1,
        "job_title": "Backend Developer Intern",
        "status": "pending",
        "applied_at": "2026-04-20T01:00:00Z"
    }
}
```

**Error Cases:**
- `400` — Already applied to this job
- `404` — Job not found or closed
- `403` — Job is closed

---

## 7.4 Applied Jobs

**`GET /student/jobs/applied`** — List jobs user has applied to

**Auth**: Required (student)

**Query Parameters:**
| Param | Type | Default |
|-------|------|---------|
| `page` | integer | 1 |
| `per_page` | integer | 15 |

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Applied jobs retrieved",
    "data": [
        {
            "application_id": 15,
            "job": {
                "id": 1,
                "title": "Backend Developer Intern",
                "company_name": "PT Tech Indonesia",
                "company_avatar": "https://...",
                "employment_type": "internship",
                "site_type": "hybrid",
                "status": "open"
            },
            "match_score": 73,
            "applied_at": "2026-04-20T01:00:00Z"
        }
    ],
    "meta": { "current_page": 1, "per_page": 15, "total": 5, "last_page": 1 }
}
```

---

# 8. STUDENT — PORTFOLIO

## 8.1 List Portfolios

**`GET /student/portfolio`** — List own portfolios

**Auth**: Required (student)

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Portfolios retrieved",
    "data": [
        {
            "id": 1,
            "description": "Built a REST API for an e-commerce platform using Laravel and MySQL",
            "external_link": "https://github.com/john/ecommerce-api",
            "is_public": true,
            "skills": ["Laravel", "REST API", "MySQL"],
            "images": [
                "https://localhost:8000/storage/portfolios/1_img1.webp",
                "https://localhost:8000/storage/portfolios/1_img2.webp"
            ],
            "created_at": "2026-04-10T00:00:00Z"
        }
    ]
}
```

---

## 8.2 Create Portfolio

**`POST /student/portfolio`** — Create a new portfolio entry

**Auth**: Required (student)  
**Content-Type**: `multipart/form-data`

**Request Body:**
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `description` | string | yes | min:10, max:2000 |
| `external_link` | string | no | valid URL, nullable |
| `is_public` | boolean | no | default: true |
| `skills[]` | array of strings | yes | min:1 skill, each max:50 chars |
| `images[]` | files | no | max 2 files, each max:2MB, image types |

**Response: `201 Created`**
```json
{
    "success": true,
    "message": "Portfolio created successfully",
    "data": {
        "id": 2,
        "description": "Built a real-time chat app...",
        "external_link": "https://github.com/john/chat-app",
        "is_public": true,
        "skills": ["Node.js", "Socket.io", "Redis"],
        "images": ["https://localhost:8000/storage/portfolios/2_img1.webp"],
        "created_at": "2026-04-20T01:00:00Z"
    }
}
```

**Error Cases:**
- `422` — Validation failed (too many images, file too large, etc.)

---

## 8.3 Update Portfolio

**`PUT /student/portfolio/{id}`** — Update portfolio

**Auth**: Required (student, must be owner)  
**Content-Type**: `multipart/form-data`

**Request Body:** Same fields as create (all optional, images replace existing)

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Portfolio updated successfully",
    "data": { ... }
}
```

---

## 8.4 Delete Portfolio

**`DELETE /student/portfolio/{id}`** — Delete portfolio

**Auth**: Required (student, must be owner)

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Portfolio deleted successfully"
}
```

---

## 8.5 Toggle Portfolio Visibility

**`PATCH /student/portfolio/{id}/toggle`** — Toggle public/private

**Auth**: Required (student, must be owner)

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Portfolio visibility updated",
    "data": {
        "id": 1,
        "is_public": false
    }
}
```

---

# 9. STUDENT — DAILY STREAK

## 9.1 Get Streak Info

**`GET /student/streak`** — Get current streak status

**Auth**: Required (student)

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Streak info retrieved",
    "data": {
        "current_streak": 7,
        "longest_streak": 14,
        "last_checkin_date": "2026-04-19",
        "checked_in_today": false
    }
}
```

---

## 9.2 Daily Check-in

**`POST /student/streak/checkin`** — Perform daily check-in

**Auth**: Required (student)

**Request Body:**
```json
{
    "description": "Hari ini saya menyelesaikan belajar dasar-dasar Node.js dan membuat REST API sederhana"
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `description` | string | yes | min:10, max:500 |

**Business Logic:**
1. Max 1 check-in per day
2. If `last_checkin_date == yesterday` → `current_streak += 1`
3. If `last_checkin_date == today` → reject (already checked in)
4. If `last_checkin_date < yesterday` or `null` → `current_streak = 1` (reset/start)
5. Update `longest_streak` if `current_streak > longest_streak`

**Response: `201 Created`**
```json
{
    "success": true,
    "message": "Check-in successful! Keep up the streak!",
    "data": {
        "current_streak": 8,
        "longest_streak": 14,
        "checked_in_today": true,
        "checkin": {
            "id": 30,
            "description": "Hari ini saya menyelesaikan belajar dasar-dasar Node.js...",
            "checkin_date": "2026-04-20"
        }
    }
}
```

**Error Cases:**
- `400` — Already checked in today:
```json
{
    "success": false,
    "message": "You have already checked in today",
    "error_code": "ALREADY_CHECKED_IN"
}
```

---

## 9.3 Streak History

**`GET /student/streak/history`** — Get check-in history

**Auth**: Required (student)

**Query Parameters:**
| Param | Type | Default |
|-------|------|---------|
| `page` | integer | 1 |
| `per_page` | integer | 15 |

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Check-in history retrieved",
    "data": [
        {
            "id": 30,
            "description": "Belajar Node.js dan REST API...",
            "checkin_date": "2026-04-20",
            "created_at": "2026-04-20T08:30:00Z"
        },
        {
            "id": 29,
            "description": "Review JavaScript fundamentals...",
            "checkin_date": "2026-04-19",
            "created_at": "2026-04-19T09:15:00Z"
        }
    ],
    "meta": { "current_page": 1, "per_page": 15, "total": 30, "last_page": 2 }
}
```

---

# 10. STUDENT — DASHBOARD

## 10.1 Student Dashboard

**`GET /student/dashboard`** — Comprehensive student dashboard

**Auth**: Required (student)

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Dashboard retrieved",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "avatar": "https://...",
            "school_name": "SMK Negeri 1 Jakarta",
            "industry": "Technology"
        },
        "streak": {
            "current_streak": 7,
            "longest_streak": 14,
            "checked_in_today": false
        },
        "assessment_status": {
            "interest_completed": true,
            "interest_field": "Technology",
            "has_roadmap": true
        },
        "roadmap_progress": {
            "career_role": "Backend Developer",
            "total_nodes": 5,
            "completed_nodes": 2,
            "percentage": 40
        },
        "skill_scores": [
            { "skill_name": "JavaScript", "score": 92 },
            { "skill_name": "Node.js", "score": 88 }
        ],
        "portfolio_count": 3,
        "portfolio_visibility": "public",
        "applications": {
            "total": 5,
            "pending": 3
        },
        "recent_activity": [
            {
                "type": "assessment_passed",
                "detail": "Passed Node.js assessment with score 88",
                "date": "2026-04-18"
            },
            {
                "type": "job_applied",
                "detail": "Applied to Backend Developer Intern at PT Tech Indonesia",
                "date": "2026-04-17"
            },
            {
                "type": "streak_checkin",
                "detail": "Daily check-in completed",
                "date": "2026-04-19"
            }
        ]
    }
}
```

---

# 11. PROFESSIONAL — JOB MANAGEMENT

## 11.1 List Own Jobs

**`GET /professional/jobs`** — List jobs created by the professional

**Auth**: Required (professional)

**Query Parameters:**
| Param | Type | Default |
|-------|------|---------|
| `status` | string | — | Filter: `open`, `closed` |
| `page` | integer | 1 |
| `per_page` | integer | 15 |

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Jobs retrieved",
    "data": [
        {
            "id": 1,
            "title": "Backend Developer Intern",
            "employment_type": "internship",
            "site_type": "hybrid",
            "industry": "Technology",
            "location": "Jakarta, Indonesia",
            "status": "open",
            "required_skills": ["Node.js", "REST API", "MySQL"],
            "total_applicants": 12,
            "created_at": "2026-04-10T00:00:00Z"
        }
    ],
    "meta": { ... }
}
```

---

## 11.2 Create Job

**`POST /professional/jobs`** — Create a new job listing

**Auth**: Required (professional)

**Request Body:**
```json
{
    "title": "Backend Developer Intern",
    "description": "Looking for a passionate backend developer intern...",
    "employment_type": "internship",
    "site_type": "hybrid",
    "industry": "Technology",
    "location": "Jakarta, Indonesia",
    "required_skills": ["Node.js", "REST API", "MySQL", "Docker"]
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `title` | string | yes | min:5, max:200 |
| `description` | string | yes | min:20, max:5000 |
| `employment_type` | string | yes | in:`internship,part_time,full_time,contract,daily_worker` |
| `site_type` | string | yes | in:`wfo,wfh,hybrid` |
| `industry` | string | yes | min:2, max:100 |
| `location` | string | no | max:200, nullable |
| `required_skills` | array | yes | min:1 item, each string max:50 |

**Response: `201 Created`**
```json
{
    "success": true,
    "message": "Job listing created successfully",
    "data": {
        "id": 3,
        "title": "Backend Developer Intern",
        "description": "...",
        "employment_type": "internship",
        "site_type": "hybrid",
        "industry": "Technology",
        "location": "Jakarta, Indonesia",
        "status": "open",
        "required_skills": ["Node.js", "REST API", "MySQL", "Docker"],
        "total_applicants": 0,
        "created_at": "2026-04-20T01:00:00Z"
    }
}
```

---

## 11.3 Job Detail

**`GET /professional/jobs/{id}`** — Get job detail with applicant summary

**Auth**: Required (professional, must be owner)

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Job detail retrieved",
    "data": {
        "id": 1,
        "title": "Backend Developer Intern",
        "description": "...",
        "employment_type": "internship",
        "site_type": "hybrid",
        "industry": "Technology",
        "location": "Jakarta, Indonesia",
        "status": "open",
        "required_skills": ["Node.js", "REST API", "MySQL", "Docker"],
        "total_applicants": 12,
        "recent_applicants": [
            {
                "application_id": 15,
                "applicant_name": "John Doe",
                "applicant_avatar": "https://...",
                "match_score": 73,
                "applied_at": "2026-04-20T01:00:00Z"
            }
        ],
        "created_at": "2026-04-10T00:00:00Z"
    }
}
```

---

## 11.4 Update Job

**`PUT /professional/jobs/{id}`** — Update job listing

**Auth**: Required (professional, must be owner)

**Request Body:** Same fields as create (all optional)

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Job listing updated successfully",
    "data": { ... }
}
```

---

## 11.5 Toggle Job Status

**`PATCH /professional/jobs/{id}/status`** — Toggle open/closed

**Auth**: Required (professional, must be owner)

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Job status updated",
    "data": {
        "id": 1,
        "status": "closed"
    }
}
```

---

# 12. PROFESSIONAL — APPLICANT TRACKING

## 12.1 All Applicants

**`GET /professional/applicants`** — All applicants across all jobs

**Auth**: Required (professional)

**Query Parameters:**
| Param | Type | Default |
|-------|------|---------|
| `search` | string | — | Search by name |
| `job_id` | integer | — | Filter by job |
| `page` | integer | 1 |
| `per_page` | integer | 15 |

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Applicants retrieved",
    "data": [
        {
            "application_id": 15,
            "job": {
                "id": 1,
                "title": "Backend Developer Intern"
            },
            "applicant": {
                "id": 5,
                "name": "John Doe",
                "avatar": "https://...",
                "bio": "Passionate student developer...",
                "match_score": 73,
                "email": "john@example.com",
                "phone_number": "08123456789"
            },
            "applied_at": "2026-04-20T01:00:00Z"
        }
    ],
    "meta": { ... }
}
```

---

## 12.2 Applicants by Job

**`GET /professional/jobs/{id}/applicants`** — Applicants for a specific job

**Auth**: Required (professional, must be job owner)

**Query Parameters:**
| Param | Type | Default |
|-------|------|---------|
| `sort` | string | `newest` | `newest`, `match_score` |
| `page` | integer | 1 |
| `per_page` | integer | 15 |

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Job applicants retrieved",
    "data": {
        "job": {
            "id": 1,
            "title": "Backend Developer Intern",
            "total_applicants": 12
        },
        "applicants": [
            {
                "application_id": 15,
                "name": "John Doe",
                "avatar": "https://...",
                "bio": "Passionate student developer...",
                "match_score": 73,
                "email": "john@example.com",
                "phone_number": "08123456789",
                "applied_at": "2026-04-20T01:00:00Z"
            }
        ]
    },
    "meta": { ... }
}
```

---

## 12.3 Applicant Detail

**`GET /professional/applicants/{id}`** — Get applicant detail for a specific application

**Auth**: Required (professional)

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Applicant detail retrieved",
    "data": {
        "application_id": 15,
        "applied_at": "2026-04-20T01:00:00Z",
        "job": {
            "id": 1,
            "title": "Backend Developer Intern",
            "required_skills": ["Node.js", "REST API", "MySQL", "Docker"]
        },
        "applicant": {
            "id": 5,
            "name": "John Doe",
            "avatar": "https://...",
            "email": "john@example.com",
            "phone_number": "08123456789",
            "bio": "Passionate about backend development...",
            "school_name": "SMK Negeri 1 Jakarta",
            "industry": "Technology",
            "linkedin_url": "https://linkedin.com/in/john",
            "match_score": 73
        },
        "match_details": {
            "matched_skills": [
                { "skill": "Node.js", "score": 88 },
                { "skill": "MySQL", "score": 92 }
            ],
            "unmatched_skills": ["REST API", "Docker"]
        }
    }
}
```

---

## 12.4 Full Applicant Profile

**`GET /professional/applicants/{id}/profile`** — Full applicant profile with portfolio

**Auth**: Required (professional)

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Applicant profile retrieved",
    "data": {
        "user": {
            "id": 5,
            "name": "John Doe",
            "avatar": "https://...",
            "email": "john@example.com",
            "phone_number": "08123456789",
            "industry": "Technology",
            "linkedin_url": "https://linkedin.com/in/john",
            "instagram_url": "https://instagram.com/john",
            "school_name": "SMK Negeri 1 Jakarta",
            "bio": "Passionate about backend development and building scalable systems"
        },
        "match_score": 73,
        "skills": [
            { "skill_name": "JavaScript", "score": 92 },
            { "skill_name": "Node.js", "score": 88 },
            { "skill_name": "MySQL", "score": 85 }
        ],
        "roadmap_progress": {
            "career_role": "Backend Developer",
            "completion": 40,
            "completed_nodes": 2,
            "total_nodes": 5
        },
        "portfolios": [
            {
                "id": 1,
                "description": "Built a REST API for an e-commerce platform...",
                "external_link": "https://github.com/john/ecommerce-api",
                "skills": ["Laravel", "MySQL", "REST API"],
                "images": [
                    "https://localhost:8000/storage/portfolios/1_img1.webp"
                ],
                "created_at": "2026-04-10T00:00:00Z"
            }
        ],
        "streak": {
            "current_streak": 7,
            "longest_streak": 14
        }
    }
}
```

---

# 13. PROFESSIONAL — DASHBOARD

## 13.1 Professional Dashboard

**`GET /professional/dashboard`** — Comprehensive professional dashboard

**Auth**: Required (professional)

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Dashboard retrieved",
    "data": {
        "user": {
            "id": 2,
            "name": "Jane Smith",
            "avatar": "https://...",
            "company_name": "PT Tech Indonesia",
            "industry": "Technology"
        },
        "statistics": {
            "total_jobs": 10,
            "active_jobs": 5,
            "closed_jobs": 5,
            "total_applicants": 50,
            "new_applicants_today": 3
        },
        "recent_jobs": [
            {
                "id": 1,
                "title": "Backend Developer Intern",
                "status": "open",
                "total_applicants": 12,
                "employment_type": "internship",
                "site_type": "hybrid",
                "created_at": "2026-04-10T00:00:00Z"
            }
        ],
        "recent_applicants": [
            {
                "application_id": 15,
                "applicant_name": "John Doe",
                "applicant_avatar": "https://...",
                "job_title": "Backend Developer Intern",
                "match_score": 73,
                "applied_at": "2026-04-20T01:00:00Z"
            }
        ]
    }
}
```

---

# 14. ADMIN (Express.js — Separate Server)

> **Base URL**: `http://localhost:3001/api/v1/admin`  
> **Auth**: JWT (jsonwebtoken)  
> **Database**: Same MySQL database as Laravel  

## 14.1 Admin Login

**`POST /login`** — Admin login

**Request Body:**
```json
{
    "email": "admin@kerjain.com",
    "password": "admin_password"
}
```

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 100,
            "name": "Admin KerjaIn",
            "email": "admin@kerjain.com",
            "role": "admin"
        },
        "token": "eyJhbGciOiJIUzI1NiIs..."
    }
}
```

**Error Cases:**
- `401` — Invalid credentials
- `403` — User is not admin role

---

## 14.2 Admin Info

**`GET /me`** — Get admin info

**Auth**: JWT Bearer

**Response: `200 OK`**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 100,
            "name": "Admin KerjaIn",
            "email": "admin@kerjain.com",
            "role": "admin"
        }
    }
}
```

---

## 14.3 Admin Logout

**`POST /logout`** — Invalidate JWT

**Auth**: JWT Bearer

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

---

## 14.4 Admin Dashboard

**`GET /dashboard`** — Platform-wide statistics

**Auth**: JWT Bearer

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Dashboard stats retrieved",
    "data": {
        "statistics": {
            "total_users": 150,
            "users_by_role": {
                "student": 120,
                "professional": 28,
                "admin": 2
            },
            "total_job_listings": 45,
            "active_job_listings": 30,
            "closed_job_listings": 15,
            "total_applications": 200,
            "new_users_this_week": 12,
            "new_jobs_this_week": 5
        }
    }
}
```

---

## 14.5 List Users

**`GET /users`** — List all users with filters

**Auth**: JWT Bearer

**Query Parameters:**
| Param | Type | Default |
|-------|------|---------|
| `role` | string | — | `student`, `professional`, `admin` |
| `search` | string | — | Search name/email |
| `industry` | string | — | Filter by industry |
| `page` | integer | 1 |
| `per_page` | integer | 20 |

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Users retrieved",
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "student",
            "avatar": "https://...",
            "industry": "Technology",
            "is_profile_completed": true,
            "created_at": "2026-04-01T00:00:00Z"
        }
    ],
    "meta": { "current_page": 1, "per_page": 20, "total": 150, "last_page": 8 }
}
```

---

## 14.6 User Detail

**`GET /users/{id}`** — Get full user info

**Auth**: JWT Bearer

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "User detail retrieved",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "student",
            "avatar": "https://...",
            "phone_number": "08123456789",
            "industry": "Technology",
            "linkedin_url": "https://...",
            "is_profile_completed": true,
            "provider": "google",
            "created_at": "2026-04-01T00:00:00Z"
        },
        "profile": {
            "school_name": "SMK Negeri 1 Jakarta",
            "bio": "Passionate developer..."
        },
        "stats": {
            "total_applications": 5,
            "roadmap_progress": 40,
            "current_streak": 7,
            "portfolio_count": 3
        }
    }
}
```

---

## 14.7 Create User

**`POST /users`** — Admin creates a user

**Auth**: JWT Bearer

**Request Body:**
```json
{
    "name": "New User",
    "email": "newuser@example.com",
    "password": "password123",
    "role": "student"
}
```

**Response: `201 Created`**
```json
{
    "success": true,
    "message": "User created successfully",
    "data": { "user": { ... } }
}
```

---

## 14.8 Update User

**`PUT /users/{id}`** — Update user info

**Auth**: JWT Bearer

**Request Body:** (any user fields)
```json
{
    "name": "Updated Name",
    "role": "professional"
}
```

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "User updated successfully",
    "data": { "user": { ... } }
}
```

---

## 14.9 Delete User

**`DELETE /users/{id}`** — Soft delete user

**Auth**: JWT Bearer

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "User deleted successfully"
}
```

---

## 14.10 List Job Listings

**`GET /jobs`** — All job listings (any status, any owner)

**Auth**: JWT Bearer

**Query Parameters:**
| Param | Type | Default |
|-------|------|---------|
| `status` | string | — | `open`, `closed` |
| `search` | string | — | Search title |
| `page` | integer | 1 |
| `per_page` | integer | 20 |

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Job listings retrieved",
    "data": [
        {
            "id": 1,
            "title": "Backend Developer Intern",
            "owner": {
                "id": 2,
                "name": "Jane Smith",
                "company_name": "PT Tech Indonesia"
            },
            "employment_type": "internship",
            "site_type": "hybrid",
            "status": "open",
            "total_applicants": 12,
            "created_at": "2026-04-10T00:00:00Z"
        }
    ],
    "meta": { ... }
}
```

---

## 14.11 Job Detail

**`GET /jobs/{id}`** — Full job detail (admin view)

**Auth**: JWT Bearer

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Job detail retrieved",
    "data": {
        "id": 1,
        "title": "Backend Developer Intern",
        "description": "...",
        "owner": {
            "id": 2,
            "name": "Jane Smith",
            "email": "jane@company.com",
            "company_name": "PT Tech Indonesia"
        },
        "employment_type": "internship",
        "site_type": "hybrid",
        "industry": "Technology",
        "location": "Jakarta",
        "status": "open",
        "required_skills": ["Node.js", "REST API", "MySQL"],
        "total_applicants": 12,
        "created_at": "2026-04-10T00:00:00Z"
    }
}
```

---

## 14.12 Toggle Job Status (Admin)

**`PATCH /jobs/{id}/status`** — Activate/deactivate a job listing

**Auth**: JWT Bearer

**Request Body:**
```json
{
    "status": "closed"
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `status` | string | yes | in: `open`, `closed` |

**Response: `200 OK`**
```json
{
    "success": true,
    "message": "Job status updated by admin",
    "data": {
        "id": 1,
        "status": "closed"
    }
}
```

---

# ENDPOINT SUMMARY TABLE

## Laravel API (Port 8000)

| # | Method | Endpoint | Auth | Rate | Description |
|---|--------|----------|------|------|-------------|
| 1 | POST | `/api/v1/register` | No | auth | Register |
| 2 | POST | `/api/v1/login` | No | auth | Login |
| 3 | POST | `/api/v1/logout` | Yes | api | Logout |
| 4 | GET | `/api/v1/me` | Yes | api | Current user |
| 5 | POST | `/api/v1/forgot-password` | No | auth | Forgot password |
| 6 | POST | `/api/v1/reset-password` | No | auth | Reset password |
| 7 | GET | `/api/v1/auth/google` | No | — | Google OAuth |
| 8 | GET | `/api/v1/auth/google/callback` | No | — | Google callback |
| 9 | POST | `/api/v1/complete-profile` | Yes | api | Complete profile |
| 10 | GET | `/api/v1/profile` | Yes | api | Get profile |
| 11 | PUT | `/api/v1/profile` | Yes | api | Update profile |
| 12 | POST | `/api/v1/profile/avatar` | Yes | api | Upload avatar |
| 13 | GET | `/api/v1/student/dashboard` | Student | api | Dashboard |
| 14 | POST | `/api/v1/student/interest/start` | Student | ai | Start assessment |
| 15 | POST | `/api/v1/student/interest/answer` | Student | ai | Answer question |
| 16 | GET | `/api/v1/student/career-recommendations` | Student | api | Get recommendations |
| 17 | POST | `/api/v1/student/roadmap/generate` | Student | ai | Generate roadmap |
| 18 | GET | `/api/v1/student/roadmap` | Student | api | Get roadmap |
| 19 | POST | `/api/v1/student/assessment/start` | Student | ai | Start self assessment |
| 20 | POST | `/api/v1/student/assessment/submit` | Student | api | Submit assessment |
| 21 | GET | `/api/v1/student/assessment/history` | Student | api | Assessment history |
| 22 | GET | `/api/v1/student/assessment/history/{nodeId}` | Student | api | Node history |
| 23 | GET | `/api/v1/student/progress` | Student | api | Overall progress |
| 24 | GET | `/api/v1/student/progress/{nodeId}` | Student | api | Node progress |
| 25 | GET | `/api/v1/student/jobs` | Student | api | List jobs |
| 26 | GET | `/api/v1/student/jobs/applied` | Student | api | Applied jobs |
| 27 | GET | `/api/v1/student/jobs/{id}` | Student | api | Job detail |
| 28 | POST | `/api/v1/student/jobs/{id}/apply` | Student | api | Apply to job |
| 29 | GET | `/api/v1/student/portfolio` | Student | api | List portfolios |
| 30 | POST | `/api/v1/student/portfolio` | Student | api | Create portfolio |
| 31 | PUT | `/api/v1/student/portfolio/{id}` | Student | api | Update portfolio |
| 32 | DELETE | `/api/v1/student/portfolio/{id}` | Student | api | Delete portfolio |
| 33 | PATCH | `/api/v1/student/portfolio/{id}/toggle` | Student | api | Toggle visibility |
| 34 | GET | `/api/v1/student/streak` | Student | api | Streak info |
| 35 | POST | `/api/v1/student/streak/checkin` | Student | api | Daily check-in |
| 36 | GET | `/api/v1/student/streak/history` | Student | api | Check-in history |
| 37 | GET | `/api/v1/professional/dashboard` | Professional | api | Dashboard |
| 38 | GET | `/api/v1/professional/jobs` | Professional | api | List own jobs |
| 39 | POST | `/api/v1/professional/jobs` | Professional | api | Create job |
| 40 | GET | `/api/v1/professional/jobs/{id}` | Professional | api | Job detail |
| 41 | PUT | `/api/v1/professional/jobs/{id}` | Professional | api | Update job |
| 42 | PATCH | `/api/v1/professional/jobs/{id}/status` | Professional | api | Toggle status |
| 43 | GET | `/api/v1/professional/applicants` | Professional | api | All applicants |
| 44 | GET | `/api/v1/professional/jobs/{id}/applicants` | Professional | api | Job applicants |
| 45 | GET | `/api/v1/professional/applicants/{id}` | Professional | api | Applicant detail |
| 46 | GET | `/api/v1/professional/applicants/{id}/profile` | Professional | api | Full profile |

## Express.js Admin API (Port 3001)

| # | Method | Endpoint | Auth | Description |
|---|--------|----------|------|-------------|
| 47 | POST | `/api/v1/admin/login` | No | Admin login |
| 48 | POST | `/api/v1/admin/logout` | JWT | Admin logout |
| 49 | GET | `/api/v1/admin/me` | JWT | Admin info |
| 50 | GET | `/api/v1/admin/dashboard` | JWT | Dashboard stats |
| 51 | GET | `/api/v1/admin/users` | JWT | List users |
| 52 | GET | `/api/v1/admin/users/{id}` | JWT | User detail |
| 53 | POST | `/api/v1/admin/users` | JWT | Create user |
| 54 | PUT | `/api/v1/admin/users/{id}` | JWT | Update user |
| 55 | DELETE | `/api/v1/admin/users/{id}` | JWT | Delete user |
| 56 | GET | `/api/v1/admin/jobs` | JWT | List jobs |
| 57 | GET | `/api/v1/admin/jobs/{id}` | JWT | Job detail |
| 58 | PATCH | `/api/v1/admin/jobs/{id}/status` | JWT | Toggle job status |

**Total: 58 endpoints** (46 Laravel + 12 Express.js)
