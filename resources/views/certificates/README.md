# Certificate PDF Template

## Overview

This directory contains the Blade template for generating professional PDF certificates for course completion in the LMS.

## Template: certificate.blade.php

### Design Features

The certificate template (`certificate.blade.php`) provides a professional, print-friendly layout with the following characteristics:

#### Visual Design
- **Landscape orientation** (11" x 8.5") optimized for certificate display
- **Professional border design** with multiple layers:
  - Outer border: Dark blue (#2c3e50) 20px solid
  - Inner accent: Light blue (#3498db) 10px inset shadow
  - Decorative border: Light gray (#bdc3c7) 3px solid
- **Elegant typography** using Georgia and Palatino serif fonts
- **Decorative elements** including ornamental dividers with symbols
- **Color scheme**: Professional blues and grays with green accent for grades

#### Required Fields (Per Requirements 1.10, 7.2-7.7)

The template includes all required fields:

1. **Student Name** - Prominently displayed in large, bold text (42px)
2. **Course Title** - Styled in blue italic font (28px)
3. **Instructor Name** - Displayed in signature section
4. **Completion Date** - Formatted as "Month Day, Year"
5. **Grade** - Shown in highlighted section (only if not "Completed")
6. **Certificate ID** - Displayed at bottom in metadata section
7. **Verification URL** - Clickable link at bottom for verification

#### Layout Sections

1. **Header**
   - "CERTIFICATE" title in large uppercase letters
   - "of Completion" subtitle

2. **Body**
   - "This certificate is proudly presented to" text
   - Student name with decorative borders
   - "For successfully completing the course" text
   - Course title in prominent styling
   - Grade section (conditional - only shown if grade is not "Completed")

3. **Footer**
   - Instructor signature line with name
   - Completion date section

4. **Metadata**
   - Certificate ID
   - Verification URL

#### Print-Friendly Features

- **@page directive** sets landscape orientation and removes margins
- **print-color-adjust: exact** ensures colors print correctly
- **High contrast** text for readability
- **No background images** for faster printing
- **Optimized page size** prevents content from being cut off

#### Conditional Display

The template intelligently handles different scenarios:

- **With Grade**: Shows "Performance Grade" section with the grade value (Excellent, Very Good, Good, Pass)
- **Without Grade** (Completed): Hides the grade section entirely for courses without assessments

#### Usage

The template expects a `$certificate` object with the following properties:

```php
$certificate = [
    'student_name' => string,
    'course_title' => string,
    'instructor_name' => string,
    'completion_date' => Carbon\Carbon (datetime),
    'grade' => string ('Excellent'|'Very Good'|'Good'|'Pass'|'Completed'),
    'certificate_id' => string (format: CERT-YYYY-XXXXX),
    'verification_url' => string (full URL),
];
```

#### Testing

The template is tested in `tests/Unit/CertificateTemplateTest.php` with the following test cases:

1. Renders all required fields correctly
2. Handles "Completed" grade (no grade section shown)
3. Formats dates correctly
4. Includes print-friendly styles
5. Contains professional layout elements

All tests pass successfully, validating the template meets requirements.

#### Future Enhancements

Potential improvements for future iterations:

- QR code generation for verification URL
- Customizable color schemes per institution
- Logo placement for branding
- Digital signature images
- Multiple language support
- Accessibility improvements (ARIA labels, semantic HTML)

## Files

- `certificate.blade.php` - Main certificate template
- `README.md` - This documentation file

## Requirements Validation

This template satisfies the following requirements from the Certificate System specification:

- **Requirement 1.10**: Include all required fields in PDF
- **Requirement 7.2**: Professional Certificate_Template applied
- **Requirement 7.3**: Student name prominently displayed
- **Requirement 7.4**: Course title, instructor name, and completion date included
- **Requirement 7.5**: Grade included (or "Completed" if no assessments)
- **Requirement 7.6**: Unique Certificate_ID included
- **Requirement 7.7**: Verification URL included

## Related Files

- Model: `app/Models/Certificate.php`
- Tests: `tests/Unit/CertificateTemplateTest.php`
- PDF Generator: `app/Services/PdfGenerator.php` (to be implemented)
