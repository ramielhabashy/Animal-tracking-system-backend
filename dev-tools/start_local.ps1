# Local development start script (PowerShell)
# Usage: .\start_local.ps1

$backendPath = "C:\animal-tracking-backup-20260505-224235\backend"

Set-Location $backendPath

# Check if .env exists
if (-not (Test-Path ".env")) {
    Write-Host "Creating .env from .env.example..."
    Copy-Item ".env.example" ".env"
    php artisan key:generate
}

# Start PHP development server
Write-Host "Starting Laravel server on http://localhost:8050..."
php artisan serve --host=localhost --port=8050
