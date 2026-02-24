# Auth Server

A centralized SSO (Single Sign-On) authentication server built with Laravel 11 and Laravel Passport.

## Requirements

- PHP >= 8.2
- Composer
- Node.js & npm
- MySQL

## Setup

### 1. Install Dependencies

```bash
composer install
npm install
```

### 2. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Update the `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=auth_server
DB_USERNAME=root
DB_PASSWORD=your_password
```

Add SSO client app URLs:

```env
ECOMMERCE_APP_URL=http://127.0.0.1:8001
FOODPANDA_APP_URL=http://127.0.0.1:8002
```

### 3. Database Setup

```bash
php artisan migrate
php artisan db:seed
```

> The seeder creates a test user (`test@example.com`) and registers SSO OAuth clients (Ecommerce & Foodpanda). Note the **Client ID** and **Client Secret** printed in the terminal — you'll need them for the client apps' `.env` files.

### 4. Install Passport Keys

```bash
php artisan passport:keys
```

### 5. Run the Application

```bash
composer run dev
```

This starts concurrently:
- **Laravel server** at `http://127.0.0.1:8000`
- **Queue worker**
- **Log viewer** (Pail)
- **Vite** dev server
