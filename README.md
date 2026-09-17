# One Talibon V1 — Expedited Showcase Deployment Runbook

> **For AJ / deployment operator**
>
> This branch exists to make the expedited One Talibon showcase deployable without touching the active Forge UI/UX correction work.
>
> **Deployment branch:** `KIRCH-TALIBON-V1-EXPEDITED-DEPLOYMENT`
>
> **Accepted application source baseline:** `0913a37f96affd2c2a681697bdf6fdb6c381a99e`
>
> This deployment branch was created directly from that accepted Showcase SHA. The only intended deployment-wrapper change is this README/runbook. The active revision branches remain sandboxed and must not be substituted into this deployment unless Kirch explicitly authorizes a later promotion.

---

## 1. What you are deploying

This is the expedited review build of **One Talibon V1**, a municipal digital operations workspace for the Local Government Unit of Talibon, Bohol.

Current stack:

- Laravel 13
- PHP 8.4 recommended for the locked dependency set
- Inertia
- React 19
- TypeScript
- Vite
- Tailwind CSS
- PostgreSQL
- Cloudflare Tunnel for temporary public review access

The intended expedited deployment path is:

```text
GitHub deployment branch
        ↓
Windows workstation
        ↓
Laravel application on 127.0.0.1:8000
        ↓
Cloudflare Quick Tunnel
        ↓
https://<random>.trycloudflare.com
        ↓
Review link for Sir Gerry / authorized reviewers
```

This is a **review/demo deployment**, not a production deployment.

Do not deploy active correction branches such as `KIRCH-TALIBON-UIUX-W*` or `KIRCH-TALIBON-V1-UIUX-CORRECTION` for this expedited link.

---

# 2. Non-negotiable safety rules

Before doing anything, follow these rules:

1. **Use a separate PostgreSQL database for this showcase.**
2. **Never run `migrate:fresh` against an existing production or engineering database.**
3. **Never commit `.env`.**
4. **Never put the private demo password in Git, screenshots, chat, or this README.**
5. **Keep `APP_DEBUG=false` for the public Cloudflare link.**
6. **Bind Laravel to `127.0.0.1`, not `0.0.0.0`, unless there is a specific reason to expose it to the LAN.**
7. **Do not run `composer update` to work around dependency problems.** Use the locked dependency set.
8. **Do not modify Forge branches or merge current UI/UX revision work into this deployment.**
9. **Cloudflare Quick Tunnel is temporary.** Its public hostname changes when the tunnel process is recreated.
10. Treat the generated `trycloudflare.com` URL as public while it is running.

---

# 3. Recommended Windows deployment location

A clean deployment directory is preferred:

```text
C:\Talibon-Sales-Prototype
```

If that directory already contains the correct repository and has no uncommitted work, it may be reused.

If there is any uncertainty, use a fresh directory instead:

```text
C:\Talibon-Expedited-Deployment
```

Do not delete an old working deployment just to reuse its folder. Rename or back it up first.

Example:

```powershell
$old = 'C:\Talibon-Prototype'
$backup = "C:\Talibon-Prototype-BACKUP-$(Get-Date -Format yyyyMMdd-HHmmss)"

if (Test-Path $old) {
    Rename-Item $old $backup
    Write-Host "Old deployment preserved at $backup"
}
```

---

# 4. Dependency checks — run these first

Open **PowerShell**.

Administrator privileges are not required for the application itself, but may be required for installing software.

Run:

```powershell
Write-Host '=== GIT ==='
git --version

Write-Host '=== PHP ==='
php -v

Write-Host '=== COMPOSER ==='
composer --version

Write-Host '=== NODE ==='
node -v

Write-Host '=== NPM ==='
npm.cmd -v

Write-Host '=== POSTGRESQL SERVICES ==='
Get-Service -ErrorAction SilentlyContinue |
    Where-Object { $_.Name -match 'postgres' -or $_.DisplayName -match 'postgres' } |
    Select-Object Name, DisplayName, Status

Write-Host '=== CLOUDFLARED ==='
Get-Command cloudflared -ErrorAction SilentlyContinue |
    Format-List Source, Version
```

## Expected environment

### Git

Any current Git version capable of cloning GitHub repositories is sufficient.

### PHP

Use **PHP 8.4.1 or newer** for this deployment.

The repository's Composer constraint historically permits PHP 8.3+, but the current lock resolves Symfony 8.1 packages that require PHP 8.4.1+. If Composer reports that Symfony packages require PHP 8.4.1, **upgrade PHP**. Do not run `composer update` as a workaround.

Known working deployment-machine example:

```text
PHP 8.4.24
```

### Node

The repository `.nvmrc` declares:

```text
22.16.0
```

Node 22.x is the expected major version.

A newer Node 22 patch release is acceptable if the build succeeds.

### npm on Windows PowerShell

If PowerShell says:

```text
npm.ps1 cannot be loaded because running scripts is disabled
```

**do not weaken the machine execution policy just for this deployment.**

Use:

```powershell
npm.cmd -v
npm.cmd ci
npm.cmd run build
```

instead of:

```powershell
npm -v
npm ci
npm run build
```

### Composer

Composer 2.x is expected.

### PostgreSQL

PostgreSQL must be running locally or otherwise reachable from the deployment machine.

Recommended local connection for this demo:

```text
Host: 127.0.0.1
Port: 5432
Database: talibon_sales_showcase
```

### cloudflared

`cloudflared` is only required when the local Laravel deployment is already healthy and you are ready to expose it publicly.

Do not troubleshoot Cloudflare before localhost works.

---

# 5. Acquire the deployment branch

## Option A — fresh clone — recommended

```powershell
Set-Location C:\

git clone `
    --branch KIRCH-TALIBON-V1-EXPEDITED-DEPLOYMENT `
    --single-branch `
    https://github.com/Kirch-Nairu/Talibon-Sales-Prototype.git `
    Talibon-Expedited-Deployment

Set-Location C:\Talibon-Expedited-Deployment
```

## Option B — existing `C:\Talibon-Sales-Prototype` clone

First inspect it:

```powershell
Set-Location C:\Talibon-Sales-Prototype

git status --short
git remote -v
git branch --show-current
```

If `git status --short` prints anything you do not recognize, **stop and preserve the work before switching branches**.

If clean:

```powershell
git fetch origin

git switch KIRCH-TALIBON-V1-EXPEDITED-DEPLOYMENT

git pull --ff-only origin KIRCH-TALIBON-V1-EXPEDITED-DEPLOYMENT
```

Do not use `git reset --hard` as a convenience step.

---

# 6. Verify that the deployment branch is actually based on the accepted Showcase

Run:

```powershell
git status --short
git branch --show-current
git log --oneline --decorate -5
```

The accepted application baseline must be present in history:

```text
0913a37f96affd2c2a681697bdf6fdb6c381a99e
```

Verify ancestry:

```powershell
git merge-base --is-ancestor `
    0913a37f96affd2c2a681697bdf6fdb6c381a99e `
    HEAD

if ($LASTEXITCODE -ne 0) {
    throw 'Deployment branch is not descended from the accepted Showcase SHA.'
}
```

For this deployment wrapper, application changes after the accepted baseline should be limited to deployment documentation unless Kirch explicitly says otherwise.

Inspect the difference:

```powershell
git diff --name-status `
    0913a37f96affd2c2a681697bdf6fdb6c381a99e..HEAD
```

If application source files unexpectedly appear here, stop and confirm the branch before deployment.

---

# 7. Install PHP dependencies

From the repository root:

```powershell
composer install `
    --no-interaction `
    --prefer-dist `
    --optimize-autoloader
```

A normal deployment should use `composer install`, which honors `composer.lock`.

Do **not** use:

```powershell
composer update
```

unless Kirch explicitly authorizes a dependency update.

## If Composer reports a PHP compatibility error

Confirm:

```powershell
php -v
```

If PHP is below 8.4.1, upgrade PHP and rerun `composer install`.

## If Composer cannot find required PHP extensions

Inspect enabled extensions:

```powershell
php -m
```

For PostgreSQL support, ensure these are available:

```text
pdo_pgsql
pgsql
```

Common Laravel requirements must also be present, including OpenSSL, Mbstring, Tokenizer, XML, Ctype, JSON support, and Fileinfo as required by the installed dependency set.

---

# 8. Install frontend dependencies and prove the frontend builds

Use `npm.cmd` on this machine to avoid PowerShell's `npm.ps1` execution-policy block.

```powershell
npm.cmd ci --no-audit --no-fund
```

Then type-check:

```powershell
npm.cmd run types:check
```

Then build the production frontend:

```powershell
npm.cmd run build
```

All three should succeed before public tunneling.

Do not replace `npm ci` with `npm install` unless there is a deliberate dependency-management reason.

---

# 9. Create the Laravel environment file

If `.env` does not exist:

```powershell
Copy-Item .env.example .env
```

Open it:

```powershell
notepad .env
```

Use a configuration similar to this for the first **local-only** boot:

```dotenv
APP_NAME="One Talibon"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://127.0.0.1:8000

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_PH

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=talibon_sales_showcase
DB_USERNAME=postgres
DB_PASSWORD=PUT_LOCAL_POSTGRES_PASSWORD_HERE
DB_TIMEZONE=Asia/Manila

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax

CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local

DOCUMENT_MAX_UPLOAD_MB=15
DOCUMENT_MAX_FILES_PER_OPERATION=5

SHOWCASE_MODE=true

PROTOTYPE_DEMO_PASSWORD=PUT_A_PRIVATE_16_PLUS_CHARACTER_PASSWORD_HERE

VITE_APP_NAME="${APP_NAME}"
```

## Important demo-password rule

`PROTOTYPE_DEMO_PASSWORD` is required when demo seeding is executed with `APP_ENV=production`.

It must:

- be private;
- contain at least 16 characters;
- not be the blocked historical shared prototype password;
- never be committed to Git.

The seeded showcase login accounts all use this configured private password.

Generate the Laravel application key:

```powershell
php artisan key:generate
```

Confirm that `APP_KEY=` now contains a generated key:

```powershell
Select-String -Path .env -Pattern '^APP_KEY='
```

---

# 10. PostgreSQL database setup

## 10.1 Confirm PostgreSQL is running

```powershell
Get-Service -ErrorAction SilentlyContinue |
    Where-Object { $_.Name -match 'postgres' -or $_.DisplayName -match 'postgres' } |
    Select-Object Name, DisplayName, Status
```

If the service is stopped, start the correct PostgreSQL service from Services or PowerShell.

Example:

```powershell
Start-Service <actual-postgresql-service-name>
```

Do not guess the service name.

## 10.2 Find `psql.exe` if it is not on PATH

First:

```powershell
psql --version
```

If PowerShell cannot find it:

```powershell
Get-ChildItem 'C:\Program Files\PostgreSQL' `
    -Filter psql.exe `
    -Recurse `
    -ErrorAction SilentlyContinue |
    Select-Object FullName
```

A typical result looks like:

```text
C:\Program Files\PostgreSQL\17\bin\psql.exe
```

You can assign it:

```powershell
$psql = 'C:\Program Files\PostgreSQL\17\bin\psql.exe'
```

Replace the version/path with the actual result on the machine.

## 10.3 Create a dedicated showcase database

Recommended database:

```text
talibon_sales_showcase
```

With `psql` on PATH:

```powershell
psql -h 127.0.0.1 -U postgres -c "CREATE DATABASE talibon_sales_showcase;"
```

Or with a full executable path:

```powershell
& $psql -h 127.0.0.1 -U postgres -c "CREATE DATABASE talibon_sales_showcase;"
```

If PostgreSQL says the database already exists, decide whether that database is the dedicated showcase database you intend to reuse.

Do not point this process at an unrelated Talibon engineering/production database.

## 10.4 Test Laravel's DB configuration

Clear stale configuration first:

```powershell
php artisan optimize:clear
```

Then inspect migration status:

```powershell
php artisan migrate:status
```

If this reports a connection error, fix the DB settings before proceeding.

Typical causes:

- wrong `DB_PASSWORD`;
- PostgreSQL service not running;
- wrong port;
- wrong database name;
- `pdo_pgsql` not enabled;
- PostgreSQL authentication policy rejecting the connection.

---

# 11. Seed the dedicated Showcase database

**STOP HERE AND VERIFY `DB_DATABASE` ONE MORE TIME.**

```powershell
Select-String -Path .env -Pattern '^DB_DATABASE='
```

It should point to the dedicated demo database, for example:

```text
DB_DATABASE=talibon_sales_showcase
```

Only after that check, initialize it:

```powershell
php artisan migrate:fresh --seed --force
```

This is intentionally destructive to the database selected by `.env`.

That is why the dedicated showcase DB is mandatory.

The main demo seeding path loads municipal structure and representative prototype data including:

- municipal departments/structure;
- workforce/demo employees;
- workflow/demo work;
- memoranda;
- legislative demo content;
- HRIS demo content;
- operations monitoring data;
- payroll demo content;
- development/demo records.

The workforce seed creates approximately 350 representative employees and the following primary showcase identities:

```text
admin@talibon.demo
mayor@talibon.demo
engineering@talibon.demo
budget@talibon.demo
hr@talibon.demo
legislative@talibon.demo
employee@talibon.demo
```

All primary identities use the private `PROTOTYPE_DEMO_PASSWORD` configured in `.env`.

Do not place that password in this README or commit it anywhere.

---

# 12. Cache Laravel configuration for the local smoke test

Run:

```powershell
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If `route:cache` fails because of an application route issue, record the exact output instead of hiding it.

---

# 13. Start the local Laravel origin

From the repository root:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Keep this PowerShell window open.

Expected output is similar to:

```text
Server running on [http://127.0.0.1:8000]
```

## Confirm the listener from another PowerShell window

```powershell
Get-NetTCPConnection -State Listen -LocalPort 8000 -ErrorAction SilentlyContinue |
    Select-Object LocalAddress, LocalPort, OwningProcess
```

Or:

```powershell
netstat -ano | findstr :8000
```

## HTTP smoke test

```powershell
curl.exe -I http://127.0.0.1:8000
```

A redirect is acceptable depending on the root/gateway behavior.

Connection refused is not acceptable.

---

# 14. Local browser smoke test — do this before Cloudflare

Open:

```text
http://127.0.0.1:8000
```

At minimum verify:

1. the application loads;
2. CSS and JavaScript load correctly;
3. the Showcase/persona gateway is present where expected;
4. a seeded account can authenticate;
5. the Home/Dashboard renders;
6. navigation works;
7. no obvious 500 error appears;
8. developer console does not show a fatal asset/application error;
9. expected demo data exists;
10. logout works.

Recommended login smoke-test accounts:

```text
admin@talibon.demo
engineering@talibon.demo
employee@talibon.demo
```

Use the private `PROTOTYPE_DEMO_PASSWORD` from `.env`.

Do not proceed to a public tunnel if localhost is broken.

---

# 15. Install or locate `cloudflared`

First check:

```powershell
Get-Command cloudflared -ErrorAction SilentlyContinue |
    Format-List Source, Version
```

Then:

```powershell
cloudflared --version
```

If it is already available, continue to the next section.

## If `cloudflared` is not installed

Use Cloudflare's official Cloudflare Tunnel download for **Windows 64-bit**.

A clean manual location is:

```text
C:\Cloudflared\bin\cloudflared.exe
```

After downloading:

```powershell
New-Item -ItemType Directory -Force C:\Cloudflared\bin | Out-Null
```

Place/rename the downloaded executable as:

```text
C:\Cloudflared\bin\cloudflared.exe
```

Verify it:

```powershell
& 'C:\Cloudflared\bin\cloudflared.exe' --version
```

For this expedited deployment, a Cloudflare account, named tunnel, service installation, DNS record, and `config.yml` are **not required** if we use a Quick Tunnel.

Cloudflare Quick Tunnels are specifically intended for temporary development/testing/demo exposure and generate a random `*.trycloudflare.com` hostname.

---

# 16. Start the Cloudflare Quick Tunnel

Keep the Laravel server running on:

```text
http://127.0.0.1:8000
```

Open a **new PowerShell window**.

If `cloudflared` is on PATH:

```powershell
cloudflared tunnel --url http://127.0.0.1:8000
```

If using the manual executable path:

```powershell
& 'C:\Cloudflared\bin\cloudflared.exe' `
    tunnel `
    --url http://127.0.0.1:8000
```

Do not close this tunnel window.

`cloudflared` should print a public URL similar to:

```text
https://random-words.trycloudflare.com
```

Copy that URL.

That is the public review URL for this tunnel session.

Important:

- Quick Tunnel hostname is random.
- Closing `cloudflared` kills the public link.
- Starting another Quick Tunnel usually generates a different URL.
- No local `config.yml` is expected for this mode.
- No inbound router/firewall port forwarding is required because `cloudflared` establishes the connection outward to Cloudflare.

---

# 17. Switch Laravel from local HTTP settings to the Cloudflare HTTPS URL

Once Cloudflare gives you the public URL, update `.env`.

Example:

```dotenv
APP_URL=https://random-words.trycloudflare.com
SESSION_SECURE_COOKIE=true
```

Keep:

```dotenv
APP_ENV=production
APP_DEBUG=false
SHOWCASE_MODE=true
```

Then in the application PowerShell window or a third shell:

```powershell
Set-Location C:\Talibon-Expedited-Deployment

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If the project is actually located at `C:\Talibon-Sales-Prototype`, use that directory instead.

For maximum certainty, restart the Laravel development server after changing the public URL:

1. Press `Ctrl+C` in the Laravel server window.
2. Start it again:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

The Cloudflare process may remain running while the origin restarts; it will reconnect to the origin once Laravel is listening again.

---

# 18. Public smoke test

Open the generated HTTPS URL in a browser.

Do not send the link to Sir Gerry until these checks pass:

```text
[ ] HTTPS URL opens
[ ] No Cloudflare 502
[ ] No Laravel 500
[ ] No debug exception page
[ ] CSS loads
[ ] JavaScript loads
[ ] Showcase gateway loads
[ ] Login works
[ ] Dashboard loads
[ ] Navigation works
[ ] Demo data is visible
[ ] Logout works
[ ] Refreshing an authenticated page behaves normally
[ ] No obvious mixed-content failure
```

Test at least:

```text
admin@talibon.demo
engineering@talibon.demo
employee@talibon.demo
```

If the reviewer needs another persona, the other seeded accounts are available as listed earlier.

---

# 19. Recommended final deployment verification commands

Run from the repository root:

```powershell
Write-Host '=== BRANCH ==='
git branch --show-current

Write-Host '=== HEAD ==='
git rev-parse HEAD

Write-Host '=== WORKTREE ==='
git status --short

Write-Host '=== PHP ==='
php -v

Write-Host '=== NODE ==='
node -v

Write-Host '=== NPM ==='
npm.cmd -v

Write-Host '=== LARAVEL ENV ==='
php artisan about

Write-Host '=== MIGRATIONS ==='
php artisan migrate:status

Write-Host '=== PORT 8000 ==='
Get-NetTCPConnection -State Listen -LocalPort 8000 -ErrorAction SilentlyContinue |
    Select-Object LocalAddress, LocalPort, OwningProcess
```

Do not paste `.env` into deployment reports because it contains secrets.

---

# 20. Troubleshooting

## A. `npm.ps1 cannot be loaded because running scripts is disabled`

Use:

```powershell
npm.cmd ci --no-audit --no-fund
npm.cmd run types:check
npm.cmd run build
```

Do not change PowerShell execution policy just to fix this.

---

## B. Composer says Symfony requires PHP >= 8.4.1

Check:

```powershell
php -v
```

Use PHP 8.4.1+.

Do not fix this by running `composer update`.

---

## C. `could not find driver` / PostgreSQL driver error

Check:

```powershell
php -m | findstr /I "pgsql pdo_pgsql"
```

If missing, enable/install the PostgreSQL PHP extensions for the active PHP installation.

Also determine the active `php.ini`:

```powershell
php --ini
```

---

## D. PostgreSQL connection refused

Check PostgreSQL services:

```powershell
Get-Service |
    Where-Object { $_.Name -match 'postgres' -or $_.DisplayName -match 'postgres' }
```

Check port 5432:

```powershell
Get-NetTCPConnection -State Listen -LocalPort 5432 -ErrorAction SilentlyContinue
```

Then verify `.env`:

```text
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD
```

---

## E. Seeder says `PROTOTYPE_DEMO_PASSWORD must be configured`

This is intentional in `production` mode.

Set a private strong value of at least 16 characters:

```dotenv
PROTOTYPE_DEMO_PASSWORD=<private value>
```

Then:

```powershell
php artisan optimize:clear
php artisan migrate:fresh --seed --force
```

Only do this against the dedicated showcase database.

---

## F. Laravel shows a 500 error

Keep `APP_DEBUG=false` publicly.

Inspect the application log locally:

```powershell
Get-Content .\storage\logs\laravel.log -Tail 100
```

For continuous observation:

```powershell
Get-Content .\storage\logs\laravel.log -Wait -Tail 50
```

Do not turn public debug mode on merely to show the reviewer the exception.

---

## G. Cloudflare shows `502 Bad Gateway`

This almost always means the tunnel cannot reach Laravel.

Check whether port 8000 is listening:

```powershell
Get-NetTCPConnection -State Listen -LocalPort 8000 -ErrorAction SilentlyContinue
```

Check localhost directly:

```powershell
curl.exe -I http://127.0.0.1:8000
```

If localhost fails, fix Laravel first.

Then restart the tunnel if necessary:

```powershell
cloudflared tunnel --url http://127.0.0.1:8000
```

---

## H. Login works locally but fails through the HTTPS tunnel / 419 CSRF issue

Verify:

```dotenv
APP_URL=https://<actual-current-quick-tunnel>.trycloudflare.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

Then:

```powershell
php artisan optimize:clear
php artisan config:cache
```

Restart Laravel and retry in a fresh browser session.

Also make sure you are not using an old Quick Tunnel hostname after restarting `cloudflared`.

---

## I. Assets are missing or the UI is unstyled

Rebuild:

```powershell
npm.cmd ci --no-audit --no-fund
npm.cmd run build
```

Confirm `public\build` exists:

```powershell
Get-ChildItem .\public\build -ErrorAction SilentlyContinue
```

Then restart Laravel.

---

## J. Port 8000 is already in use

Identify the owner:

```powershell
Get-NetTCPConnection -LocalPort 8000 -ErrorAction SilentlyContinue |
    Select-Object LocalAddress, LocalPort, State, OwningProcess
```

Then:

```powershell
Get-Process -Id <PID>
```

Do not randomly terminate processes. Confirm what owns the port first.

If another valid service must keep 8000, use a different port consistently, for example 8080:

```powershell
php artisan serve --host=127.0.0.1 --port=8080
cloudflared tunnel --url http://127.0.0.1:8080
```

---

## K. `cloudflared` command not found

If you downloaded it manually:

```powershell
& 'C:\Cloudflared\bin\cloudflared.exe' --version
```

Then run the tunnel using the full path.

A Quick Tunnel does not require `%USERPROFILE%\.cloudflared\config.yml`.

---

# 21. Keeping the expedited review link alive

Two processes must remain alive:

### Window 1 — Laravel

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

### Window 2 — Cloudflare

```powershell
cloudflared tunnel --url http://127.0.0.1:8000
```

If either process stops, the public review deployment stops working.

If the computer sleeps, shuts down, loses internet connectivity, or restarts, the Quick Tunnel will stop and the generated public URL should be treated as expired.

For the expedited review path, this is acceptable.

If Kirch later wants a stable hostname or unattended deployment, that should be converted into a named/remotely managed Cloudflare Tunnel or another proper hosting arrangement rather than pretending the Quick Tunnel is production infrastructure.

---

# 22. Stopping the deployment

To stop public access:

1. Press `Ctrl+C` in the Cloudflare tunnel window.
2. Press `Ctrl+C` in the Laravel server window.

Confirm port 8000 is no longer listening:

```powershell
Get-NetTCPConnection -State Listen -LocalPort 8000 -ErrorAction SilentlyContinue
```

No Cloudflare DNS cleanup is necessary for a Quick Tunnel because the temporary hostname belongs to the Quick Tunnel session.

---

# 23. Updating the expedited deployment later

Do not automatically pull random UI/UX writer branches into this deployment.

When Kirch authorizes a newer review build, he should identify the exact promoted branch/SHA.

Then the operator can:

```powershell
git fetch origin
git status --short
```

If the worktree is clean and the deployment branch has been intentionally updated:

```powershell
git pull --ff-only
composer install --no-interaction --prefer-dist --optimize-autoloader
npm.cmd ci --no-audit --no-fund
npm.cmd run types:check
npm.cmd run build
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Do **not** rerun `migrate:fresh --seed` during a normal update unless the deployment is deliberately being reset as a disposable showcase database.

---

# 24. Fast-path checklist for AJ

If the machine already has Git, PHP 8.4+, Composer, Node 22, PostgreSQL, and cloudflared, the practical sequence is:

```powershell
# 1. Get the deployment branch
git clone --branch KIRCH-TALIBON-V1-EXPEDITED-DEPLOYMENT --single-branch https://github.com/Kirch-Nairu/Talibon-Sales-Prototype.git C:\Talibon-Expedited-Deployment
cd C:\Talibon-Expedited-Deployment

# 2. PHP dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader

# 3. Frontend dependencies + validation
npm.cmd ci --no-audit --no-fund
npm.cmd run types:check
npm.cmd run build

# 4. Environment
Copy-Item .env.example .env
notepad .env
# Configure APP_ENV, APP_URL, PostgreSQL, SHOWCASE_MODE,
# and a private PROTOTYPE_DEMO_PASSWORD.

# 5. Key
php artisan key:generate

# 6. Dedicated showcase DB must already exist before this command.
php artisan optimize:clear
php artisan migrate:fresh --seed --force

# 7. Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Local origin
php artisan serve --host=127.0.0.1 --port=8000
```

Then open a second PowerShell:

```powershell
cloudflared tunnel --url http://127.0.0.1:8000
```

Copy the generated HTTPS URL, update `APP_URL`, set `SESSION_SECURE_COOKIE=true`, clear/cache configuration, restart Laravel, perform the public smoke test, then send the verified link.

---

# 25. What to report back after deployment

Please give Kirch:

```text
Deployment branch:
Deployment HEAD SHA:
Machine PHP version:
Node version:
PostgreSQL database used:
Frontend typecheck: PASS / FAIL
Frontend build: PASS / FAIL
Migration/seed: PASS / FAIL
Local smoke test: PASS / FAIL
Cloudflare URL:
Public login smoke test: PASS / FAIL
Personas tested:
Known issues:
```

Do not include:

- `DB_PASSWORD`;
- `APP_KEY`;
- `PROTOTYPE_DEMO_PASSWORD`;
- tunnel credentials/tokens;
- private credentials of any kind.

---

# 26. Final note to AJ

AJ, the main rule is simple: **make localhost fully healthy first, then expose that known-good localhost through Cloudflare.** Do not use the tunnel as a debugging substitute for a broken Laravel installation, and do not point destructive demo seeding at an existing non-demo database.

Take the deployment one layer at a time:

```text
Dependencies
→ exact branch
→ Composer
→ frontend build
→ .env
→ dedicated PostgreSQL DB
→ migrations + seed
→ localhost
→ local login smoke test
→ Cloudflare Quick Tunnel
→ public HTTPS smoke test
→ send the link
```

Good luck with the deployment, AJ. Thank you for handling this for us.

**— Kirch**
