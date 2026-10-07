# OpenEdu-Romania

> **Aceeași lecție. Mai multe moduri de a o înțelege.**

**OpenEdu-Romania este o platformă educațională construită pentru elevii care au nevoie de o explicație diferită pentru a înțelege o lecție.**

Un profesor poate explica excelent pentru o parte dintre elevi și mai puțin potrivit pentru alții. Alt profesor poate folosi alte exemple, o reprezentare vizuală, un video, un exercițiu interactiv sau pur și simplu alte cuvinte.

OpenEdu își propune să aducă aceste explicații împreună.

Nu căutăm o singură „lecție perfectă”. Construim o platformă în care **mai mulți profesori pot crea abordări diferite ale aceleiași teme**, iar elevul poate găsi explicația care îl ajută să înțeleagă.

Proiectul a pornit în 2020, în contextul pandemiei, și a fost reluat în 2026 prin reconstruirea completă a aplicației pe o arhitectură modernă.

Principiul de la care proiectul nu se abate este simplu:

> **Niciun copil nu ar trebui să rămână în urmă doar pentru că prima explicație nu a fost cea potrivită pentru el.**

---

## Stadiul proiectului

OpenEdu-Romania este în dezvoltare activă.

În octombrie 2026, aplicația centrală dispune deja de un nucleu funcțional care include:

- catalog educațional;
- structură curriculară;
- concepte și resurse educaționale;
- model de lecții;
- versiuni editoriale;
- flux profesor → moderare → publicare;
- validare pedagogică și reputație;
- autentificare și roluri;
- administrarea utilizatorilor centrali;
- interfață web funcțională;
- API versionat;
- suite automate de teste backend și frontend.

La momentul acestei actualizări:

- **217 teste backend** trec cu succes;
- **128 teste frontend** trec cu succes;
- build-ul frontend este verificat;
- fluxurile principale au fost testate și manual în browser.

Acesta este un milestone de dezvoltare, nu o lansare publică și nu reprezintă încă o platformă pregătită pentru utilizarea cu date reale ale elevilor.

---

## Cuprins

- [De ce există OpenEdu](#de-ce-există-openedu)
- [Cum vede OpenEdu o lecție](#cum-vede-openedu-o-lecție)
- [Mai multe explicații pentru aceeași temă](#mai-multe-explicații-pentru-aceeași-temă)
- [Curriculum flexibil și colaborativ](#curriculum-flexibil-și-colaborativ)
- [Validare și contribuții](#validare-și-contribuții)
- [Arhitectură: OpenEdu Central și OpenEdu Local](#arhitectură-openedu-central-și-openedu-local)
- [Ce funcționează acum](#ce-funcționează-acum)
- [Roadmap](#roadmap)
- [Editorul de lecții](#editorul-de-lecții)
- [Tehnologii](#tehnologii)
- [Structura repository-ului](#structura-repository-ului)
- [Pornire pentru dezvoltare](#pornire-pentru-dezvoltare)
- [Contribuții](#contribuții)
- [Q&A](#qa)
- [Licențiere și drepturile asupra materialelor](#licențiere-și-drepturile-asupra-materialelor)

---

## De ce există OpenEdu

OpenEdu a pornit de la o situație foarte simplă.

Un copil participă la o lecție.

Profesorul explică.

Copilul nu înțelege.

Ajunge acasă și încearcă să găsească o explicație diferită.

Problema nu înseamnă neapărat că profesorul a explicat greșit sau că elevul nu poate înțelege. Uneori pur și simplu **modul în care a fost prezentată informația nu a rezonat cu acel elev**.

De aici pornește OpenEdu.

Pentru aceeași temă pot exista:

- o explicație foarte vizuală;
- o explicație pas cu pas;
- o explicație bazată pe exemple practice;
- o explicație video;
- o abordare bazată pe exerciții;
- o prezentare foarte scurtă pentru recapitulare;
- o lecție mai amplă pentru aprofundare.

Toate pot fi corecte.

Toate pot respecta aceeași programă.

Dar una dintre ele poate face diferența pentru un anumit copil.

OpenEdu nu urmărește să înlocuiască profesorul sau școala.

**OpenEdu oferă elevului încă o șansă să înțeleagă.**

---

## Cum vede OpenEdu o lecție

Structura urmărită separă trei lucruri care nu trebuie confundate:

```text
Concept curricular
        ↓
Lecții / explicații alternative
        ↓
Versiuni editoriale ale fiecărei lecții
```

De exemplu:

```text
Concept: Fracții
│
├── Lecție: Fracțiile explicate vizual
│   ├── versiunea 1
│   └── versiunea 2
│
├── Lecție: Fracțiile prin exemple din viața reală
│   └── versiunea 1
│
├── Lecție: Fracțiile pas cu pas
│   ├── versiunea 1
│   └── versiunea 2
│
└── Lecție: Fracțiile prin video și exerciții
    └── versiunea 1
```

Profesorii nu trebuie să se suprascrie unii pe alții.

Un profesor poate crea propria explicație pentru un concept deja existent.

Fiecare lecție are propriul autor, propriul conținut și propriul istoric de revizii.

Astfel, diversitatea explicațiilor devine o caracteristică a platformei, nu o problemă care trebuie eliminată.

---

## Mai multe explicații pentru aceeași temă

Elevul nu trebuie să primească doar o listă de fișiere.

Experiența urmărită este mult mai apropiată de:

```text
Fracții

Nu ai înțeles?
Încearcă o altă explicație.

→ Explicație vizuală
→ Pas cu pas
→ Exemple practice
→ Video + exerciții
→ Recapitulare rapidă
```

În viitor, lecțiile vor putea fi descrise prin caracteristici precum:

- vizuală;
- pas cu pas;
- bazată pe exemple;
- practică;
- video;
- interactivă;
- recapitulare;
- aprofundare.

Aceste caracteristici nu definesc „tipuri de copii”.

Ele descriu pur și simplu **moduri diferite de a explica același lucru**.

O explicație mai puțin populară nu trebuie eliminată automat. Poate fi exact explicația care funcționează pentru un anumit elev.

---

## Curriculum flexibil și colaborativ

Punctul de referință pentru organizarea conținutului este programa școlară.

Manualele sunt resurse importante, dar OpenEdu nu trebuie să depindă de ordinea sau formularea unui singur manual.

Modelul urmărit separă:

```text
Nivel / clasă
    ↓
Context curricular
    ↓
Materie
    ↓
Domeniu
    ↓
Concept / temă
    ↓
Lecții și resurse
```

### Concepte canonice

Platforma trebuie să evite apariția unor structuri precum:

```text
Clasa a VIII-a
Clasa VIII
Clasa a 8-a
VIII
```

sau:

```text
Limba română
Lb. română
Limba și literatura română
```

acolo unde denumirile reprezintă în realitate același lucru.

OpenEdu va utiliza:

- entități canonice;
- denumiri normalizate;
- aliasuri;
- căutare după denumiri alternative;
- propuneri de unificare;
- audit al modificărilor.

Aliasurile nu sunt considerate date inutile.

Dacă `Lb. română` este un alias pentru `Limba și literatura română`, căutarea după termenul folosit de profesor trebuie să ducă în continuare la obiectul corect.

### Propuneri din partea profesorilor

Profesorii nu trebuie limitați exclusiv la structura existentă.

Ei vor putea propune:

- o clasă / un nivel nou;
- o materie nouă;
- un domeniu;
- un concept sau o temă;
- modificarea unei denumiri;
- unificarea a două elemente care par duplicate.

O propunere nu modifică automat nomenclatorul canonic.

Ea intră într-un workflow de validare.

Moderatorul sau administratorul poate:

- aproba;
- respinge motivat;
- cere modificări;
- corecta înainte de aprobare;
- lega propunerea de un element existent;
- aproba un merge.

Profesorul trebuie să poată continua lucrul chiar dacă propunerea este încă în analiză. La aprobarea sau unificarea acesteia, relațiile vor putea fi mutate către obiectul canonic.

### Variante și contexte educaționale

Sistemul nu trebuie să conțină în cod o listă rigidă de metode sau alternative educaționale.

Modelul trebuie să permită definirea de contexte educaționale noi fără modificarea aplicației.

În interfață, un utilizator va putea indica opțional că o structură aparține unei variante educaționale față de programul de bază.

Terminologia canonică trebuie să rămână neutră și suficient de generală pentru utilizare națională.

Denumirile locale pot exista ca aliasuri sau etichete de prezentare.

### Fără duplicarea inutilă a curriculumului

O variantă educațională nu trebuie să ducă automat la duplicarea tuturor conceptelor.

Dacă un concept este comun:

```text
Fracții
```

el trebuie să rămână un singur concept.

Același concept poate avea mai multe plasări sau contexte curriculare.

Diferențele specifice unui anumit program trebuie descrise doar acolo unde există cu adevărat.

---

## Validare și contribuții

OpenEdu este construit în jurul contribuției profesorilor, dar publicarea conținutului trebuie să rămână controlată.

Fluxul editorial actual include:

```text
draft
  ↓
submitted
  ↓
approved
  ↓
published
```

O contribuție poate fi și:

```text
submitted
  ↓
rejected
  ↓
draft
```

cu feedback din partea moderatorului.

Modificarea unei resurse publicate nu trebuie să distrugă versiunea existentă.

Noua variantă parcurge propriul proces editorial.

Pe termen lung, reputația contributorilor și istoricul validărilor pot ajuta la prioritizarea procesului editorial, fără a elimina verificarea umană pentru modificările importante.

Pentru fiecare modificare structurală trebuie păstrat un audit:

- cine a propus-o;
- cine a analizat-o;
- ce s-a modificat;
- când;
- de ce;
- ce relații au fost afectate.

Merge-urile trebuie să fie inspectabile și proiectate astfel încât istoricul să nu fie pierdut.

---

## Arhitectură: OpenEdu Central și OpenEdu Local

OpenEdu este proiectat în jurul separării dintre **conținutul educațional comun** și **datele elevilor și ale instituțiilor**.

### OpenEdu Central

OpenEdu Central gestionează:

- curriculumul;
- conceptele;
- lecțiile;
- resursele;
- versiunile;
- contribuțiile profesorilor;
- validarea;
- publicarea;
- distribuirea conținutului educațional.

### OpenEdu Local

O instanță locală a unei școli va gestiona:

- elevii;
- tutorii;
- clasele instituției;
- activitățile locale;
- progresul elevilor;
- răspunsurile la quiz-uri;
- rezultate;
- date interne ale instituției.

Principiul urmărit este:

> **Conținutul educațional poate fi comun. Datele copilului rămân în responsabilitatea instituției care le gestionează.**

Integrarea Central–Local, izolarea datelor, retenția, backup-ul, securitatea și conformitatea GDPR vor necesita implementare și verificare dedicate înaintea utilizării cu date reale.

---

## Ce funcționează acum

| Componentă | Stadiu |
| --- | --- |
| Catalog educațional | Funcțional |
| Niveluri, materii și concepte | Funcțional |
| Import curricular | Implementat |
| Resurse asociate conceptelor | Funcțional |
| Catalog public | Funcțional |
| Căutare, filtrare, sortare și paginare | Funcțional |
| Pagina publică a resursei | Funcțional |
| Modelul lecțiilor | Implementat |
| Relații lecție–concept | Implementate |
| Relații între versiuni de lecție și resurse | Implementate |
| Autentificare | Funcțională |
| Roluri și autorizare | Funcționale |
| Zona profesorului | Funcțională |
| Workflow editorial | Funcțional |
| Moderare | Funcțională |
| Publicare | Funcțională |
| Validare pedagogică | Implementată |
| Model de reputație | Implementat |
| Administrarea utilizatorilor centrali | Funcțională |
| Activare / dezactivare conturi | Funcțională |
| Resetarea parolelor de către administrator | Funcțională |
| Protecția rutelor frontend | Funcțională |
| API `/api/v1` | Funcțional |
| Testare backend | 217 teste |
| Testare frontend | 128 teste |
| Build frontend | Funcțional |

Rolurile definite în prezent sunt:

```text
learner
guardian
teacher
moderator
admin
```

Rolurile de elev și tutore există în model, dar modulele complete OpenEdu Local nu sunt încă implementate.

---

## Roadmap

Roadmap-ul descrie direcția actuală a proiectului și poate evolua pe măsură ce deciziile tehnice și pedagogice sunt validate.
Roadmap-ul nu reprezintă doar o listă de funcționalități, ci ordinea în care OpenEdu evoluează de la un nucleu editorial funcțional către un ecosistem educațional complet.

### Fundația proiectului — realizată

#### 00 — Viziune și cerințe

- definirea scopului;
- separarea dintre produs și prototipul din 2020;
- stabilirea beneficiarilor;
- principii educaționale.

#### 01 — Auditul prototipului 2020

- audit repository;
- evaluarea codului vechi;
- decizia de reconstruire.

#### 02 — Arhitectură

- OpenEdu Central;
- OpenEdu Local;
- separarea responsabilităților;
- stack tehnologic;
- structură repository.

#### 03 — Model de date

- entități principale;
- relații;
- schema relațională.

#### 04 — Autentificare, roluri și permisiuni

- utilizatori;
- autentificare;
- roluri;
- autorizare.

#### 05 — Backend API

- API versionat;
- catalog;
- resurse;
- workflow editorial;
- căutare;
- autorizare.

#### 06 — Frontend Central

- catalog;
- pagini de resurse;
- căutare;
- autentificare;
- zona profesorului;
- moderare;
- route guards;
- testare.

---

### 07 — Curriculum, lecții și comunitatea profesorilor

#### Implementat până acum

- model curricular;
- import curricular;
- model de lecții;
- asocierea conceptelor;
- resursele versiunilor de lecție;
- consumul public;
- validarea pedagogică;
- reputația contributorilor;
- administrarea utilizatorilor centrali;
- conturi demonstrative pentru dezvoltare.

#### 07.7 — Curriculum colaborativ și deduplicare

Direcția următoare:

- propuneri pentru clase / niveluri;
- propuneri pentru materii;
- propuneri pentru domenii;
- propuneri pentru concepte;
- modificări propuse;
- workflow de revizuire;
- solicitare de corecturi;
- propuneri de merge;
- aliasuri;
- normalizarea denumirilor;
- detectarea duplicatelor;
- preview al efectelor unui merge;
- audit complet;
- păstrarea istoricului;
- contexte și variante educaționale;
- terminologie locală prin aliasuri;
- posibilitatea de a continua lucrul pe propuneri aflate încă în moderare.

---

### 08 — Editor avansat de lecții

Editorul de lecții trebuie să devină una dintre componentele centrale ale OpenEdu.

Nu este gândit ca un simplu câmp de text.

Direcția este un **editor modular pe blocuri**, construit special pentru conținut educațional.

Blocurile planificate includ:

#### Conținut

- text formatat;
- titluri;
- subtitluri;
- liste;
- tabele;
- citate;
- casete informative;
- „De reținut”;
- exemple;
- observații.

#### Multimedia

- imagini;
- galerii;
- audio;
- video;
- player YouTube;
- resurse externe.

#### Matematică și științe

- formule;
- ecuații;
- grafice;
- funcții;
- tabele de valori;
- elemente geometrice;
- diagrame;
- reprezentări interactive.

#### Activități

- exerciții;
- exemple rezolvate;
- indicii;
- soluții;
- alegere multiplă;
- adevărat / fals;
- răspuns liber;
- asociere;
- ordonare;
- quiz-uri.

#### Experiența editorului

- drag & drop;
- mutarea blocurilor;
- duplicarea blocurilor;
- preview;
- mod elev;
- previzualizare desktop / tabletă / telefon;
- salvare draft;
- versionare;
- template-uri de lecție.

Profesorul nu trebuie să cunoască HTML, CSS sau programare.

Platforma trebuie să permită unui profesor să creeze o lecție atractivă concentrându-se exclusiv pe conținut și pedagogie.

---

### 09 — Explicații alternative și experiența elevului

Aceeași temă trebuie să poată avea mai multe lecții independente.

Profesorii nu editează implicit lecțiile altor profesori.

Ei pot crea propriile explicații pentru același concept.

Planificat:

- mai multe lecții pentru același concept;
- autori distincți;
- versiuni distincte pentru fiecare lecție;
- descrierea abordării;
- estimarea timpului;
- nivel de dificultate;
- resurse necesare;
- „Încearcă altă explicație”;
- navigare între abordări;
- feedback privind utilitatea unei explicații;
- recomandarea unei abordări diferite atunci când elevul dorește o alternativă;
- recapitulare;
- aprofundare.

Scopul nu este alegerea unei singure lecții câștigătoare.

Scopul este ca elevul să poată găsi **o explicație care funcționează pentru el**.

---

### 10 — Activități interactive și evaluare

Planificat:

- quiz-uri integrate în lecții;
- exerciții interactive;
- feedback imediat;
- încercări multiple;
- explicații după răspuns;
- seturi de exerciții;
- evaluări formative;
- progres;
- recapitulări;
- activități de grup.

Conținutul activităților poate fi distribuit central.

Răspunsurile și progresul elevilor vor aparține instanțelor locale.

---

### 11 — OpenEdu Local

Planificat:

- instituții;
- clase;
- elevi;
- tutori;
- profesori locali;
- înscrieri;
- administrarea claselor;
- progres;
- activități;
- rezultate;
- setări ale instituției;
- permisiuni locale.

OpenEdu Local trebuie proiectat astfel încât școala să își gestioneze propriile date fără a transforma OpenEdu Central într-o bază națională de date cu informațiile personale ale tuturor elevilor.

---

### 12 — Sincronizare Central–Local

Planificat:

- distribuirea curriculumului;
- distribuirea lecțiilor;
- actualizarea resurselor;
- versiuni;
- sincronizare incrementală;
- rezolvarea conflictelor;
- funcționare controlată în condiții de conectivitate limitată;
- verificarea integrității datelor.

---

### 13 — Securitate și protecția datelor

Înaintea utilizării reale cu date ale copiilor vor fi necesare:

- threat modeling;
- control riguros al accesului;
- audit;
- protecția sesiunilor;
- rate limiting;
- politici de parole;
- HTTPS;
- backup;
- restaurare;
- retenție;
- ștergere;
- jurnalizare;
- separarea datelor între instituții;
- revizuire GDPR;
- testare de securitate.

---

### 14 — Deployment și operare

Planificat:

- instalare reproductibilă;
- configurare pentru producție;
- containere;
- managementul configurației;
- migrații controlate;
- backup automat;
- monitorizare;
- loguri;
- health checks;
- proceduri de upgrade;
- documentație pentru administratori.

---

### 15 — Accesibilitate și experiență multi-device

Planificat:

- WCAG;
- navigare completă din tastatură;
- suport pentru cititoare de ecran;
- contrast;
- fonturi și dimensiuni accesibile;
- responsive design;
- telefon;
- tabletă;
- desktop;
- optimizare pentru conexiuni lente.

---

### 16 — Pilot și extindere

Înaintea unei lansări largi:

- pilot cu profesori;
- pilot cu elevi;
- feedback de la părinți;
- analizarea experienței reale;
- corectarea problemelor;
- documentație pentru școli;
- onboarding;
- măsurarea impactului;
- extindere graduală.

---

## Editorul de lecții

Editorul reprezintă una dintre direcțiile cu cea mai mare prioritate pentru experiența finală a OpenEdu.

Exemplu conceptual:

```text
┌───────────────────────────────────────────────────────┐
│ Lecția: Teorema lui Pitagora           Preview       │
├─────────────┬─────────────────────────┬───────────────┤
│ ELEMENTE    │                         │ PROPRIETĂȚI   │
│             │        LECȚIA           │               │
│ Text        │                         │ Dimensiune    │
│ Imagine     │ [ Introducere ]         │ Aliniere      │
│ Video       │                         │ Dificultate   │
│ Formulă     │ [ Video ]               │ ...           │
│ Grafic      │                         │               │
│ Geometrie   │ [ Explicație ]          │               │
│ Quiz        │                         │               │
│ Exercițiu   │ [ Quiz ]                │               │
└─────────────┴─────────────────────────┴───────────────┘
```

Lecțiile nu vor fi stocate ca pagini HTML arbitrare.

Direcția tehnică urmărită este reprezentarea structurată a blocurilor, astfel încât același conținut să poată fi randat în mai multe contexte și să poată evolua independent de designul interfeței.

Exemplu conceptual:

```json
{
  "blocks": [
    {
      "type": "paragraph",
      "data": {}
    },
    {
      "type": "youtube",
      "data": {}
    },
    {
      "type": "geometry",
      "data": {}
    },
    {
      "type": "quiz",
      "data": {}
    }
  ]
}
```

Conținutul introdus de utilizatori nu trebuie să permită executarea arbitrară de HTML sau JavaScript.

---

## Tehnologii

| Zonă | Tehnologii |
| --- | --- |
| Backend central | PHP, Laravel 13, Laravel Sanctum, Eloquent |
| Bază de date dezvoltare | PostgreSQL 18 |
| Frontend central | Vue 3, TypeScript, Vite, Vue Router, Pinia |
| Testare backend | PHPUnit |
| Testare frontend | Vitest, Vue Test Utils, jsdom |
| Mediu DB teste backend | SQLite în memorie |
| Infrastructură dezvoltare | Docker Compose |

Arhitectura și tehnologiile pot evolua atunci când există un motiv tehnic clar pentru schimbare.

---

## Structura repository-ului

| Director / fișier | Rol |
| --- | --- |
| `apps/central/backend/` | API, modele, migrări, seedere și teste backend |
| `apps/central/frontend/` | Interfața OpenEdu Central și testele frontend |
| `infra/compose.dev.yml` | PostgreSQL pentru dezvoltare |
| `infra/.env.example` | Configurație exemplu pentru infrastructura locală |

Prototipul OpenEdu din 2020 rămâne disponibil în istoricul Git al repository-ului.

---

## Pornire pentru dezvoltare

Sunt necesare:

- Git;
- Docker + Compose;
- PHP;
- Composer;
- Node.js;
- npm.

Mediul actual de dezvoltare a utilizat PHP 8.5 și Node.js 24.

### 1. Clonează repository-ul

```bash
git clone https://github.com/neckroRO/OpenEdu-Romania.git
cd OpenEdu-Romania
cp infra/.env.example infra/.env
```

Configurează `POSTGRES_PASSWORD` în:

```text
infra/.env
```

Apoi:

```bash
docker compose \
  --env-file infra/.env \
  -f infra/compose.dev.yml \
  up -d
```

### 2. Backend

```bash
cd apps/central/backend
composer install
cp .env.example .env
php artisan key:generate
```

Exemplu de configurare:

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
php artisan db:seed --class=OpenEduDevelopmentUsersSeeder

php artisan serve --host=127.0.0.1 --port=8000
```

`OpenEduMvpSeeder` creează date educaționale demonstrative.

`OpenEduDevelopmentUsersSeeder` creează conturi destinate exclusiv dezvoltării locale.

| Rol | E-mail | Parolă inițială |
| --- | --- | --- |
| Administrator | `admin@exemplu.ro` | `admin` |
| Moderator | `moderator@exemplu.ro` | `moderator` |
| Profesor | `profesor@exemplu.ro` | `profesor` |
| Elev | `elev@exemplu.ro` | `elev` |
| Tutore | `tutore@exemplu.ro` | `tutore` |

> **Important:** aceste credențiale sunt exclusiv pentru dezvoltare. Nu trebuie utilizate într-un mediu public sau de producție. Parolele inițiale trebuie schimbate atunci când este necesar.

Seederul refuză rularea în mediul `production`.

Rerularea lui nu înlocuiește parolele deja schimbate.

### 3. Frontend

Într-un terminal separat:

```bash
cd apps/central/frontend
npm ci
npm run dev -- --host 127.0.0.1
```

Implicit:

```text
Frontend: http://localhost:5173
API:      http://localhost:8000/api/v1
```

Vite redirecționează cererile `/api` către backendul local.

### 4. Teste backend

```bash
cd apps/central/backend
php artisan test
```

### 5. Teste și build frontend

```bash
cd apps/central/frontend
npm run test:run
npm run build
```

Testarea automată nu înlocuiește verificarea pe PostgreSQL și smoke testele în browser.

---

## Contribuții

OpenEdu are nevoie de mai mult decât programatori.

Sunt binevenite contribuțiile:

- profesorilor;
- elevilor;
- părinților;
- dezvoltatorilor;
- specialiștilor în UX;
- specialiștilor în accesibilitate;
- specialiștilor în securitate;
- instituțiilor interesate de pilotare.

Contribuțiile pot include:

- cod;
- teste;
- documentație;
- curriculum;
- lecții;
- explicații alternative;
- exerciții;
- verificare pedagogică;
- feedback;
- testare cu utilizatori;
- accesibilitate;
- securitate.

Pentru probleme sau propuneri tehnice:

https://github.com/neckroRO/OpenEdu-Romania/issues

Modificările de cod sunt propuse prin pull request.

README-ul și documentația publică sunt redactate în limba română. Identificatorii tehnici din cod pot rămâne în engleză.

---

## Q&A

### Ce este OpenEdu-Romania?

O platformă educațională construită în jurul programei școlare și al ideii că aceeași noțiune poate fi explicată în mai multe moduri.

Beneficiarii principali sunt elevii.

### Care este problema pe care încearcă să o rezolve?

Un elev poate să nu înțeleagă o explicație de la clasă fără ca profesorul sau elevul să fi făcut ceva greșit.

OpenEdu încearcă să îi ofere acces la alte explicații ale aceleiași teme.

### Va exista o singură lecție pentru fiecare temă?

Nu.

Tocmai diversitatea explicațiilor este una dintre ideile centrale ale proiectului.

Mai mulți profesori vor putea crea lecții diferite pentru același concept.

### Profesorii vor modifica lecțiile altor profesori?

Nu în mod implicit.

O lecție reprezintă o abordare distinctă și are propriul autor și propriile versiuni.

Un alt profesor poate crea o explicație alternativă pentru același concept.

### Ce se întâmplă când o lecție este actualizată?

Conținutul publicat nu trebuie suprascris fără istoric.

Modificările sunt gestionate prin versiuni editoriale.

### Cine verifică materialele?

Profesorii contribuie cu materiale.

Moderatorii și administratorii gestionează procesul de validare și publicare.

Modelul de validare pedagogică și reputație este în curs de dezvoltare continuă.

### Poate un profesor propune un concept care nu există?

Aceasta este o funcționalitate planificată pentru etapa de curriculum colaborativ.

Profesorii vor putea propune elemente noi sau unificarea unor elemente duplicate.

### Cum vor fi evitate denumirile duplicate?

Prin:

- normalizare;
- căutare;
- aliasuri;
- sugestii;
- propuneri de merge;
- moderare;
- audit.

### Cum sunt tratate alternativele sau variantele educaționale?

Modelul este proiectat să fie generic.

Aplicația nu trebuie să conțină o listă rigidă de denumiri în cod.

Contextele educaționale pot fi definite ca date, iar terminologia locală poate fi păstrată prin aliasuri sau etichete.

Conținutul comun nu trebuie duplicat inutil.

### OpenEdu înlocuiește școala?

Nu.

Platforma completează activitatea profesorului și oferă elevului resurse suplimentare atunci când are nevoie de o altă explicație, recapitulare sau aprofundare.

### Este doar pentru situații de urgență sau pentru elevii absenți?

Nu.

Poate fi folosit pentru:

- recuperarea unei lecții;
- înțelegerea unei explicații dificile;
- recapitulare;
- exercițiu;
- aprofundare;
- pregătire individuală.

### De ce nu este suficient un manual digital?

Un manual oferă o anumită structură și o anumită explicație.

OpenEdu urmărește să lege conceptele curriculare de mai multe explicații și resurse, create de profesori diferiți.

### Este programa școlară baza platformei?

Da.

Programa este reperul principal de organizare.

Manualele reprezintă surse și resurse, nu structura unică a platformei.

### Este curriculumul actual complet?

Nu.

Datele existente sunt încă demonstrative și vor fi extinse gradual.

### Există deja OpenEdu Local?

Nu.

Arhitectura Central–Local este definită ca direcție, dar modulele complete pentru instituții, elevi și clase locale urmează să fie construite.

### De ce separăm Central de Local?

Pentru ca resursele educaționale să poată fi comune fără ca toate datele elevilor să fie centralizate în aceeași aplicație.

### Există deja aplicație mobilă?

Nu.

Interfața web responsive este prioritară. Nevoia unei aplicații dedicate va fi evaluată ulterior.

### Proiectul are avizul Ministerului Educației?

Nu revendicăm un astfel de statut.

Orice aviz, acreditare, parteneriat sau adoptare oficială va fi comunicată numai dacă există o confirmare documentată.

### Cine poate contribui?

Oricine poate contribui tehnic prin mecanismele repository-ului.

Pentru conținutul educațional, rolul profesorilor și validarea pedagogică sunt esențiale.

---

## Licențiere și drepturile asupra materialelor

Intenția proiectului este dezvoltarea deschisă.

La data acestei actualizări, repository-ul nu conține încă un fișier `LICENSE` la rădăcină care să stabilească licența întregului proiect.

Alegerea și publicarea licenței trebuie clarificate separat.

Materialele educaționale au propriile drepturi de autor.

Faptul că un manual, un video, o imagine sau un document este disponibil online nu înseamnă automat că poate fi copiat și redistribuit.

Politica privind:

- licența codului;
- licența conținutului;
- atribuirea autorilor;
- sursele;
- materialele externe;

va fi definită explicit înaintea unei utilizări publice la scară largă.

---

## Originea proiectului

OpenEdu-Romania a pornit în 2020, în perioada pandemiei, din comunitatea **Grupul IT-știlor**.

Repository:

https://github.com/neckroRO/OpenEdu-Romania

Comunitate:

https://discord.gg/2zZwSU9

---

**Stadiu documentat: 7 octombrie 2026 — nucleul OpenEdu Central funcțional, dezvoltarea curriculară și editorială ajunsă până la etapa 07.6.2.5; următoarea direcție majoră este curriculumul colaborativ, urmat de editorul avansat de lecții.**
