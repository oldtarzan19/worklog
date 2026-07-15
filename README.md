# Worklog – munkaidő-nyilvántartó alkalmazás

## Összefoglaló

Laravel 12, Inertia.js 2, Vue 3, TypeScript, Tailwind CSS 3 és kötelezően Shadcn Vue komponensek használatával készülő, magyar nyelvű, reszponzív munkaidő-nyilvántartó alkalmazás.

- A munkavállalók több idősávban rögzíthetik, szerkeszthetik és törölhetik saját munkaidejüket.
- A backend számítja ki a napi és időszaki munkaidőt.
- A normál felhasználók kizárólag saját adataikat láthatják és exportálhatják.
- Az admin minden felhasználó adatait szűrheti, kezelheti és XLSX-formátumban exportálhatja.
- A dashboard központi eleme egy látványos munkaidő-naptár, kiegészítő KPI-kártyákkal és diagrammal.
- Az alkalmazás támogatja a világos és sötét megjelenést.

## Adatmodell, hitelesítés és jogosultságok

### Felhasználók és regisztrációk

- A `users` tábla új mezői:
    - `role`: `admin` vagy `user`, PHP enum casttal.
    - `is_active`: meghatározza, hogy a felhasználó beléphet-e.
- Külön `registration_requests` tábla készül:
    - `name`, egyedi `email`, biztonságosan hash-elt `password`, időbélyegek.
    - A függő jelentkező még nem `User`, ezért nem tud bejelentkezni.
- Az első aktív adminisztrátort a telepítéskor a `php artisan worklog:create-admin` paranccsal kell létrehozni.
- Minden nyilvános regisztráció függő kérelmet hoz létre, és egy várakozási visszajelző oldalra irányít.
- Helyes jelszóval próbálkozó függő jelentkező „jóváhagyásra vár” üzenetet kap; más esetben marad az általános hibajelzés.
- Jóváhagyáskor tranzakcióban létrejön az aktív `user` szerepű felhasználó, majd törlődik a kérelem.
- Elutasításkor a kérelem végleg törlődik.
- Az admin módosíthatja a szerepkört és letilthatja/engedélyezheti a fiókot. Az utolsó aktív admin nem tiltható le és nem fokozható le.
- Letiltott fiók nem léphet be; meglévő munkamenetét middleware megszünteti.
- Saját fiók végleges törlése nem lesz elérhető, hogy a munkaidő-adatok megmaradjanak.

### Munkaidő-bejegyzések

A `work_entries` tábla mezői:

- `user_id` idegen kulcs;
- `work_date` dátum;
- `start_time` és `end_time` percpontossággal;
- opcionális `note`;
- időbélyegek;
- összetett index `user_id + work_date` mezőkre.

Szabályok:

- Egy naphoz több idősáv tartozhat; a szüneteket az idősávok közötti kihagyás jelenti.
- Az idősávok ugyanazon felhasználó és nap esetén nem fedhetik egymást.
- A befejezésnek későbbinek kell lennie a kezdésnél.
- Éjfélen átnyúló bejegyzés nem menthető; azt két napra kell bontani.
- Jövőbeli dátum vagy még el nem érkezett befejezési idő nem rögzíthető.
- Nincs normaidő-, túlóra-, projekt- vagy bérszámítás.
- Az időtartam nem kerül redundánsan tárolásra: egy közös backend szolgáltatás számolja percben, így a dashboard, riport és Excel ugyanazt az eredményt használja.

A policy-k alapján a felhasználó csak saját rekordjain végezhet CRUD-műveletet, az admin pedig bárkién.

## Felületek és felhasználói élmény

### Felhasználói dashboard

- Alapértelmezett időszak az aktuális hónap.
- Dátumtartomány-választó „ettől–eddig” mezőkkel, valamint aktuális hónap, előző hónap, aktuális év és egyedi időszak gyorsszűrőkkel.
- A szűrők URL query paraméterként maradnak meg frissítés és visszanavigálás után.
- Az oldalon található egy gomb ahol egy felugró modalban a felhasználó meg tudja adni a ledolgozott munkaidejét.
- Fő elem egy havi Shadcn naptár:
    - minden nap cellája mutatja az összes ledolgozott időt;
    - a színintenzitás jelzi a napi óraszámot;
    - napra kattintva Sheet/Dialog nyílik az idősávokkal és a szerkesztés/törlés műveletekkel.
- Kiegészítő KPI-k: összes munkaidő, munkanapok száma és napi átlag.
- Kompakt oszlopdiagram mutatja a szűrt időszak napi munkaidejét.
- A részletes, lapozott táblázat dátumot, kezdést, befejezést, időtartamot és megjegyzést mutat.
- Saját XLSX-export az aktuális szűrőkkel.

### Adminfelületek

- „Regisztrációk” oldal függő kérelmekkel, Badge-ekkel, jóváhagyási és megerősítést kérő elutasítási párbeszédablakkal.
- A sidebar jelzi a függő kérelmek számát.
- „Felhasználók” oldal név/e-mail/szerepkör/állapot szerinti kereséssel és szűréssel.
- „Riportok” oldal:
    - egy felhasználó vagy minden felhasználó kiválasztása;
    - ugyanazok a hónap-, év- és dátumtartomány-szűrők;
    - egy kiválasztott személynél a felhasználói naptár és diagram;
    - mindenkinél csapatszintű KPI-k, napi összesítő diagram és felhasználónkénti táblázat;
    - adminisztrátori munkaidő CRUD a kiválasztott felhasználóhoz;
    - XLSX-letöltés az aktuális szűrőkkel.

### Shadcn Vue használat

Minden interaktív felület Shadcn elemekből épül: Sidebar, Card, Calendar/Range Calendar, Popover, Select, Combobox, Dialog, Sheet, Form, Input, Textarea, Table/Data Table, Badge, Alert Dialog, Skeleton, Tooltip, Sonner és
Chart. A chart komponens az official Shadcn Vue megoldás szerint Unovisra épül.

A felület:

- magyar nyelvű, 24 órás időformátumú;
- mobilon, tableten és asztali nézetben használható;
- teljes világos/sötét módot támogat;
- betöltéskor skeletonokat, műveleteknél toast visszajelzéseket és megerősítést igénylő törléseket használ.

## Interfészek és technikai megvalósítás

- REST-szerű, névvel ellátott web route-ok készülnek a munkaidő-bejegyzésekhez, exporthoz, regisztrációs döntésekhez és userkezeléshez.
- Form Request osztályok végzik a validációt és az engedélyezést; policy-k akadályozzák meg más felhasználók adatainak elérését.
- A listák lapozottak, a riportlekérdezések csak a szükséges oszlopokat kérik le, és használják a dátum- és felhasználóindexeket.
- Az Inertia oldalak típusos `TimeEntry`, `DailySummary`, `DashboardFilters`, `UserOption` és `RegistrationRequest` propokat kapnak.
- Az XLSX-exporthoz a Laravel 12-t támogató stabil `maatwebsite/excel:^3.1` csomag kerül be.
- Az Excel két munkalapot tartalmaz:
    - „Összesítés”: felhasználó, munkanapok, teljes idő és napi átlag;
    - „Részletek”: felhasználó, dátum, kezdés, befejezés, időtartam és megjegyzés.
- A tartam cellái valódi Excel-időértékek `[h]:mm` formázással.
- Az export jogosultsága és szűrése szerveroldali; manipulált query paraméterrel sem kérhetők le tiltott adatok.

## Fejlesztői tesztadatok és seedelés

- A fejlesztés és a bemutatás támogatására külön `WorklogDemoSeeder` készül, amelyet az alapértelmezett `DatabaseSeeder` hív meg.
- A seeder célja, hogy az alkalmazás fő funkciói – a dashboard, a naptár, a riportok, az adminisztráció és az XLSX-export – kézi adatbevitel nélkül, valószerű tesztadatokkal azonnal kipróbálhatók legyenek.
- A demó adatok egy aktív adminisztrátort és négy aktív munkavállalót tartalmaznak. A munkavállalókhoz az előző 90 nap munkanapjaira, ebédszünettel kettébontott és változatos megjegyzésekkel ellátott munkaidő-bejegyzések készülnek.
- A seedelés többször is biztonságosan lefuttatható: ugyanazokat a demó felhasználókat frissíti, a kezelt időszak bejegyzéseit pedig duplikáció nélkül állítja elő újra.
- A demó adatok kizárólag helyi és tesztkörnyezetben tölthetők be; production környezetben a seeder hibával leáll. Az éles rendszer első adminisztrátorát továbbra is a `php artisan worklog:create-admin` paranccsal kell létrehozni.
- A teljes demó adatkészlet a `php artisan migrate:fresh --seed` paranccsal, meglévő adatbázison pedig a `php artisan db:seed` paranccsal tölthető be.

## Tesztelés és elfogadási feltételek

Pest 3 feature- és unit tesztek fedik le:

- az adminisztrátor parancssori létrehozását és a nyilvános regisztrációk függő állapotát;
- az e-mail-cím egyediségét a felhasználók és kérelmek között;
- a jóváhagyást, elutasítást és a kérelmek törlését;
- admin route-ok elutasítását normál felhasználóknál;
- user letiltását, szerepkörváltását és az utolsó admin védelmét;
- saját és admin munkaidő CRUD-ot, valamint más felhasználó adatainak tiltását;
- átfedő, hibás, jövőbeli és éjfélen átnyúló idősávok elutasítását;
- napi/időszaki összesítéseket és dátumszűrőket;
- a demó seeder felhasználóit, 90 napos munkaidőadatait, újrafuttathatóságát és duplikációmentességét;
- saját, egyfelhasználós és összesített admin XLSX-export tartalmát;
- Inertia oldalak szükséges propjait és a szűrők megőrzését.

Ellenőrzés:

- célzott `php artisan test --compact` tesztek;
- teljes releváns Pest tesztcsomag;
- `vendor/bin/pint --dirty --format agent`;
- frontend formázás, ESLint, TypeScript/Vite production build;
- reszponzív, világos és sötét megjelenés kézi böngésző-ellenőrzése.

## Rögzített feltételezések

- A production adatbázis MySQL, a tesztek SQLite memóriadatbázison is futnak.
- Az alkalmazás időzónája `Europe/Budapest`, locale-ja `hu`, faker locale-ja `hu_HU`.
- A cél PHP-verzió 8.4; a jelenleg érzékelt PHP 8.2 Herd-környezetet implementálás előtt 8.4-re kell váltani, és a Composer-követelményt ehhez igazítani.
- Nincs külön nyilvános API, mobilalkalmazás, projektkezelés, jelenléti automata, bérszámfejtés vagy túlóraszámítás.
- Az Excel-generálás szinkron letöltés; a kis alkalmazás várható adatmennyisége nem indokol külön export jobot.
- A terv végleges elfogadása után ez a specifikáció képezi a projekt magyar nyelvű `README.md` dokumentációjának alapját.
