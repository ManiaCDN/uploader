# Test Scenarios Overview

## ✅ COMPLETED

### 1. File Browsing Tests (`FileBrowsingTest.php`)
- User can view their own files
- User cannot see other users' files
- Subfolder navigation
- File listing functionality

### 2. File Upload Workflow Tests (`FileUploadTest.php`)
- ✅ Basic file upload scenario covered
- ✅ `oneup/uploader-bundle` integration tested
- ✅ `blocked_files` file creation verified
- ✅ vfsStream filesystem isolation working

### 3. Admin Review Workflow Tests (`AdminReviewTest.php`, `MailerTest.php`)
- Email notifications when files are approved (`symfony/swiftmailer-bundle`)
- File unblocking (approve) functionality
- File rejection (stays blocked)
- File deletion from filesystem and blocked list
- Admin review interface (only blocked files listed)
- Admin-only file download via `/admin/review/download`
- CSRF protection on review form
- Access restriction for non-admin users

### 4. LOC File Download Tests (`LocDownloadTest.php`)
- ZIP file creation for .loc files (`maennchen/zipstream-php` via `BrowseController::downloadLocsAction`)
- .loc entry names and contents (public URLs) for root and subdirectories
- Authentication required (`/browse` access control)
- `tests/Support/ZipReader.php`: minimal zip parser (no ext-zip dependency, uses `gzinflate`)

## ⏳ PENDING (Next Steps)

### 5. Homepage Markdown Content Tests - `michelf/php-markdown` + database
- Markdown rendering on homepage
- Admin settings for homepage content
- Database storage/retrieval of settings

### 6. OAuth2 Login Flow Tests - `knpuniversity/oauth2-client-bundle`
- Complete authentication workflow
- User creation/updates from Maniaplanet API
- Session management

### 7. CLI Command Tests - `symfony/console`
- `BlockedFilesReminderCommand` functionality
- Email sending for admin notifications
- Console application testing

## Current Status Summary

**Progress**: 4/7 scenarios completed ✅
**Next Action**: Start Homepage Markdown Content Tests (`michelf/php-markdown` + `Setting` entity)
**Foundation**: Solid testing infrastructure established with vfsStream, database patterns, and file upload validation

## Dependencies Covered

| Scenario | Library/Component | Purpose |
|-----------|------------------|----------|
| File Upload | `oneup/uploader-bundle` | File handling and validation |
| Admin Review | `symfony/swiftmailer-bundle` | Email notifications |
| LOC Download | `maennchen/zipstream-php` | ZIP file creation |
| Homepage | `michelf/php-markdown` | Markdown rendering |
| OAuth2 | `knpuniversity/oauth2-client-bundle` | Authentication |
| CLI | `symfony/console` | Command execution |
