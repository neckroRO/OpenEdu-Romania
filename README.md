# OpenEdu-Romania

**O platformă educațională pentru învățământul românesc, construită în jurul înțelegerii, accesului la resurse și colaborării.**

OpenEdu-Romania își propune să sprijine elevii și profesorii prin materiale organizate după programa școlară, explicații alternative și, în etapele următoare, activități interactive și instrumente de lucru pentru școli.

Proiectul a pornit în 2020, în contextul pandemiei, din nevoia de continuitate a învățării. În 2026, dezvoltarea a fost reluată prin reconstruirea aplicației pe o arhitectură modernă, păstrând ideea de la care a pornit: **niciun copil să nu fie lăsat în urmă, iar învățarea să urmărească înțelegerea și aplicarea cunoștințelor.**

> **Milestone — octombrie 2026:** aplicația centrală are un flux funcțional de la navigarea în catalog până la crearea, moderarea și publicarea resurselor. Backendul și frontendul sunt integrate în `master`. Proiectul este în dezvoltare; acest milestone nu reprezintă încă platforma completă pentru școli sau un serviciu național lansat în producție.

## Cuprins

- [Viziune și principii](#viziune-și-principii)
- [Abordarea educațională](#abordarea-educațională)
- [Ce funcționează acum](#ce-funcționează-acum)
- [Arhitectură: conținut comun, date locale](#arhitectură-conținut-comun-date-locale)
- [Resurse și validare editorială](#resurse-și-validare-editorială)
- [Milestone și direcții de dezvoltare](#milestone-și-direcții-de-dezvoltare)
- [Tehnologii și structura repository-ului](#tehnologii-și-structura-repository-ului)
- [Pornire pentru dezvoltare](#pornire-pentru-dezvoltare)
- [Contribuții](#contribuții)
- [Q&A — întrebări și răspunsuri](#qa--întrebări-și-răspunsuri)
- [Licențiere și drepturile asupra materialelor](#licențiere-și-drepturile-asupra-materialelor)

## Viziune și principii

OpenEdu-Romania este gândit pentru elevi, profesori, părinți și instituții de învățământ. Beneficiarii principali sunt copiii.

Obiectivele proiectului sunt:

- **Continuitatea învățării:** un elev absent să poată accesa explicațiile și materialele relevante pentru ceea ce se studiază la clasă.
- **Înțelegere și aplicare:** explicații, exemple și activități care ajută elevul să folosească ceea ce învață.
- **Acces la explicații diferite:** același concept poate fi explicat în mai multe moduri, pentru nevoi și ritmuri diferite.
- **Conținut verificat:** resursele trec printr-un proces editorial înainte de publicare.
- **Autonomie pentru școli:** arhitectura urmărește separarea resurselor comune de datele gestionate de fiecare instituție.
- **Dezvoltare deschisă și transparentă:** codul, deciziile și progresul pot fi urmărite public.

Platforma completează activitatea de la clasă. Relația dintre elev și profesor, interacțiunea directă și viața școlară rămân esențiale.

## Abordarea educațională

Punctul de plecare pentru organizarea conținutului este **programa școlară din România**, cu competențele și conținuturile ei. Manualele avizate sunt resurse de referință; platforma nu trebuie să depindă de ordinea capitolelor sau de explicațiile unui singur manual.

În discuțiile proiectului, Estonia a fost aleasă ca reper european pentru explorarea unei abordări care pune accent pe înțelegere, autonomie și utilizarea instrumentelor digitale. Direcția urmărită este adaptarea unor practici potrivite la contextul românesc. Proiectarea concretă a lecțiilor și validarea pedagogică vor fi realizate împreună cu profesori.

Catalogul actual permite navigarea:

**Nivel educațional → Materie → Concept → Resurse asociate**

Un concept poate avea mai multe resurse: explicații, exemple, exerciții sau trimiteri către materiale externe. Resursele au metadate separate pentru dificultate și complexitate, astfel încât să poată fi căutate și filtrate.

Acoperirea integrală a programei este un obiectiv pe termen lung. Datele demonstrative actuale sunt limitate și nu reprezintă o programă completă sau validată oficial.

## Ce funcționează acum

| Componentă | Funcționalitate existentă |
| --- | --- |
| Catalog educațional | Navigare prin niveluri, materii și concepte, cu acces la resursele asociate |
| Catalog public de resurse | Căutare, filtrare, sortare și paginare |
| Pagina resursei | Conținut textual, rezumat, metadate, versiune și legătură către sursa externă, când există |
| Autentificare | Login, identificarea utilizatorului autentificat și logout prin Laravel Sanctum |
| Roluri și autorizare | Permisiuni pe backend și protejarea rutelor frontend în funcție de rol |
| Zona profesorului | Listarea resurselor accesibile, creare, editare și trimitere spre verificare |
| Zona de moderare | Coada de verificare, aprobare, respingere motivată și publicare |
| Versiuni editoriale | Gestionarea stărilor și a reviziilor resurselor |
| Contract API | Endpointuri sub `/api/v1`, validare, răspunsuri și erori structurate, identificator de cerere |
| Testare | Teste backend și frontend pentru contracte, autorizare, catalog și fluxuri editoriale |

Accesul public este limitat la resurse active cu versiune publicată. Autorizarea operațiilor editoriale este verificată pe server.

Rolurile definite sunt `learner`, `guardian`, `teacher`, `moderator` și `admin`. Definirea rolurilor de elev și părinte nu înseamnă că există deja module complete dedicate acestora.

Exemplul demonstrativ inclus în proiect este **Clasa a VII-a → Matematică → Algebră → Ecuații de gradul I**, cu o resursă explicativă.

## Arhitectură: conținut comun, date locale

Arhitectura țintă separă două responsabilități:

| Componentă | Responsabilitate | Stadiu |
| --- | --- | --- |
| OpenEdu Central | Catalog curricular, resurse educaționale, contribuții și moderare | Nucleu funcțional |
| Instanțele locale ale școlilor | Utilizatori locali, elevi, clase și activități ale instituției | Planificat |
| Integrarea central–local | Distribuirea și actualizarea resurselor comune către instanțe | Planificat |

Ideea este ca instituțiile să poată utiliza aceeași bază de resurse, păstrând controlul asupra propriilor date și utilizatori. Modelul de instalare, sincronizarea și izolarea datelor trebuie implementate și verificate înaintea utilizării în școli.

Separarea central–local este o decizie de arhitectură. Conformitatea GDPR va necesita și reguli de acces, retenție, responsabilități, proceduri și verificări dedicate.

## Resurse și validare editorială

Fluxul implementat pentru versiunile resurselor este:

- **Ciornă (`draft`) → trimisă (`submitted`) → aprobată (`approved`) → publicată (`published`).**
- O versiune trimisă poate fi **respinsă (`rejected`)**, cu o explicație, și readusă în ciornă pentru corectare.
- Modificarea conținutului publicat se face printr-o versiune nouă, care parcurge procesul editorial.

Profesorii contribuie cu materiale; moderatorii și administratorii au permisiuni pentru verificare și publicare. Dreptul de editare este controlat și în funcție de autorul resursei.

Validarea prin participarea unei comunități de profesori, vot și reputație rămâne o direcție de dezvoltare. Fluxul actual folosește roluri și aprobare editorială.

## Milestone și direcții de dezvoltare

### Etape parcurse

| Capitol | Rezultat |
| --- | --- |
| 00 — Viziune și cerințe | Clarificarea scopului și a limitelor primei versiuni |
| 01 — Auditul repository-ului din 2020 | Evaluarea prototipului și decizia de reconstruire |
| 02–04 — Arhitectură, model de date, roluri | Fundamentarea separării central–local și implementarea bazei necesare aplicației centrale |
| 05.1–05.6 — Backend API | Fundație, contract API, catalog, workflow editorial, autentificare, autorizare și căutare |
| 06.1–06.8 — Frontend | Interfață, catalog, resurse, căutare, autentificare, zona profesorului, moderare și teste |

Implementările backend și frontend sunt urmărite în [PR-urile integrate](https://github.com/neckroRO/OpenEdu-Romania/pulls?q=is%3Apr+is%3Amerged). Numerotarea capitolelor urmărește planul de lucru; nu indică finalizarea tuturor funcțiilor din viziunea pe termen lung.

### Ce urmează

- **Curriculum și lecții:** maparea programei, extinderea conținutului și validarea cu profesori.
- **Instanțe locale:** administrarea instituțiilor, elevilor și claselor, integrarea cu OpenEdu Central.
- **Activități educaționale:** exerciții interactive, quiz-uri și colaborare în grup.
- **Deployment:** instalare reproductibilă, configurare pentru producție, backup și monitorizare.
- **Securitate și GDPR:** verificări dedicate pentru folosirea cu date reale și în instituții.
- **Testare extinsă:** scenarii complete de utilizare și pilot cu feedback de la profesori și elevi.
- **AI:** etapă ulterioară, amânată pentru prima versiune; un eventual modul va fi opțional și va necesita evaluare separată.

Aplicația mobilă dedicată, grupurile de lucru, votul/reputația profesorilor și modulele AI nu sunt implementate în milestone-ul actual.

## Tehnologii și structura repository-ului

| Zonă | Tehnologii |
| --- | --- |
| Backend central | PHP, Laravel 13, Laravel Sanctum, Eloquent |
| Bază de date pentru dezvoltare | PostgreSQL 18 în Docker |
| Frontend central | Vue 3, TypeScript, Vite, Vue Router, Pinia |
| Teste backend | PHPUnit; configurația actuală folosește SQLite în memorie |
| Teste frontend | Vitest, Vue Test Utils, jsdom |

| Director / fișier | Rol |
| --- | --- |
| `apps/central/backend/` | API, modele, migrări, seedere și teste backend |
| `apps/central/frontend/` | Interfața aplicației centrale și teste frontend |
| `infra/compose.dev.yml` | Serviciul PostgreSQL pentru dezvoltare |
| `infra/.env.example` | Exemplu de configurare a bazei de date |
| `admin/`, `index.php`, `connection.php`, `openedu.sql` | Prototipul istoric din 2020 |

Aplicația actuală se dezvoltă în `apps/central/`. Fișierele prototipului istoric nu reprezintă punctul de pornire al noii aplicații.

## Pornire pentru dezvoltare

Instrucțiunile de mai jos sunt pentru un mediu local nou. Sunt necesare Git, Docker cu Compose, PHP și Composer, Node.js și npm. Folosește versiuni compatibile cu fișierele de dependențe; mediul de lucru al proiectului a utilizat PHP 8.5 și Node.js 24. PHP are nevoie de extensiile cerute de Composer, inclusiv driverul PostgreSQL; testele backend necesită și SQLite.

### 1. Repository și baza de date

```bash
git clone https://github.com/neckroRO/OpenEdu-Romania.git
cd OpenEdu-Romania
cp infra/.env.example infra/.env
```

În `infra/.env`, setează o parolă proprie pentru `POSTGRES_PASSWORD`, apoi pornește baza de date:

```bash
docker compose --env-file infra/.env -f infra/compose.dev.yml up -d
```

### 2. Backend

```bash
cd apps/central/backend
composer install
cp .env.example .env
php artisan key:generate
```

În fișierul backend `.env`, configurează conexiunea PostgreSQL cu aceleași valori din `infra/.env`:

```dotenv
APP_NAME=OpenEdu-Romania
APP_URL=http://localhost:8000
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=openedu
DB_USERNAME=openedu
DB_PASSWORD=parola_aleasa
```

Apoi:

```bash
php artisan migrate
php artisan db:seed --class=OpenEduMvpSeeder
php artisan serve --host=127.0.0.1 --port=8000
```

Seederul `OpenEduMvpSeeder` creează exemplul educațional demonstrativ. Nu creează conturi pentru login; utilizatorii și rolurile pentru testarea editorială se configurează separat în mediul de dezvoltare.

### 3. Frontend

Într-un terminal separat, din rădăcina repository-ului:

```bash
cd apps/central/frontend
npm ci
npm run dev -- --host 127.0.0.1
```

Interfața este disponibilă, în configurația implicită, la `http://localhost:5173`, iar API-ul la `http://localhost:8000/api/v1`. Vite redirecționează cererile `/api` către backendul local.

Aceste comenzi pornesc servere de dezvoltare. Publicarea în producție va avea o configurație dedicată.

### 4. Verificări

Backend, din `apps/central/backend/`:

```bash
php artisan test
```

Frontend, din `apps/central/frontend/`:

```bash
npm run test:run
npm run build
```

Testele automate acoperă părți esențiale ale aplicației, dar folosirea PostgreSQL în dezvoltare și SQLite în testele backend impune și verificări pe baza de date țintă.

## Contribuții

Proiectul are nevoie de dezvoltatori, profesori, elevi, părinți și parteneri care pot contribui cu experiență, feedback sau resurse.

Contribuțiile pot include:

- dezvoltare și remedierea problemelor;
- maparea programei și realizarea de materiale educaționale;
- verificare pedagogică și editorială;
- testare, accesibilitate și feedback despre experiența de utilizare;
- documentație și sprijin pentru implementarea în instituții.

Pentru o propunere sau o problemă, deschide un [issue](https://github.com/neckroRO/OpenEdu-Romania/issues). Modificările de cod se propun prin pull request, cu o descriere a schimbării și a verificărilor efectuate.

README-ul, documentația și descrierile publice ale contribuțiilor sunt în română. Identificatorii și denumirile tehnice din cod pot rămâne în engleză.

## Q&A — întrebări și răspunsuri

Această secțiune păstrează întrebările de la începutul proiectului, cu răspunsuri actualizate pentru direcția și stadiul din 2026.

### Ce se dorește prin OpenEdu-Romania?

O platformă educațională adaptată nevoilor învățământului din România, care poate fi utilizată de elevi și profesori și, în arhitectura țintă, instalată pentru fiecare școală. Resursele educaționale comune sunt gestionate central, iar datele și activitățile instituției sunt gestionate local. În prezent este funcțional nucleul web al aplicației centrale.

### De ce un proiect propriu, când există deja platforme educaționale?

Pentru a construi o soluție în jurul programei românești, a explicațiilor verificate și a autonomiei instituțiilor, cu dezvoltare transparentă și orientată spre beneficiul copiilor.

Motivația inițială rămâne: proiectul nu trebuie folosit ca pretext pentru cheltuieli netransparente sau pentru interese care îndepărtează platforma de scopul educațional. Costurile reale de dezvoltare, infrastructură și întreținere trebuie explicate transparent. Publicarea codului și licențierea sunt lucruri distincte; statutul licenței este precizat mai jos.

### Nu riscăm ca elevii să devină „roboți” și să piardă interacțiunea socială?

Platforma trebuie să sprijine relația cu profesorul și colaborarea dintre elevi. Un copil bolnav poate recupera explicațiile de acasă, iar activitățile digitale pot susține lucrul în grup. Direcția pedagogică urmărește înțelegerea și implicarea activă. Instrumentele de colaborare în grup sunt încă planificate.

### Urmează să desființăm școlile sau să mutăm totul online? Este doar pentru situații de urgență?

Nu. OpenEdu-Romania completează școala și poate fi util atât în situații de întrerupere a participării la clasă, cât și pentru recapitulare, aprofundare și pregătire. Profesorul și interacțiunea directă rămân parte esențială a învățării.

### Există deja manuale.edu.ro. Ce aduce în plus?

Viziunea proiectului leagă conceptele din programă de explicații alternative, exemple, resurse suplimentare și activități interactive. Catalogul și fluxul editorial sunt implementate; jocurile, quiz-urile și grupurile de lucru sunt etape viitoare.

### De cine este nevoie pentru a dezvolta platforma?

De dezvoltatori, profesori, elevi, părinți și entități partenere. Profesorii sunt esențiali pentru corectitudinea și utilitatea materialelor, iar elevii și părinții pentru feedback. Ideea inițială a pornit din comunitatea „Grupul IT-știlor” de pe Discord.

### Se va prelucra doar materia considerată importantă sau esențială?

Obiectivul este acoperirea programei, fără eliminarea arbitrară a unor conținuturi. Materialele suplimentare pot aprofunda sau explica diferit conceptele. Catalogul actual este demonstrativ și nu acoperă încă toate clasele și materiile.

### Există un singur manual pentru fiecare materie?

În discuția inițială, proiectul era descris în jurul unui manual comun. Direcția actuală ține cont de existența manualelor alternative avizate: programa este reperul de organizare, iar manualele pot oferi explicații și succesiuni diferite. Platforma urmărește conceptele și competențele, astfel încât să poată susține mai multe abordări ale aceluiași conținut.

### De ce ne-am uitat la Estonia?

Pentru a explora un reper european apropiat de contextul nostru și idei care pot ajuta la proiectarea experienței de învățare. Programa românească rămâne baza conținutului. Adaptarea pedagogică este o direcție de lucru care trebuie concretizată și evaluată cu profesori.

### Cine verifică materialele înainte de publicare?

În implementarea actuală, contribuțiile parcurg verificarea moderatorilor sau administratorilor. Aprobarea și publicarea sunt operații distincte. Un sistem comunitar de vot și reputație pentru profesori este planificat.

### Va avea inteligență artificială?

AI a fost amânat pentru prima versiune. Prioritatea este o bază de conținut verificat și fluxuri educaționale funcționale. Un eventual modul AI va fi opțional și nu va publica automat materiale fără validare.

### Sunt deja implementate clasele, catalogul de note sau aplicația mobilă?

Nu. Milestone-ul actual privește aplicația centrală de resurse. Modulele școlilor și o aplicație mobilă dedicată sunt dezvoltări ulterioare.

### Proiectul are avizul Ministerului Educației?

README-ul inițial menționa posibilitatea unui aviz ca obiectiv. Acest document nu revendică un aviz, o acreditare sau o adoptare oficială. Orice astfel de statut va fi anunțat numai în baza unei confirmări documentate.

## Licențiere și drepturile asupra materialelor

Intenția proiectului este dezvoltarea open-source. La data acestei actualizări, repository-ul nu conține un fișier `LICENSE` la rădăcină care să stabilească licența întregului proiect. Alegerea și publicarea acesteia rămân de clarificat; licențele componentelor și dependențelor trebuie respectate separat.

Materialele educaționale au propriile drepturi de autor. Disponibilitatea online a unui manual sau a unui material nu acordă automat dreptul de a-l copia și redistribui. Contribuțiile trebuie să indice sursa și dreptul de utilizare; politica de licențiere a conținutului va fi definită separat de cea a codului.

---

**Stadiu documentat: 7 octombrie 2026 — nucleul central funcțional, după integrarea etapelor backend 05.1–05.6 și frontend 06.1–06.8.**
