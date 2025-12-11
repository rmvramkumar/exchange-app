# 🚀 Exchange API — Setup Guide

This project contains:

- **Laravel Backend API**
- **Vue.js (Vite) Frontend**
- **Real-Time System using Pusher**
- **Order Matching, Wallet Management & User Authentication**

Follow the steps below to set up the backend and frontend environments.

---

# 📦 Setup Instructions   

## 🎯 Backend Setup (Laravel)

### 1️⃣ Install Dependencies
```bash
cd exchange-app
composer install
```

---

### 2️⃣ Environment Configuration

Copy `.env`:
```bash
copy .env.example .env
```

Generate the app key:
```bash
php artisan key:generate
```

Update `.env` with database info:
```
DB_DATABASE=exchange_api
DB_USERNAME=root
DB_PASSWORD=
```

---

### 3️⃣ Configure Pusher (Real-time Broadcasting)

Go to **https://pusher.com** and create an app.  
Add credentials to `.env`:

```
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1
```

---

### 4️⃣ Run Migrations
```bash
php artisan migrate
```

---

### 5️⃣ Create Test User (Optional)
```bash
php artisan tinker
```

Inside Tinker:
```php
User::create([
    'name' => 'Test User',
    'email' => 'user@example.com',
    'password' => bcrypt('password'),
    'balance' => 10000
]);
```

---

### 6️⃣ Start Laravel Server
```bash
php artisan serve
```

Backend runs at:
➡️ **http://localhost:8000**

---

# 🎨 Frontend Setup (Vue 3 + Vite)

### 1️⃣ Install Dependencies
```bash
cd frontend
npm install
```

---

### 2️⃣ Create `.env` File
Add the following:

```
VITE_PUSHER_APP_KEY=your_pusher_app_key
VITE_PUSHER_CLUSTER=mt1
```

---

### 3️⃣ Install Tailwind (If not already installed)
```bash
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
```

---

### 4️⃣ Start Development Server
```bash
npm run dev
```

Frontend runs at:
➡️ **http://localhost:5173**

---

### 5️⃣ Build for Production
```bash
npm run build
```

---

# ✅ You're Ready to Go!
