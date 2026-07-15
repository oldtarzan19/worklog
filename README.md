# Worklog – munkaidő-nyilvántartó alkalmazás

A Worklog egy magyar nyelvű, reszponzív munkaidő-nyilvántartó webalkalmazás. A munkavállalók saját munkaidő-bejegyzéseiket kezelhetik és exportálhatják, az adminisztrátorok pedig a regisztrációkat, felhasználókat és a teljes csapat munkaidő-riportjait felügyelhetik.

A megvalósítás alapjául szolgáló eredeti specifikáció a [PLAN.md](PLAN.md) fájlban található.

## Technológiai háttér

- PHP 8.4 és Laravel 12
- Inertia.js 2, Vue 3 és TypeScript
- Tailwind CSS 3, Shadcn Vue és Unovis diagramok
- MySQL
- Pest 3
- Laravel Excel (`maatwebsite/excel`) XLSX-exporthoz

Az alkalmazás alapértelmezett nyelve magyar, időzónája `Europe/Budapest`, a felület pedig 24 órás időformátumot használ.

## Fő funkciók

### Munkavállalói funkciók

- Nyilvános regisztráció. Az új fiók csak adminisztrátori jóváhagyás után használható.
- Saját munkaidő rögzítése, szerkesztése és törlése.
- Több idősáv rögzítése ugyanarra a napra, így a munkaközi szünetek külön tárolás nélkül kezelhetők.
- Havi munkaidő-naptár, napi összesítések és részletes, lapozott bejegyzéslista.
- KPI-k az összes munkaidőről, a munkanapok számáról és a napi átlagról.
- Napi munkaidőt bemutató diagram.
- Aktuális hónap, előző hónap, aktuális év vagy egyedi dátumtartomány kiválasztása. Egy lekérdezés legfeljebb 366 napot fedhet le.
- A kiválasztott szűrők megmaradnak az URL-ben.
- Saját adatok letöltése kéttáblás XLSX-fájlban.
- Saját név, e-mail-cím, jelszó és megjelenési téma módosítása.

### Adminisztrátori funkciók

- Függő regisztrációk listázása, jóváhagyása vagy elutasítása; a függő kérelmek száma az oldalsávban is látható.
- Felhasználók keresése és szűrése név, e-mail-cím, szerepkör és állapot alapján.
- Felhasználó nevének, e-mail-címének, szerepkörének és aktív állapotának módosítása.
- Egy kiválasztott felhasználó vagy a teljes csapat riportjának megtekintése.
- Egyéni és csapatszintű naptár, diagram, KPI-k és felhasználónkénti összesítés.
- Munkaidő-bejegyzések kezelése a kiválasztott felhasználó nevében.
- Egy felhasználó vagy az összes felhasználó adatainak XLSX-exportja az aktív dátumszűrőkkel.

### Munkaidő-szabályok

- A befejezésnek későbbinek kell lennie a kezdésnél.
- Egy felhasználó azonos napi idősávjai nem fedhetik egymást.
- Jövőbeli munkaidő nem rögzíthető.
- Éjfélen átnyúló munkaidőt két külön napra kell bontani.
- A megjegyzés opcionális és legfeljebb 500 karakteres lehet.
- Az időtartamot a backend számítja, ezért a képernyőn és az exportban ugyanaz az eredmény jelenik meg.

## Szerepkörök és jogosultságok

| Szerepkör | Hozzáférés |
| --- | --- |
| Vendég | Kezdőlap, bejelentkezés és regisztráció |
| Felhasználó (`user`) | Kizárólag a saját munkaidő-adatainak kezelése, megtekintése és exportja |
| Adminisztrátor (`admin`) | Regisztrációk, felhasználók, minden munkaidő-bejegyzés, riportok és admin export |

A letiltott felhasználó nem tud bejelentkezni, a már aktív munkamenete pedig megszűnik. Az utolsó aktív adminisztrátor nem tiltható le és nem fokozható vissza. A fiók önálló törlése nem érhető el, így a munkaidő-nyilvántartás megmarad.

## Tesztfelhasználók

A `WorklogDemoSeeder` a következő helyi demófiókokat hozza létre:

| Név | Szerepkör | Állapot | E-mail-cím | Jelszó |
| --- | --- | --- | --- | --- |
| Worklog Admin | Admin | Aktív | `admin@worklog.test` | `password` |
| Kovács Anna | Felhasználó | Aktív | `anna.kovacs@worklog.test` | `password` |
| Nagy Balázs | Felhasználó | Aktív | `balazs.nagy@worklog.test` | `password` |
| Tóth Csilla | Felhasználó | Aktív | `csilla.toth@worklog.test` | `password` |
| Kiss Dávid | Felhasználó | **Letiltott** | `david.kiss@worklog.test` | `password` |

Kiss Dávid a felhasználólistán és a riportokban is megjelenik, de letiltott állapota miatt nem tud bejelentkezni. Az adminisztrátor a **Felhasználók** oldalon újra engedélyezheti a fiókját.

A négy munkavállalóhoz az előző 90 nap munkanapjaira valószerű adatok készülnek. A munkanapok jellemzően két idősávból állnak egy ebédszünettel, és néhány kihagyott napot is tartalmaznak. A demó adminisztrátorhoz a seeder nem készít munkaidő-bejegyzéseket.

### Függő regisztrációs kérelmek

A seeder a **Regisztrációk** adminoldal kipróbálásához három függő kérelmet is létrehoz:

| Név | E-mail-cím | Jelszó | Állapot |
| --- | --- | --- | --- |
| Szabó Eszter | `eszter.szabo@worklog.test` | `password` | Jóváhagyásra vár |
| Horváth Gergő | `gergo.horvath@worklog.test` | `password` | Jóváhagyásra vár |
| Varga Lilla | `lilla.varga@worklog.test` | `password` | Jóváhagyásra vár |

Ezek még nem felhasználói fiókok, ezért nem tudnak belépni. Helyes jelszó megadásakor a bejelentkezési oldal jelzi, hogy a regisztráció adminisztrátori jóváhagyásra vár. Jóváhagyás után aktív felhasználói fiókká alakulnak.

## Helyi telepítés Laravel Herddel

Előfeltételek:

- PHP 8.4
- Composer
- Node.js és npm
- MySQL
- Laravel Herd

Telepítés:

```powershell
composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
```

Hozd létre a `.env` fájlban megadott adatbázist, majd ellenőrizd legalább a `DB_DATABASE`, `DB_USERNAME` és `DB_PASSWORD` értékeket. Ezután futtasd:

```powershell
php artisan migrate --seed
npm run dev
```

Production frontend build készítéséhez:

```powershell
npm run build
```

## Seedelés

Az alapértelmezett `DatabaseSeeder` meghívja a `WorklogDemoSeeder` osztályt.

Meglévő, migrált helyi adatbázis demóadatokkal való feltöltése:

```powershell
php artisan db:seed
```

Migrációk futtatása és seedelés:

```powershell
php artisan migrate --seed
```

Az adatbázis teljes újraépítése:

```powershell
php artisan migrate:fresh --seed
```

A seeder többször futtatható: frissíti az öt demófiókot, majd a négy demó munkavállaló előző 90 napba eső bejegyzéseit újragenerálja. Emiatt az ezekhez a demófiókokhoz kézzel felvitt, ugyanebbe az időszakba eső bejegyzések is törlődnek. A demófiókok jelszava, szerepköre és aktív állapota minden futtatáskor visszaáll a fent dokumentált értékre.

A három függő demókérelem újrafuttatáskor frissül, de a seeder nem hoz létre kérelmet olyan e-mail-címhez, amelyből időközben már felhasználói fiók lett. Production környezetben a demóadatok feltöltéséhez futtasd a `php artisan db:seed --force` parancsot.

## Adminisztrátor létrehozása

Az első éles adminisztrátort interaktívan hozd létre:

```powershell
php artisan worklog:create-admin
```

A parancs bekéri a nevet, az e-mail-címet, a jelszót és annak megerősítését. Az e-mail-címnek egyedinek kell lennie a felhasználók és a függő regisztrációs kérelmek között.

Automatizált helyi környezetben az adatok opciókkal is megadhatók:

```powershell
php artisan worklog:create-admin --name="Adminisztrátor" --email="admin@example.com" --password="biztonsagos-jelszo"
```

Éles rendszeren az interaktív mód ajánlott, mert a parancssori opcióként megadott jelszó bekerülhet a terminál előzményeibe vagy a folyamatlistába.

## Regisztráció és belépés

1. A látogató megadja a nevét, e-mail-címét és jelszavát a regisztrációs oldalon.
2. A rendszer függő regisztrációs kérelmet hoz létre; a jelentkező ekkor még nem tud belépni.
3. Az adminisztrátor a **Regisztrációk** oldalon jóváhagyja vagy elutasítja a kérelmet.
4. Jóváhagyáskor aktív `user` fiók jön létre az eredeti jelszóval, és a kérelem törlődik.
5. Elutasításkor a kérelem végleg törlődik.

A függő jelentkező helyes belépési adatokkal külön „jóváhagyásra vár” üzenetet kap. Az alkalmazásban nincs e-mailes címellenőrzés, elfelejtettjelszó-folyamat vagy e-mail-küldés; elveszett jelszó esetén adminisztrátori vagy adatbázis-kezelői beavatkozás szükséges.

## XLSX-export

Minden export két munkalapot tartalmaz:

- **Összesítés:** felhasználó, munkanapok, teljes idő és napi átlag.
- **Részletek:** felhasználó, dátum, kezdés, befejezés, időtartam és megjegyzés.

Az időtartamok valódi Excel-időértékként, `[h]:mm` formátumban készülnek. A jogosultság és a szűrés szerveroldali; a felhasználói export nem tartalmazhat más fiókhoz tartozó adatot. A szöveges cellák képletként való kiértékelése ellen az export külön védelemmel rendelkezik.

## Éles környezet

- Állítsd az `APP_ENV=production` és `APP_DEBUG=false` értékeket.
- Használj saját alkalmazáskulcsot, adatbázist és megfelelő `APP_URL` értéket.
- Futtasd a migrációkat: `php artisan migrate --force`, majd töltsd be a demóadatokat: `php artisan db:seed --force`.
- Készítsd el a frontend asseteket: `npm ci`, majd `npm run build`.
- Az első admint a `php artisan worklog:create-admin` paranccsal hozd létre.
- Az alkalmazás állapotellenőrző végpontja: `/up`.

## Tesztelés és kódminőség

```powershell
php artisan test --compact
vendor/bin/pint --format agent
npm run format:check
npm run lint
npm run build
```

A tesztek lefedik többek között a regisztráció és jóváhagyás folyamatát, a jogosultságokat, a felhasználókezelést, a munkaidő-validációt, az összesítéseket, a seedert és az XLSX-exportot.
