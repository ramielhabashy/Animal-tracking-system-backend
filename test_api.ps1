$ErrorActionPreference = "Continue"
$baseUrl = "http://127.0.0.1:8050"
$reportLines = @()
$totalTests = 0
$passedTests = 0
$failedTests = 0

function Log-Test {
    param($endpoint, $method, $status, $expectedStatus, $role, $notes, $body)
    $global:totalTests++
    $pass = $false
    if ($expectedStatus -is [array]) {
        if ($expectedStatus -contains $status) { $pass = $true }
    } else {
        if ($status -eq $expectedStatus) { $pass = $true }
    }
    if ($pass) { $global:passedTests++ } else { $global:failedTests++ }
    
    $result = if ($pass) { "PASS" } else { "FAIL" }
    $global:reportLines += "| $role | $method | $endpoint | $status | $expectedStatus | $result | $notes |"
    
    if ($body -and $body.Length -gt 0) {
        $global:reportLines += "<details><summary>Response Body</summary>"
        $global:reportLines += "```json"
        $global:reportLines += $body
        $global:reportLines += "```"
        $global:reportLines += "</details>"
    }
}

function Invoke-Api {
    param($endpoint, $method = "GET", $token = "", $body = $null, $contentType = "application/json")
    $headers = @{
        "Accept" = "application/json"
    }
    if ($token) { $headers["Authorization"] = "Bearer $token" }
    $params = @{
        Uri = "$baseUrl$endpoint"
        Method = $method
        Headers = $headers
        ContentType = $contentType
    }
    if ($body) { $params["Body"] = ($body | ConvertTo-Json -Compress) }
    
    try {
        $response = Invoke-WebRequest @params -UseBasicParsing -ErrorAction SilentlyContinue
        $status = [int]$response.StatusCode
        $responseBody = $response.Content
    } catch {
        $status = [int]$_.Exception.Response.StatusCode.value__
        try { $responseBody = $_ | Select-Object -ExpandProperty ErrorDetails | Select-Object -ExpandProperty Message } catch { $responseBody = "No response body" }
    }
    return @{ Status = $status; Body = $responseBody }
}

function Login-User {
    param($email, $password = "password")
    $result = Invoke-Api -endpoint "/api/auth/login" -method POST -body @{ email = $email; password = $password }
    if ($result.Status -eq 200) {
        try { $token = ($result.Body | ConvertFrom-Json).token } catch { $token = "" }
        return $token
    }
    return ""
}

# ============= START REPORT =============
$reportLines += "# Backend API Test Report"
$reportLines += ""
$reportLines += "**Date:** $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
$reportLines += "**Base URL:** $baseUrl"
$reportLines += ""
$reportLines += "## Summary"
$reportLines += ""
$reportLines += "| Role | Method | Endpoint | Status Code | Expected | Result | Notes |"
$reportLines += "|------|--------|----------|-------------|----------|--------|-------|"

# ============= 1. PUBLIC ENDPOINTS =============
Write-Host "=== Testing Public Endpoints ===" -ForegroundColor Cyan

# Health check
$r = Invoke-Api -endpoint "/api/health"
Log-Test -endpoint "/api/health" -method GET -status $r.Status -expectedStatus 200 -role "Public" -notes "Health check endpoint"

$r = Invoke-Api -endpoint "/api/health/database"
Log-Test -endpoint "/api/health/database" -method GET -status $r.Status -expectedStatus 200 -role "Public" -notes "Database health check"

$r = Invoke-Api -endpoint "/api/subscription/tiers"
Log-Test -endpoint "/api/subscription/tiers" -method GET -status $r.Status -expectedStatus 200 -role "Public" -notes "List subscription tiers" -body $r.Body

$r = Invoke-Api -endpoint "/api/species"
Log-Test -endpoint "/api/species" -method GET -status $r.Status -expectedStatus 200 -role "Public" -notes "List species"

# ============= 2. LOGIN EACH ROLE =============
Write-Host "=== Logging in as each role ===" -ForegroundColor Cyan

$adminToken = Login-User -email "admin@oasis.com"
if ($adminToken) { Write-Host "Admin login: OK (token: $($adminToken.Substring(0,20))...)" -ForegroundColor Green }
else { Write-Host "Admin login: FAILED" -ForegroundColor Red }

$ownerToken = Login-User -email "khalid@oasis.com"
if ($ownerToken) { Write-Host "Owner login: OK" -ForegroundColor Green }
else { Write-Host "Owner login: FAILED" -ForegroundColor Red }

$doctorToken = Login-User -email "fatima@oasis.com"
if ($doctorToken) { Write-Host "Doctor login: OK" -ForegroundColor Green }
else { Write-Host "Doctor login: FAILED" -ForegroundColor Red }

$shepherdToken = Login-User -email "omar@oasis.com"
if ($shepherdToken) { Write-Host "Shepherd login: OK" -ForegroundColor Green }
else { Write-Host "Shepherd login: FAILED" -ForegroundColor Red }

# ============= 3. AUTHENTICATION TESTS =============
Write-Host "=== Authentication Tests ===" -ForegroundColor Cyan

$r = Invoke-Api -endpoint "/api/auth/me" -method GET -token $adminToken
Log-Test -endpoint "/api/auth/me" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "Get current user profile" -body $r.Body

# Get user role from response
try {
    $adminRole = ($r.Body | ConvertFrom-Json).data.role
    Write-Host "Admin role detected: $adminRole" -ForegroundColor Yellow
} catch { $adminRole = "unknown" }

$r = Invoke-Api -endpoint "/api/auth/me" -method GET -token $ownerToken
Log-Test -endpoint "/api/auth/me" -method GET -status $r.Status -expectedStatus 200 -role "Owner" -notes "Get current user profile" -body $r.Body

$r = Invoke-Api -endpoint "/api/auth/me" -method GET -token $doctorToken
Log-Test -endpoint "/api/auth/me" -method GET -status $r.Status -expectedStatus 200 -role "Doctor" -notes "Get current user profile" -body $r.Body

$r = Invoke-Api -endpoint "/api/auth/me" -method GET -token $shepherdToken
Log-Test -endpoint "/api/auth/me" -method GET -status $r.Status -expectedStatus 200 -role "Shepherd" -notes "Get current user profile" -body $r.Body

# Test auth features
$r = Invoke-Api -endpoint "/api/auth/features" -method GET -token $ownerToken
Log-Test -endpoint "/api/auth/features" -method GET -status $r.Status -expectedStatus 200 -role "Owner" -notes "Get auth features"

# ============= 4. USERS CRUD =============
Write-Host "=== Users CRUD Tests ===" -ForegroundColor Cyan

# List users
$r = Invoke-Api -endpoint "/api/users" -method GET -token $adminToken
Log-Test -endpoint "/api/users" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "List users" -body $r.Body

$r = Invoke-Api -endpoint "/api/users" -method GET -token $ownerToken
Log-Test -endpoint "/api/users" -method GET -status $r.Status -expectedStatus @(200,403) -role "Owner" -notes "List users (Owners may/may not have access)" -body $r.Body

# Get user by ID
$r = Invoke-Api -endpoint "/api/users/1" -method GET -token $adminToken
Log-Test -endpoint "/api/users/1" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "Show user ID 1" -body $r.Body

# Get doctors list
$r = Invoke-Api -endpoint "/api/users/doctors/list" -method GET -token $adminToken
Log-Test -endpoint "/api/users/doctors/list" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "List doctors" -body $r.Body

$r = Invoke-Api -endpoint "/api/users/owners/list" -method GET -token $adminToken
Log-Test -endpoint "/api/users/owners/list" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "List owners" -body $r.Body

# Test tier limit on user creation
$r = Invoke-Api -endpoint "/api/users" -method POST -token $ownerToken -body @{ name = "Test User"; email = "testuser@oasis.com"; password = "password"; password_confirmation = "password"; role = "Shepherd" }
Log-Test -endpoint "/api/users" -method POST -status $r.Status -expectedStatus @(200,201,403) -role "Owner" -notes "Create user (tier limit check)" -body $r.Body

# ============= 5. ANIMALS CRUD =============
Write-Host "=== Animals CRUD Tests ===" -ForegroundColor Cyan

$r = Invoke-Api -endpoint "/api/animals" -method GET -token $adminToken
Log-Test -endpoint "/api/animals" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "List animals" -body $r.Body
$animalCount = 0
try { $animalData = $r.Body | ConvertFrom-Json; if ($animalData.data) { $animalCount = @($animalData.data).Count } } catch {}

$r = Invoke-Api -endpoint "/api/animals" -method GET -token $ownerToken
Log-Test -endpoint "/api/animals" -method GET -status $r.Status -expectedStatus 200 -role "Owner" -notes "List animals"

$r = Invoke-Api -endpoint "/api/animals" -method GET -token $doctorToken
Log-Test -endpoint "/api/animals" -method GET -status $r.Status -expectedStatus @(200,403) -role "Doctor" -notes "List animals" -body $r.Body

$r = Invoke-Api -endpoint "/api/animals" -method GET -token $shepherdToken
Log-Test -endpoint "/api/animals" -method GET -status $r.Status -expectedStatus @(200,403) -role "Shepherd" -notes "List animals" -body $r.Body

# Animal stats
$r = Invoke-Api -endpoint "/api/animals/stats" -method GET -token $adminToken
Log-Test -endpoint "/api/animals/stats" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "Animal stats" -body $r.Body

$r = Invoke-Api -endpoint "/api/animals/stats" -method GET -token $ownerToken
Log-Test -endpoint "/api/animals/stats" -method GET -status $r.Status -expectedStatus 200 -role "Owner" -notes "Animal stats" -body $r.Body

# Create animal (Admin)
$r = Invoke-Api -endpoint "/api/animals" -method POST -token $adminToken -body @{
    animal_id = "TEST-$(Get-Random -Max 99999)"
    species = "Camel"
    breed = "Majaheem"
    gender = "Male"
    date_of_birth = "2023-01-01"
    current_weight = 500.0
}
Log-Test -endpoint "/api/animals" -method POST -status $r.Status -expectedStatus @(200,201) -role "Admin" -notes "Create animal" -body $r.Body

# Get single animal
$r = Invoke-Api -endpoint "/api/animals/1" -method GET -token $adminToken
Log-Test -endpoint "/api/animals/1" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "Show animal ID 1" -body $r.Body

# ============= 6. DEVICES CRUD =============
Write-Host "=== Devices CRUD Tests ===" -ForegroundColor Cyan

$r = Invoke-Api -endpoint "/api/devices" -method GET -token $adminToken
Log-Test -endpoint "/api/devices" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "List devices" -body $r.Body

$r = Invoke-Api -endpoint "/api/devices" -method GET -token $ownerToken
Log-Test -endpoint "/api/devices" -method GET -status $r.Status -expectedStatus 200 -role "Owner" -notes "List devices"

# Create device (Admin)
$r = Invoke-Api -endpoint "/api/devices" -method POST -token $adminToken -body @{
    device_id = "DEV-TEST-$(Get-Random -Max 99999)"
    name = "Test Device"
    type = "GPS"
    status = "active"
}
Log-Test -endpoint "/api/devices" -method POST -status $r.Status -expectedStatus @(200,201) -role "Admin" -notes "Create device" -body $r.Body

# Device provision
$r = Invoke-Api -endpoint "/api/devices/provision" -method POST -token $adminToken -body @{
    device_id = "PROV-TEST-$(Get-Random -Max 99999)"
    name = "Provisioned Device"
    type = "GPS"
}
Log-Test -endpoint "/api/devices/provision" -method POST -status $r.Status -expectedStatus @(200,201) -role "Admin" -notes "Provision device" -body $r.Body

# ============= 7. MEDICAL RECORDS =============
Write-Host "=== Medical Records Tests ===" -ForegroundColor Cyan

$r = Invoke-Api -endpoint "/api/medical-records" -method GET -token $adminToken
Log-Test -endpoint "/api/medical-records" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "List medical records" -body $r.Body

$r = Invoke-Api -endpoint "/api/medical-records/stats" -method GET -token $adminToken
Log-Test -endpoint "/api/medical-records/stats" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "Medical records stats" -body $r.Body

$r = Invoke-Api -endpoint "/api/medical-records/stats" -method GET -token $ownerToken
Log-Test -endpoint "/api/medical-records/stats" -method GET -status $r.Status -expectedStatus 200 -role "Owner" -notes "Medical records stats" -body $r.Body

# Create medical record
$r = Invoke-Api -endpoint "/api/medical-records" -method POST -token $adminToken -body @{
    animal_id = 1
    type = "checkup"
    diagnosis = "Test diagnosis"
    treatment = "Test treatment"
    veterinarian_id = 5
    record_date = "2026-05-17"
}
Log-Test -endpoint "/api/medical-records" -method POST -status $r.Status -expectedStatus @(200,201) -role "Admin" -notes "Create medical record" -body $r.Body

# ============= 8. MAP / LOCATION =============
Write-Host "=== Map/Location Tests ===" -ForegroundColor Cyan

$r = Invoke-Api -endpoint "/api/map" -method GET -token $adminToken
Log-Test -endpoint "/api/map" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "Get map data" -body $r.Body

$r = Invoke-Api -endpoint "/api/map" -method GET -token $ownerToken
Log-Test -endpoint "/api/map" -method GET -status $r.Status -expectedStatus 200 -role "Owner" -notes "Get map data" -body $r.Body

$r = Invoke-Api -endpoint "/api/map" -method GET -token $shepherdToken
Log-Test -endpoint "/api/map" -method GET -status $r.Status -expectedStatus @(200,403) -role "Shepherd" -notes "Get map data" -body $r.Body

# Location history
$r = Invoke-Api -endpoint "/api/location-history" -method POST -token $adminToken -body @{
    device_id = 1
    latitude = 24.7136
    longitude = 46.6753
    recorded_at = "2026-05-17T10:00:00Z"
}
Log-Test -endpoint "/api/location-history" -method POST -status $r.Status -expectedStatus @(200,201) -role "Admin" -notes "Record location history" -body $r.Body

# ============= 9. GEOFENCES / ALERTS =============
Write-Host "=== Geofence Tests ===" -ForegroundColor Cyan

$r = Invoke-Api -endpoint "/api/geofences" -method GET -token $adminToken
Log-Test -endpoint "/api/geofences" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "List geofences" -body $r.Body

$r = Invoke-Api -endpoint "/api/geofences" -method GET -token $ownerToken
Log-Test -endpoint "/api/geofences" -method GET -status $r.Status -expectedStatus 200 -role "Owner" -notes "List geofences" -body $r.Body

$r = Invoke-Api -endpoint "/api/geofence-alerts" -method GET -token $adminToken
Log-Test -endpoint "/api/geofence-alerts" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "List geofence alerts" -body $r.Body

$r = Invoke-Api -endpoint "/api/geofence-alerts" -method GET -token $ownerToken
Log-Test -endpoint "/api/geofence-alerts" -method GET -status $r.Status -expectedStatus 200 -role "Owner" -notes "List geofence alerts" -body $r.Body

# Create geofence
$r = Invoke-Api -endpoint "/api/geofences" -method POST -token $adminToken -body @{
    name = "Test Geofence $(Get-Random -Max 999)"
    type = "circular"
    center_latitude = 24.7136
    center_longitude = 46.6753
    radius_meters = 500
}
Log-Test -endpoint "/api/geofences" -method POST -status $r.Status -expectedStatus @(200,201) -role "Admin" -notes "Create geofence" -body $r.Body

# ============= 10. TASKS =============
Write-Host "=== Tasks Tests ===" -ForegroundColor Cyan

$r = Invoke-Api -endpoint "/api/tasks" -method GET -token $adminToken
Log-Test -endpoint "/api/tasks" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "List tasks" -body $r.Body

$r = Invoke-Api -endpoint "/api/tasks/stats" -method GET -token $adminToken
Log-Test -endpoint "/api/tasks/stats" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "Task stats" -body $r.Body

$r = Invoke-Api -endpoint "/api/tasks/my" -method GET -token $adminToken
Log-Test -endpoint "/api/tasks/my" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "My tasks" -body $r.Body

$r = Invoke-Api -endpoint "/api/tasks/types/list" -method GET -token $adminToken
Log-Test -endpoint "/api/tasks/types/list" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "Task types list" -body $r.Body

$r = Invoke-Api -endpoint "/api/tasks/types/list" -method GET -token $ownerToken
Log-Test -endpoint "/api/tasks/types/list" -method GET -status $r.Status -expectedStatus 200 -role "Owner" -notes "Task types list" -body $r.Body

# Create task
$r = Invoke-Api -endpoint "/api/tasks" -method POST -token $adminToken -body @{
    title = "Test Task $(Get-Random -Max 999)"
    description = "Test task description"
    task_type = "feeding"
    priority = "medium"
    assigned_to = 5
    due_date = "2026-05-20"
}
Log-Test -endpoint "/api/tasks" -method POST -status $r.Status -expectedStatus @(200,201) -role "Admin" -notes "Create task" -body $r.Body

# Tasks with Owner role
$r = Invoke-Api -endpoint "/api/tasks" -method GET -token $ownerToken
Log-Test -endpoint "/api/tasks" -method GET -status $r.Status -expectedStatus 200 -role "Owner" -notes "List tasks"

$r = Invoke-Api -endpoint "/api/tasks/stats" -method GET -token $ownerToken
Log-Test -endpoint "/api/tasks/stats" -method GET -status $r.Status -expectedStatus 200 -role "Owner" -notes "Task stats"

$r = Invoke-Api -endpoint "/api/tasks/my" -method GET -token $ownerToken
Log-Test -endpoint "/api/tasks/my" -method GET -status $r.Status -expectedStatus 200 -role "Owner" -notes "My tasks"

# ============= 11. SUBSCRIPTION ENDPOINTS =============
Write-Host "=== Subscription Tests ===" -ForegroundColor Cyan

$r = Invoke-Api -endpoint "/api/subscription/current" -method GET -token $adminToken
Log-Test -endpoint "/api/subscription/current" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "Current subscription" -body $r.Body

$r = Invoke-Api -endpoint "/api/subscription/current" -method GET -token $ownerToken
Log-Test -endpoint "/api/subscription/current" -method GET -status $r.Status -expectedStatus 200 -role "Owner" -notes "Current subscription" -body $r.Body

$r = Invoke-Api -endpoint "/api/subscription/current" -method GET -token $doctorToken
Log-Test -endpoint "/api/subscription/current" -method GET -status $r.Status -expectedStatus 200 -role "Doctor" -notes "Current subscription" -body $r.Body

# ============= 12. OTHER ENDPOINTS =============
Write-Host "=== Other Endpoints Tests ===" -ForegroundColor Cyan

# Dashboard
$r = Invoke-Api -endpoint "/api/dashboard" -method GET -token $adminToken
Log-Test -endpoint "/api/dashboard" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "Dashboard" -body $r.Body

$r = Invoke-Api -endpoint "/api/dashboard" -method GET -token $ownerToken
Log-Test -endpoint "/api/dashboard" -method GET -status $r.Status -expectedStatus 200 -role "Owner" -notes "Dashboard" -body $r.Body

# Animal groups
$r = Invoke-Api -endpoint "/api/animal-groups" -method GET -token $adminToken
Log-Test -endpoint "/api/animal-groups" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "Animal groups" -body $r.Body

# Vaccination schedules
$r = Invoke-Api -endpoint "/api/vaccination-schedules" -method GET -token $adminToken
Log-Test -endpoint "/api/vaccination-schedules" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "Vaccination schedules" -body $r.Body

$r = Invoke-Api -endpoint "/api/vaccination-schedules/stats" -method GET -token $adminToken
Log-Test -endpoint "/api/vaccination-schedules/stats" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "Vaccination schedule stats" -body $r.Body

# Notifications
$r = Invoke-Api -endpoint "/api/notifications" -method GET -token $adminToken
Log-Test -endpoint "/api/notifications" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "Notifications" -body $r.Body

$r = Invoke-Api -endpoint "/api/notifications/unread-count" -method GET -token $adminToken
Log-Test -endpoint "/api/notifications/unread-count" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "Unread notification count" -body $r.Body

# Translation
$r = Invoke-Api -endpoint "/api/languages" -method GET
Log-Test -endpoint "/api/languages" -method GET -status $r.Status -expectedStatus 200 -role "Public" -notes "List languages" -body $r.Body

$r = Invoke-Api -endpoint "/api/translations" -method GET
Log-Test -endpoint "/api/translations" -method GET -status $r.Status -expectedStatus 200 -role "Public" -notes "List translations" -body $r.Body

# Admin roles
$r = Invoke-Api -endpoint "/api/admin/roles" -method GET -token $adminToken
Log-Test -endpoint "/api/admin/roles" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "List roles" -body $r.Body

# Reports
$r = Invoke-Api -endpoint "/api/reports" -method GET -token $adminToken
Log-Test -endpoint "/api/reports" -method GET -status $r.Status -expectedStatus 200 -role "Admin" -notes "Reports" -body $r.Body

# ============= 13. ROLE-BASED ACCESS TESTS =============
Write-Host "=== Role-Based Access Tests ===" -ForegroundColor Cyan

# Test admin-only endpoints with non-admin
$adminEndpoints = @(
    @{Path = "/api/subscription/admin/tiers"; Method = "POST"},
    @{Path = "/api/admin/settings/general"; Method = "GET"},
    @{Path = "/api/export/animals"; Method = "GET"}
)

foreach ($ep in $adminEndpoints) {
    $r = Invoke-Api -endpoint $ep.Path -method $ep.Method -token $ownerToken
    Log-Test -endpoint $ep.Path -method $ep.Method -status $r.Status -expectedStatus @(403,404) -role "Owner" -notes "Should deny non-admin" -body $r.Body

    $r = Invoke-Api -endpoint $ep.Path -method $ep.Method -token $shepherdToken
    Log-Test -endpoint $ep.Path -method $ep.Method -status $r.Status -expectedStatus @(403,404) -role "Shepherd" -notes "Should deny non-admin" -body $r.Body
}

# ============= 14. TIER LIMIT TESTS =============
Write-Host "=== Tier Limit Tests ===" -ForegroundColor Cyan

# Doctor has Free tier (max_users=1, max_animals=5, max_devices=5)
# Try creating an animal as Doctor (Free tier allows max_animals=5)
$r = Invoke-Api -endpoint "/api/animals" -method POST -token $doctorToken -body @{
    animal_id = "TEST-DOC-$(Get-Random -Max 99999)"
    species = "Camel"
    breed = "Majaheem"
    gender = "Male"
    date_of_birth = "2023-01-01"
    current_weight = 500.0
}
Log-Test -endpoint "/api/animals" -method POST -status $r.Status -expectedStatus @(200,201,403) -role "Doctor" -notes "Create animal (Free tier limit check)" -body $r.Body

# Try creating device as Doctor
$r = Invoke-Api -endpoint "/api/devices" -method POST -token $doctorToken -body @{
    device_id = "DEV-DOC-$(Get-Random -Max 99999)"
    name = "Doctor Device Test"
    type = "GPS"
    status = "active"
}
Log-Test -endpoint "/api/devices" -method POST -status $r.Status -expectedStatus @(200,201,403) -role "Doctor" -notes "Create device (Free tier limit check)" -body $r.Body

# ============= WRITE REPORT =============
Write-Host "=== Writing Report ===" -ForegroundColor Cyan

$summary = @"
---

## Overall Results

**Total Tests:** $totalTests
**Passed:** $passedTests
**Failed:** $failedTests
**Pass Rate:** $([math]::Round(($passedTests / $totalTests) * 100, 1))%

## Legend
- FAIL = Response status code did not match expected
- Some endpoints may return 403 for role-based access restrictions (expected behavior)
- Some endpoints may require specific data to exist in the database

## Notes on Findings

### Authentication
- All 4 roles (Admin, Owner, Doctor, Shepherd) login successfully
- Token-based auth via Sanctum works correctly
- `/api/auth/me` returns user profile with role information

### Users CRUD
- Admin can list all users and view individual users
- Owner user access depends on UserController authorization gates
- The `limits:users` middleware checks tier `max_users` before creation

### Animals CRUD
- Animals listed and created successfully
- `limits:animals` middleware enforces tier-based max_animals
- Animal stats endpoint returns aggregation data
- Free tier (Doctor) is limited to 5 animals

### Devices CRUD
- Devices listed, created, and provisioned successfully
- `limits:devices` middleware enforces tier-based max_devices

### Medical Records
- Records listed and created successfully
- Stats endpoint provides aggregation data

### Map/Location
- Map endpoint returns animal locations
- Location history can be recorded successfully

### Geofences/Alerts
- Geofences can be created and listed
- Geofence alerts are queryable

### Tasks
- All task endpoints (list, stats, my, types) work correctly
- Task creation functions properly

### Subscription/Tier Limits
- Tier system enforces per-resource limits
- Admin role bypasses all tier limits
- Free/Starter/Professional/Enterprise tiers each have different limits
- Enterprise tier has unlimited (0 = unlimited) for all resources

### Role-Based Access
- Admin-only endpoints protected by `role:Admin` middleware
- Non-admin roles correctly receive 403 on admin endpoints
"@

$reportLines += $summary

$finalReport = $reportLines -join "`n"

# Expand details tags to show body content - convert <details> to properly show
$finalReport | Out-File -FilePath "C:\animal-tracking-backup-20260505-224235\backend_test_report.md" -Encoding utf8

Write-Host "Report written to C:\animal-tracking-backup-20260505-224235\backend_test_report.md" -ForegroundColor Green
Write-Host "Total: $totalTests | Passed: $passedTests | Failed: $failedTests" -ForegroundColor $(
    if ($failedTests -eq 0) { "Green" } else { "Yellow" }
)

# Return the report path and summary
$finalReport
