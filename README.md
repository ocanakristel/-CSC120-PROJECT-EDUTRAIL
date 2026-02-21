# EduTrail (CSC 120 Project)

EduTrail is an academic buddy web app that helps organize tasks, assignments, and projects.

## Project Structure
- `edutrail/` – Frontend (Vue + Vite + Vuetify)
- `edutrail-backend/` – Backend (Laravel API)

## Requirements
- Node.js (for frontend)
- PHP + Composer (for backend)
- MySQL (database)

## Setup

### Backend (Laravel)
```bash
cd edutrail-backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
