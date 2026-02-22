# Windows PowerShell verification script for EduTrail backend fixes
# Run this from the project root directory with: powershell -ExecutionPolicy Bypass -File verify.ps1

Write-Host "=== EduTrail Backend Verification Script ===" -ForegroundColor Cyan
Write-Host ""

$ProjectDir = Get-Location

# Check if in project directory
if (!(Test-Path "$ProjectDir\artisan")) {
    Write-Host "Error: Not in Laravel project directory" -ForegroundColor Red
    Write-Host "Expected artisan file at: $ProjectDir\artisan"
    exit 1
}

Write-Host "1. Checking PHP version..." -ForegroundColor Yellow
php -v | Select-Object -First 1

Write-Host ""
Write-Host "2. Checking composer dependencies..." -ForegroundColor Yellow
if (Test-Path "$ProjectDir\vendor") {
    Write-Host "✓ vendor/ exists" -ForegroundColor Green
}
else {
    Write-Host "✗ vendor/ not found. Run: composer install" -ForegroundColor Red
}

Write-Host ""
Write-Host "3. Checking .env file..." -ForegroundColor Yellow
if (Test-Path "$ProjectDir\.env") {
    Write-Host "✓ .env exists" -ForegroundColor Green
    $envContent = Get-Content "$ProjectDir\.env" -Raw
    if ($envContent -match "APP_KEY=base64:") {
        Write-Host "✓ APP_KEY is set" -ForegroundColor Green
    }
    else {
        Write-Host "⚠ APP_KEY may not be set" -ForegroundColor Yellow
    }
}
else {
    Write-Host "✗ .env file not found" -ForegroundColor Red
}

Write-Host ""
Write-Host "4. Checking database migrations..." -ForegroundColor Yellow
if (Test-Path "$ProjectDir\database\migrations\2026_02_11_150853_create_projects_table.php") {
    Write-Host "✓ Projects migration exists" -ForegroundColor Green
}
else {
    Write-Host "✗ Projects migration not found" -ForegroundColor Red
}

Write-Host ""
Write-Host "5. Checking key files are in place..." -ForegroundColor Yellow

$files = @(
    "app\Http\Controllers\ProjectController.php",
    "app\Http\Controllers\StorageController.php",
    "app\Http\Controllers\DebugController.php",
    "app\Models\Project.php",
    "routes\api.php"
)

foreach ($file in $files) {
    if (Test-Path "$ProjectDir\$file") {
        Write-Host "✓ $file" -ForegroundColor Green
    }
    else {
        Write-Host "✗ $file NOT FOUND" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "6. Quick lint check..." -ForegroundColor Yellow
$lintOutput = php -l "$ProjectDir\app\Http\Controllers\ProjectController.php" 2>&1
if ($lintOutput -match "No syntax errors") {
    Write-Host "✓ ProjectController.php syntax OK" -ForegroundColor Green
}
else {
    Write-Host "✗ Syntax error in ProjectController.php" -ForegroundColor Red
    Write-Host $lintOutput
}

Write-Host ""
Write-Host "=== Pre-flight Checks Complete ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Green
Write-Host "1. Verify database: mysql -u root (or your DB user)"
Write-Host "2. Run migrations: php artisan migrate --force"
Write-Host "3. Create storage link: php artisan storage:link"
Write-Host "4. Start server: php artisan serve"
Write-Host "5. Test endpoints with curl (see DEBUGGING_GUIDE.md)"
Write-Host ""
Write-Host "For full verification steps, see: FIX_SUMMARY.md"
