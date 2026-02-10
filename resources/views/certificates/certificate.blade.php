<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Completion</title>
    <style>
        @page {
            margin: 0;
            size: 11in 8.5in landscape;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            width: 11in;
            height: 8.5in;
            position: relative;
            padding: 0;
            margin: 0;
        }
        
        .certificate-container {
            width: 100%;
            height: 100%;
            padding: 0.75in;
            position: relative;
            background: white;
            border: 20px solid #2c3e50;
            box-shadow: inset 0 0 0 10px #3498db;
        }
        
        .certificate-border {
            width: 100%;
            height: 100%;
            border: 3px solid #bdc3c7;
            padding: 40px;
            position: relative;
        }
        
        .certificate-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .certificate-title {
            font-size: 48px;
            font-weight: bold;
            color: #2c3e50;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 10px;
            font-family: 'Palatino', 'Georgia', serif;
        }
        
        .certificate-subtitle {
            font-size: 20px;
            color: #7f8c8d;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        .certificate-body {
            text-align: center;
            margin: 40px 0;
        }
        
        .presented-to {
            font-size: 18px;
            color: #7f8c8d;
            margin-bottom: 15px;
            letter-spacing: 1px;
        }
        
        .student-name {
            font-size: 42px;
            font-weight: bold;
            color: #2c3e50;
            margin: 20px 0;
            padding: 15px 0;
            border-top: 2px solid #3498db;
            border-bottom: 2px solid #3498db;
            font-family: 'Palatino', 'Georgia', serif;
        }
        
        .achievement-text {
            font-size: 16px;
            color: #34495e;
            line-height: 1.8;
            margin: 25px auto;
            max-width: 80%;
        }
        
        .course-title {
            font-size: 28px;
            font-weight: bold;
            color: #3498db;
            margin: 20px 0;
            font-style: italic;
        }
        
        .grade-section {
            margin: 25px 0;
            padding: 15px;
            background: #ecf0f1;
            border-radius: 8px;
            display: inline-block;
        }
        
        .grade-label {
            font-size: 14px;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .grade-value {
            font-size: 24px;
            font-weight: bold;
            color: #27ae60;
            margin-top: 5px;
        }
        
        .certificate-footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 0 60px;
        }
        
        .signature-section {
            text-align: center;
            flex: 1;
        }
        
        .signature-line {
            width: 200px;
            border-top: 2px solid #2c3e50;
            margin: 0 auto 10px;
            padding-top: 5px;
        }
        
        .signature-label {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 3px;
        }
        
        .signature-name {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .date-section {
            text-align: center;
            flex: 1;
        }
        
        .date-label {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .date-value {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .certificate-metadata {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #95a5a6;
        }
        
        .certificate-id {
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        
        .verification-url {
            color: #3498db;
            text-decoration: none;
        }
        
        .decorative-element {
            text-align: center;
            margin: 20px 0;
        }
        
        .decorative-line {
            display: inline-block;
            width: 100px;
            height: 2px;
            background: linear-gradient(to right, transparent, #3498db, transparent);
            margin: 0 20px;
            vertical-align: middle;
        }
        
        .decorative-symbol {
            display: inline-block;
            font-size: 24px;
            color: #3498db;
            vertical-align: middle;
        }
        
        /* Print-specific styles */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .certificate-container {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate-border">
            <!-- Header -->
            <div class="certificate-header">
                <div class="certificate-title">Certificate</div>
                <div class="certificate-subtitle">of Completion</div>
            </div>
            
            <!-- Decorative Element -->
            <div class="decorative-element">
                <span class="decorative-line"></span>
                <span class="decorative-symbol">✦</span>
                <span class="decorative-line"></span>
            </div>
            
            <!-- Body -->
            <div class="certificate-body">
                <div class="presented-to">This certificate is proudly presented to</div>
                
                <div class="student-name">{{ $certificate->student_name }}</div>
                
                <div class="achievement-text">
                    For successfully completing the course
                </div>
                
                <div class="course-title">{{ $certificate->course_title }}</div>
                
                @if($certificate->grade !== 'Completed')
                <div class="grade-section">
                    <div class="grade-label">Performance Grade</div>
                    <div class="grade-value">{{ $certificate->grade }}</div>
                </div>
                @endif
            </div>
            
            <!-- Decorative Element -->
            <div class="decorative-element">
                <span class="decorative-line"></span>
                <span class="decorative-symbol">✦</span>
                <span class="decorative-line"></span>
            </div>
            
            <!-- Footer with Signatures -->
            <div class="certificate-footer">
                <div class="signature-section">
                    <div class="signature-line"></div>
                    <div class="signature-label">Instructor</div>
                    <div class="signature-name">{{ $certificate->instructor_name }}</div>
                </div>
                
                <div class="date-section">
                    <div class="date-label">Date of Completion</div>
                    <div class="date-value">{{ $certificate->completion_date->format('F d, Y') }}</div>
                </div>
            </div>
            
            <!-- Certificate Metadata -->
            <div class="certificate-metadata">
                <div class="certificate-id">Certificate ID: {{ $certificate->certificate_id }}</div>
                <div>
                    Verify at: <a href="{{ $certificate->verification_url }}" class="verification-url">{{ $certificate->verification_url }}</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
