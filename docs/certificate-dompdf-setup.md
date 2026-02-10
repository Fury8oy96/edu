# Certificate System - DomPDF Setup

## Overview

This document describes the DomPDF configuration for the Certificate System in the LMS.

## Installation

The Laravel DomPDF package has been installed via Composer:

```bash
composer require barryvdh/laravel-dompdf
```

**Package Version**: v3.1.1  
**DomPDF Version**: v3.1.4

## Configuration

### DomPDF Configuration

The DomPDF configuration file is located at `config/dompdf.php`. Key settings:

- **Paper Size**: A4 (default)
- **Orientation**: Portrait (default)
- **DPI**: 96
- **Font Directory**: `storage/fonts`
- **Font Cache**: `storage/fonts`
- **Enable PHP**: `false` (security)
- **Enable Remote**: `false` (security)

### Storage Configuration

A dedicated filesystem disk has been configured for certificate PDFs:

**Disk Name**: `certificates`  
**Location**: `storage/app/certificates`  
**Visibility**: Private  
**Driver**: Local

Configuration in `config/filesystems.php`:

```php
'certificates' => [
    'driver' => 'local',
    'root' => storage_path('app/certificates'),
    'visibility' => 'private',
    'throw' => false,
    'report' => false,
],
```

### Directory Structure

```
storage/
├── app/
│   └── certificates/        # Certificate PDFs stored here
│       └── .gitignore       # Prevents PDFs from being committed
└── fonts/                   # DomPDF fonts and font cache
    └── .gitignore           # Prevents font cache from being committed
```

## Usage

### Basic Usage

```php
use Barryvdh\DomPDF\Facade\Pdf;

// Generate PDF from view
$pdf = Pdf::loadView('certificates.template', $data);

// Save to storage
$path = $pdf->save(storage_path('app/certificates/CERT-2024-00001.pdf'));

// Or save using Storage facade
use Illuminate\Support\Facades\Storage;

$pdfContent = $pdf->output();
Storage::disk('certificates')->put('CERT-2024-00001.pdf', $pdfContent);
```

### Streaming PDF Downloads

```php
// Stream PDF to browser
return $pdf->stream('certificate.pdf');

// Force download
return $pdf->download('certificate.pdf');
```

### Configuration Options

```php
// Set paper size and orientation
$pdf = Pdf::loadView('certificates.template', $data)
    ->setPaper('a4', 'landscape');

// Set custom options
$pdf = Pdf::setOptions([
    'dpi' => 150,
    'defaultFont' => 'sans-serif'
])->loadView('certificates.template', $data);
```

## Security Considerations

1. **PHP Execution Disabled**: `enable_php` is set to `false` to prevent arbitrary PHP execution in PDFs
2. **Remote Access Disabled**: `enable_remote` is set to `false` to prevent loading remote resources
3. **Private Storage**: Certificate PDFs are stored in a private disk, not publicly accessible
4. **Chroot Protection**: DomPDF is restricted to the application base path

## Performance Considerations

1. **Asynchronous Generation**: PDF generation should be done in queue jobs to avoid blocking requests
2. **Font Subsetting**: Currently disabled; enable if file size becomes an issue
3. **Font Cache**: Fonts are cached in `storage/fonts` for improved performance
4. **Temporary Files**: System temp directory is used for temporary files during generation

## Troubleshooting

### Common Issues

**Issue**: "Font directory not writable"  
**Solution**: Ensure `storage/fonts` directory exists and is writable by the web server

**Issue**: "Unable to save PDF"  
**Solution**: Ensure `storage/app/certificates` directory exists and is writable

**Issue**: "Missing fonts in PDF"  
**Solution**: Check that required fonts are available in `storage/fonts` or use standard PDF fonts

### Verification

To verify the installation:

```bash
# Check if DomPDF facade is available
php artisan tinker --execute="echo class_exists('Barryvdh\DomPDF\Facade\Pdf') ? 'OK' : 'FAIL';"

# Check certificates disk configuration
php artisan tinker --execute="echo config('filesystems.disks.certificates') ? 'OK' : 'FAIL';"
```

## Next Steps

1. Create certificate PDF template (Blade view) - Task 5.2
2. Implement PdfGenerator service class - Task 5.3
3. Write property-based tests for PDF generation - Tasks 5.4-5.6

## References

- [Laravel DomPDF Documentation](https://github.com/barryvdh/laravel-dompdf)
- [DomPDF Documentation](https://github.com/dompdf/dompdf)
- [Certificate System Requirements](.kiro/specs/certificate-system/requirements.md)
- [Certificate System Design](.kiro/specs/certificate-system/design.md)
