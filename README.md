#  Auth Server (Laravel SSO Server)

A centralized **Single Sign-On (SSO) Authentication Server** built with Laravel 11 and Laravel Passport.

This server provides OAuth2 authentication for multiple client applications (Example: Ecommerce & Foodpanda).  
When a user logs in once, they can access all connected apps.

---

#  Features

- OAuth2 Authorization Server
- Laravel Passport authentication
- Multi-application SSO login
- Centralized authentication service
- Token-based authorization
- Client management via console command
- Session + Cache + Queue using database
- Preconfigured test clients

---

#  Tech Stack

- Laravel 11
- Laravel Passport
- MySQL
- OAuth2 Authorization Code Flow

---

#  Requirements

Make sure your system has:

- PHP >= 8.2
- Composer
- Node.js >= 18
- npm
- MySQL / MariaDB
- Git

---

# ⚡ Installation Guide (Complete Setup)

Follow **all steps carefully**.

---

## 1️⃣ Clone Project

```bash
git clone <your-repository-url>
cd <project-folder>
````

---

## 2️ Install Dependencies

```bash
composer install
npm install
```

---

## 3️ Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

---

## 4️ Configure Database

Update `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=auth_server
DB_USERNAME=root
DB_PASSWORD=your_password
```

Create database manually:

```sql
CREATE DATABASE auth_server;
```

---

## 5️ Configure SSO Client URLs

Update `.env`:

```env
ECOMMERCE_APP_URL=http://127.0.0.1:8001
FOODPANDA_APP_URL=http://127.0.0.1:8002
```

These are redirect targets for OAuth login.

---

## 6️ Install Laravel Passport

```bash
composer require laravel/passport
php artisan passport:install
php artisan passport:keys
```

This generates encryption keys for OAuth tokens.

---

## 7️ Create Required Database Tables

Your project uses:

* database session
* database cache
* database queue

So generate tables:

```bash
php artisan session:table
php artisan cache:table
php artisan queue:table
php artisan migrate
```

---

## 8️ Seed SSO Clients

```bash
php artisan db:seed
```

This creates:

* Test user (`test@example.com`)
* Test Password (`password`)
* Ecommerce OAuth client
* Foodpanda OAuth client

You will see:

```
Client ID
Client Secret
```

Copy them into client app `.env` files.

---


## 9 Link Storage (Recommended)

```bash
php artisan storage:link
```

---

## 1️0 Run Server

```bash
php artisan serve --port=8000
```

Server runs at:

```
http://127.0.0.1:8000
```

---

# 🔑 Test Credentials

```
Email: test@example.com
Password: password
```

(If password changed in seeder, check DatabaseSeeder.)

---

#  Client Applications Setup

Each client app must store:

```env
SSO_CLIENT_ID=xxxx
SSO_CLIENT_SECRET=xxxx
SSO_SERVER_URL=http://127.0.0.1:8000
```

Redirect URI must match exactly:

```
http://127.0.0.1:8001/auth/callback
http://127.0.0.1:8002/auth/callback
```

---

#  SSO Client Management (Console Commands)

You included a custom command:

## List Clients

```bash
php artisan sso:clients list
```

---

## Create New Client

```bash
php artisan sso:clients create \
--name="New App" \
--redirect="http://127.0.0.1:9000/auth/callback"
```

---

## Revoke Client

```bash
php artisan sso:clients revoke --id=CLIENT_ID
```

---

## Refresh Client Secret

```bash
php artisan sso:clients refresh-secret --id=CLIENT_ID
```

---

#  OAuth Flow Overview

1. Client app redirects user → Auth Server
2. User logs in once
3. Auth Server issues authorization code
4. Client exchanges code → access token
5. User is authenticated across all apps

---

# 📁 Project Structure Notes

```
app/Console/Commands/SsoClientManage.php
→ Manage OAuth clients

database/seeders/SsoClientSeeder.php
→ Creates default clients

Laravel Passport
→ OAuth2 server implementation
```

---

# ⚠️ Important Notes

* Redirect URIs must match exactly.
* Client secret is shown only once.
* Store client secret securely.
* Never commit `.env` to Git.
* Regenerate keys if leaked:

```bash
php artisan passport:keys --force
```

---

#  Troubleshooting

## Clear Cache

```bash
php artisan optimize:clear
```

---

## Permission Issues (Linux)

```bash
chmod -R 775 storage bootstrap/cache
```

---

## Migration Errors

```bash
php artisan migrate:fresh --seed
```

---

## Passport Token Issues

```bash
php artisan passport:keys --force
```

---


#  Author

Laravel SSO Authentication Server.

---
