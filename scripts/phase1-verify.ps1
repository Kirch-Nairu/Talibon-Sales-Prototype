param(
    [switch]$SkipMigration
)

$ErrorActionPreference = 'Stop'

function Assert-NativeSuccess {
    param([Parameter(Mandatory = $true)][string]$Label)

    if ($LASTEXITCODE -ne 0) {
        throw "$Label failed with exit code $LASTEXITCODE."
    }
}

Write-Host '=== Talibon Phase 1 Verification ===' -ForegroundColor Cyan
Write-Host "Working directory: $(Get-Location)"

$branchOutput = git branch --show-current
Assert-NativeSuccess 'git branch --show-current'
$branch = ($branchOutput | Out-String).Trim()
Write-Host "Branch: $branch"

$headOutput = git rev-parse HEAD
Assert-NativeSuccess 'git rev-parse HEAD'
$head = ($headOutput | Out-String).Trim()
Write-Host "HEAD: $head"

if ($branch -ne 'KIRCH-PHASE1-INTERNAL-OPS-HRIS') {
    throw 'Verification must be run from KIRCH-PHASE1-INTERNAL-OPS-HRIS.'
}

Write-Host "`n=== Git Status ===" -ForegroundColor Cyan
git status --short
Assert-NativeSuccess 'git status --short'

if (-not $SkipMigration) {
    Write-Host "`n=== Additive Migrations ===" -ForegroundColor Cyan
    Write-Host 'This script intentionally never runs migrate:fresh or any destructive reset.' -ForegroundColor Yellow
    php artisan migrate
    Assert-NativeSuccess 'php artisan migrate'
}

Write-Host "`n=== TypeScript ===" -ForegroundColor Cyan
npm run types:check
Assert-NativeSuccess 'npm run types:check'

Write-Host "`n=== Production Build ===" -ForegroundColor Cyan
npm run build
Assert-NativeSuccess 'npm run build'

Write-Host "`n=== Feature Suite ===" -ForegroundColor Cyan
php artisan test --testsuite=Feature
Assert-NativeSuccess 'php artisan test --testsuite=Feature'

Write-Host "`n=== Phase 1 Verification Completed ===" -ForegroundColor Green
Write-Host "Record this exact HEAD and the command output in docs/ENGINEERING_LOG.md before assigning a release-green label."
