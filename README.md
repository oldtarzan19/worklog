# Worklog

Magyar nyelvű, reszponzív munkaidő-nyilvántartó Laravel 12, Inertia.js 2, Vue 3, TypeScript, Tailwind CSS 3 és Shadcn Vue alapokon.

## Követelmények

- PHP 8.4 és Composer
- Node.js és npm
- MySQL 8+

Laravel Herd használatakor a projekt a `https://worklog.test` címen érhető el, külön webszerver indítása nélkül.

## Telepítés

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

A `.env` fájlban állítsd be a `DB_*` és `APP_URL` értékeket. Az alkalmazás alapértelmezett időzónája `Europe/Budapest`, nyelve `hu`, a production adatbázis pedig MySQL.

## Első regisztráció

> Biztonsági figyelmeztetés: az üres rendszer első regisztrálója automatikusan aktív adminisztrátor lesz. Production telepítés után azonnal hozd létre ezt a fiókot, és ne hagyd őrizetlenül a nyilvános regisztrációs oldalt.

Minden további regisztráció függő kérelemként jön létre. Az admin a **Regisztrációk** oldalon hagyhatja jóvá vagy utasíthatja el. Az alkalmazás nem küld e-mailes értesítéseket.

## Fejlesztés és ellenőrzés

```bash
npm run dev
php artisan test --compact
vendor/bin/pint --format agent
npm run format:check
npm run lint
npm run build
```

A Pest tesztek SQLite memóriadatbázist használnak. A munkaidő-validáció tiltja az átfedő, jövőbeli, hibás és éjfélen átnyúló idősávokat; a jogosultságokat szerveroldali policy-k és middleware-ek védik.

## Fő funkciók

- több idősávos napi munkaidő CRUD;
- havi naptár, KPI-k és Unovis diagram;
- URL-ben megőrzött dátumszűrők;
- felhasználói és admin XLSX export két munkalappal;
- admin regisztráció-, felhasználó- és riportkezelés;
- világos és sötét megjelenés.
