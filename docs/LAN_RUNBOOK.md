# LAN Prototype Runbook

## Prerequisites on the host

- PHP 8.3+
- Composer
- Node.js 22+
- PostgreSQL 16+

## First-time setup

```powershell
Copy-Item .env.example .env
composer install
php artisan key:generate
npm install
```

Create a PostgreSQL database named `talibon_portal`, then set `DB_USERNAME` and `DB_PASSWORD` in `.env`.

Before a controlled demonstration that requires known login credentials, set `PROTOTYPE_DEMO_PASSWORD` in the local `.env` to a private value of at least 16 characters. Do not commit, publish, log, or place that value in frontend code. Production demo seeding refuses to run without an acceptable environment-provided value.

```powershell
php artisan migrate:fresh --seed
npm run build
```

## Run on the LAN

Terminal 1:

```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

For development with Vite hot reload, Terminal 2:

```powershell
npm run dev
```

Find the host IPv4 address with:

```powershell
ipconfig
```

Other devices on the same trusted LAN open:

```text
http://HOST_IPV4:8000
```

If Windows Firewall prompts for PHP/Node access, permit only the appropriate Private network profile for the controlled demo network.

## Demo identities

Featured synthetic prototype identities include:

- admin@talibon.demo
- mayor@talibon.demo
- engineering@talibon.demo
- budget@talibon.demo
- hr@talibon.demo
- legislative@talibon.demo
- employee@talibon.demo

Passwords are not committed to the repository. When `PROTOTYPE_DEMO_PASSWORD` is omitted outside production, the seeder uses an unreported random value so the dataset can still be created without establishing a shared repository credential.

These identities are synthetic prototype data. They must not be reused as production identities.

## Reset before a demonstration

```powershell
php artisan migrate:fresh --seed
```

Run the full demo path after every reset before presenting.
