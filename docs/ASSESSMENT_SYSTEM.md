# Assessment System Documentation

## Overview

The Assessment System provides comprehensive course-level evaluation capabilities for the Laravel 12 LMS application. Unlike the lesson-specific Quiz System, assessments are course-level evaluations that support multiple question types, prerequisite requirements, and detailed analytics.

## Features

### Core Functionality
- **Multiple Question Types**: Support for multiple choice, true/false, short answer, and essay questions
- **Auto-Grading**: Automatic scoring for multiple choice and true/false questions
- **Manual Grading**: Administrator review and scoring for short answer and essay questions
- **Prerequisites**: Configurable requirements (quiz completion, minimum progress, lesson completion)
- **Availability Windows**: Time-based access control with start and end dates
- **Attempt Limits**: Configurable maximum attempts per student
- **Timer System**: Real-time countdown with automatic submission on timeout
- **Analytics**: Comprehensive statistics for assessments, questions, and student performance

### Interfaces
- **Admin Dashboard (Web)**: Full assessment lifecycle management
- **Student API (RESTful)**: Mobile/frontend access for taking assessments

## Architecture

### Service Layer
- `AssessmentService`: Assessment and question management
- `AssessmentAttemptService`: Attempt creation and submission
- `AssessmentGradingService`: Auto and manual grading
- `PrerequisiteCheckService`: Prerequisite validation

### Models
- `Assessment`: Course-level assessment configuration
- `AssessmentQuestion`: Individual questions with type-specific data
- `AssessmentAttempt`: Student attempt tracking
- `AssessmentAnswer`: Individual answer storage and grading
- `AssessmentPrerequisite`: Prerequisite configuration

## API Endpoints

### Student API (Protected with Sanctum)

#### Get Assessment Details
```
GET /api/v1/assessments/{assessment}
```
Returns assessment details including questions, time limit, and attempts remaining.

#### Start Assessment Attempt
```
POST /api/v1/assessments/{assessment}/start
```
Creates a new attempt and returns start time.

#### Submit Assessment
```
POST /api/v1/assessment-attempts/{attempt}/submit
```
Submits answers and returns results (immediate or partial based on question types).

**Request Body:**
```json
{
  "answers": [
    {
      "question_id": 1,
      "answer": {"selected_option_id": "a"}
    },
    {
      "question_id": 2,
      "answer": {"text": "Student's written response"}
    }
  ]
}
```

#### Get Remaining Time
```
GET /api/v1/assessment-attempts/{attempt}/remaining-time
```
Returns remaining seconds for the attempt.

#### Get Attempt History
```
GET /api/v1/assessments/{assessment}/history
```
Returns all attempts for the authenticated student.

#### Get Attempt Details
```
GET /api/v1/assessment-attempts/{attempt}
```
Returns detailed attempt information including answers and feedback.

### Admin Web Routes (Protected with Auth + Admin Middleware)

#### Assessment Management
- `GET /admin/assessments` - List all assessments
- `GET /admin/assessments/create` - Show create form
- `POST /admin/assessments` - Create new assessment
- `GET /admin/assessments/{id}/edit` - Show edit form
- `PUT /admin/assessments/{id}` - Update assessment
- `DELETE /admin/assessments/{id}` - Delete assessment

#### Question Management
- `POST /admin/assessments/{id}/questions` - Add question
- `PUT /admin/assessment-questions/{id}` - Update question
- `DELETE /admin/assessment-questions/{id}` - Delete question
- `POST /admin/assessments/{id}/questions/reorder` - Reorder questions

#### Prerequisite Management
- `POST /admin/assessments/{id}/prerequisites` - Add prerequisite
- `DELETE /admin/assessment-prerequisites/{id}` - Remove prerequisite

#### Grading
- `GET /admin/assessment-grading/pending` - View pending grading queue
- `GET /admin/assessment-grading/attempts/{id}` - View attempt for grading
- `POST /admin/assessment-grading/answers/{id}` - Grade answer

#### Analytics
- `GET /admin/assessments/{id}/analytics` - View analytics
- `GET /admin/assessments/{id}/analytics/export` - Export CSV

## Question Types

### Multiple Choice
```json
{
  "question_type": "multiple_choice",
  "question_text": "What is 2 + 2?",
  "points": 5,
  "options": [
    {"id": "a", "text": "3"},
    {"id": "b", "text": "4"},
    {"id": "c", "text": "5"},
    {"id": "d", "text": "6"}
  ],
  "correct_answer": {"correct_option_id": "b"}
}
```

### True/False
```json
{
  "question_type": "true_false",
  "question_text": "The sky is blue.",
  "points": 3,
  "correct_answer": {"correct_value": true}
}
```

### Short Answer
```json
{
  "question_type": "short_answer",
  "question_text": "Explain the water cycle.",
  "points": 10,
  "grading_rubric": "Should mention evaporation, condensation, and precipitation."
}
```

### Essay
```json
{
  "question_type": "essay",
  "question_text": "Discuss the impact of climate change.",
  "points": 20,
  "grading_rubric": "Comprehensive analysis with examples and citations."
}
```

## Prerequisites

### Quiz Completion
Requires all course quizzes to be passed.
```json
{
  "prerequisite_type": "quiz_completion",
  "prerequisite_data": {"require_all_quizzes": true}
}
```

### Minimum Progress
Requires minimum course progress percentage.
```json
{
  "prerequisite_type": "minimum_progress",
  "prerequisite_data": {"minimum_percentage": 75}
}
```

### Lesson Completion
Requires specific lessons to be completed.
```json
{
  "prerequisite_type": "lesson_completion",
  "prerequisite_data": {"lesson_ids": [1, 2, 3]}
}
```

## Grading Workflow

### Auto-Grading
1. Student submits assessment
2. System automatically grades multiple choice and true/false questions
3. If all questions are auto-graded, final score is calculated immediately
4. Student receives instant results

### Manual Grading
1. Student submits assessment with essay/short answer questions
2. System auto-grades applicable questions
3. Attempt status set to `grading_pending`
4. Administrator reviews and grades pending questions
5. System recalculates total score
6. Student can view updated results

## Database Schema

### Key Tables
- `assessments`: Assessment configuration
- `assessment_questions`: Questions with type-specific data
- `assessment_attempts`: Student attempts with scores
- `assessment_answers`: Individual answers with grading data
- `assessment_prerequisites`: Prerequisite configuration

### Relationships
- Assessment belongs to Course
- Assessment has many Questions
- Assessment has many Attempts
- Assessment has many Prerequisites
- Attempt belongs to Assessment and Student
- Attempt has many Answers
- Answer belongs to Attempt and Question

## Testing

### Running Tests
```bash
php artisan test
```

### Test Coverage
- Unit tests for services and models
- Feature tests for API endpoints
- Integration tests for complete workflows
- Validation tests for form requests

### Factories
All models have factories for testing and seeding:
```php
Assessment::factory()->withQuestions(5)->create();
AssessmentAttempt::factory()->passed()->create();
AssessmentQuestion::factory()->multipleChoice()->create();
```

### Seeding Demo Data
```bash
php artisan db:seed --class=AssessmentSeeder
```

## Security

### Authentication
- **Student API**: Sanctum token authentication
- **Admin Dashboard**: Session authentication with admin middleware

### Authorization
- Students can only access assessments for enrolled courses
- Students can only view their own attempts
- Email verification required for assessment access
- Administrators require admin role for all management operations

### Data Integrity
- Database transactions for multi-step operations
- Validation at multiple layers (request, service, model)
- Custom exceptions for business rule violations
- Attempt ownership verification

## Performance Considerations

### Optimization
- Eager loading of relationships to prevent N+1 queries
- Indexed columns for common queries (status, completion_time)
- Pagination for large result sets
- Efficient prerequisite checking

### Caching
Consider caching:
- Assessment details (invalidate on update)
- Student prerequisite status
- Analytics data (with time-based expiration)

## Error Handling

### Custom Exceptions
- `AssessmentNotFoundException`: Assessment not found (404)
- `NotEnrolledException`: Student not enrolled in course (403)
- `PrerequisitesNotMetException`: Prerequisites not met (403)
- `MaxAttemptsExceededException`: Attempt limit reached (403)
- `TimeLimitExceededException`: Time limit exceeded (422)
- `AssessmentNotAvailableException`: Outside availability window (403)

### Error Response Format
```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable error message",
    "context": {
      "unmet_prerequisites": []
    }
  }
}
```

## Future Enhancements

### Potential Features
- Question banks for random question selection
- Adaptive assessments based on performance
- Peer review for essay questions
- Proctoring integration
- Certificate generation on passing
- Assessment templates
- Question import/export
- Bulk grading interface
- Advanced analytics with charts
- Student feedback collection

## Support

For issues or questions:
1. Check the requirements and design documents in `.kiro/specs/assessment-system/`
2. Review the test files for usage examples
3. Consult the API documentation above
4. Contact the development team

## License

This assessment system is part of the Laravel 12 LMS application.
