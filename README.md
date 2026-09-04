# ZoodeSIM

Partner referral platform for ZoodeSIM eSIM — Laravel + Livewire admin and partner portals.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
```

## Demo credentials (after seeding)

- Admin: `admin@zoodesim.test` / `password123`
- Partner: `partner@zoodesim.test` / `password123`
