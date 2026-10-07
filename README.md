# OpenEdu-Romania

*Aceeași lecție, explicată în mai multe feluri.*

OpenEdu-Romania este o platformă educațională pentru elevii care au nevoie de o altă explicație ca să înțeleagă o lecție.

Se întâmplă des: un profesor explică bine pentru jumătate din clasă, iar ceilalți rămân pe dinafară. Alt profesor ar fi ales alte exemple, un desen, un video sau pur și simplu alte cuvinte și poate că tocmai aceea ar fi fost explicația potrivită. OpenEdu strânge la un loc aceste explicații.

Nu încercăm să scriem „lecția perfectă". Vrem un loc în care mai mulți profesori pot explica aceeași temă în maniere diferite, iar elevul o găsește pe cea care i se potrivește.

Proiectul a pornit în 2020, în pandemie, și a fost refăcut de la zero în 2026, pe o arhitectură modernă. Ideea de la care nu ne abatem:

> Niciun copil nu ar trebui să rămână în urmă doar pentru că prima explicație nu i s-a potrivit.

## Unde suntem acum

Proiectul e în dezvoltare activă. În octombrie 2026, aplicația centrală are un nucleu care funcționează: catalogul educațional, structura curriculară, lecțiile și versiunile lor, fluxul profesor → moderare → publicare, validarea pedagogică și reputația, conturile cu roluri, administrarea utilizatorilor, o interfață web și un API versionat.

Verificările de la ultima actualizare: 217 teste backend și 128 teste frontend trec, build-ul frontend merge, iar fluxurile principale au fost încercate și manual în browser.

Atenție totuși: e un punct de reper în dezvoltare, nu o lansare. Platforma nu este pregătită pentru date reale ale elevilor.

**Pe scurt:** [De ce există](#de-ce-există-openedu) · [Cum e gândită o lecție](#cum-e-gândită-o-lecție) · [Curriculum](#curriculum-colaborativ) · [Validare](#validare-și-contribuții) · [Central și Local](#openedu-central-și-openedu-local) · [Ce merge acum](#ce-merge-acum) · [Roadmap](#roadmap) · [Editorul](#editorul-de-lecții) · [Pornire](#pornire-pentru-dezvoltare) · [Contribuții](#contribuții) · [Întrebări](#întrebări-frecvente) · [Licență](#licență-și-drepturi)

---

## De ce ideea OpenEdu

Un copil stă la lecție, profesorul explică, iar copilul nu înțelege. Seara, acasă, caută pe cont propriu o explicație diferită.

Nu înseamnă că profesorul a greșit sau că elevul nu poate. De multe ori informația pur și simplu nu a ajuns la el în forma în care o poate primi. Pentru aceeași temă pot exista o explicație vizuală, una pas cu pas, una pornită de la exemple din viața de zi cu zi, un video, o serie de exerciții, o recapitulare de cinci minute sau o lecție lungă pentru cine vrea să meargă mai adânc. Toate pot fi corecte și toate pot respecta programa. Doar că una dintre ele face diferența pentru un anumit copil.

OpenEdu nu vrea să înlocuiască profesorul sau școala. Vrea să-i dea elevului încă o șansă să înțeleagă.

## Cum e gândită o lecție

Platforma ține separate trei lucruri care se confundă ușor: conceptul din programă, lecțiile care îl explică și versiunile fiecărei lecții.

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

Profesorii nu se suprascriu între ei. Un profesor poate scrie propria explicație pentru un concept care există deja, iar fiecare lecție are autorul, conținutul și istoricul ei de revizii. Diversitatea explicațiilor este un punct forte al platformei, nu o problemă de rezolvat.

### Ce vede elevul

Elevul nu primește o simplă listă de fișiere. Experiența pe care o urmărim arată cam așa:

```text
Fracții

Nu ai înțeles? Încearcă altă explicație:

→ Explicație vizuală
→ Pas cu pas
→ Exemple practice
→ Video + exerciții
→ Recapitulare rapidă
```

Mai târziu, lecțiile vor putea fi etichetate după felul în care explică (vizuală, pas cu pas, bazată pe exemple, practică, video, interactivă, recapitulare, aprofundare). Etichetele acestea nu împart copiii în „tipuri". Descriu doar modul de a explica. Iar o explicație mai puțin populară nu se elimină automat: poate fi chiar cea de care are nevoie un anumit elev.

## Curriculum colaborativ

Programa școlară este reperul după care organizăm conținutul. Manualele sunt resurse utile, dar platforma nu trebuie să depindă de ordinea sau de formulările unui singur manual.

Ierarhia este: nivel/clasă → context curricular → materie → domeniu → concept → lecții și resurse.

### Fără duplicate și denumiri încurcate

Nu vrem ca „Clasa a VIII-a", „Clasa VIII", „Clasa a 8-a" și „VIII" să ajungă patru entități diferite. La fel pentru „Limba română", „Lb. română" și „Limba și literatura română". Pentru asta folosim denumiri normalizate, entități canonice și aliasuri. Aliasurile nu sunt gunoi: dacă un profesor caută „Lb. română", trebuie să ajungă tot la obiectul corect.

### Propuneri din partea profesorilor

Profesorii nu sunt limitați la structura existentă. Vor putea propune o clasă, o materie, un domeniu sau un concept nou, schimbarea unei denumiri sau unirea a două elemente care par să fie același lucru.

O propunere nu modifică direct nomenclatorul. Intră întâi la moderare, unde moderatorul sau administratorul poate s-o aprobe, s-o respingă cu motiv, să ceară modificări, s-o corecteze, s-o lege de un element existent sau să aprobe unirea. Între timp, profesorul poate continua să lucreze; când propunerea este aprobată sau unită cu altceva, legăturile se mută la obiectul canonic.

### Variante educaționale

În cod nu vrem o listă fixă de metode sau alternative educaționale. Contextele noi se definesc ca date, fără să modificăm aplicația, iar un utilizator poate marca opțional că o structură ține de o variantă față de programul de bază. Terminologia canonică rămâne neutră, valabilă la nivel național; denumirile locale trăiesc ca aliasuri sau etichete.

Conceptele comune nu se dublează. „Fracții" rămâne un singur concept, care poate apărea în mai multe contexte. Diferențele se descriu doar acolo unde chiar există.

## Validare și contribuții

OpenEdu se sprijină pe contribuțiile profesorilor, dar publicarea rămâne controlată. Fluxul editorial de acum:

```text
draft → submitted → approved → published
```

O contribuție respinsă primește feedback de la moderator și se întoarce la draft (`submitted → rejected → draft`). Dacă editezi o resursă publicată, versiunea existentă nu se pierde: varianta nouă trece prin propriul circuit.

Pe termen lung, reputația contributorilor și istoricul validărilor pot ajuta la prioritizarea moderării, dar fără să scoată omul din ecuație pentru schimbările importante.

Orice modificare structurală e auditată: cine a propus-o, cine a verificat-o, ce s-a schimbat, când, de ce și ce legături au fost afectate. Unirile (merge) trebuie să poată fi inspectate și să nu piardă istoricul.

## OpenEdu Central și OpenEdu Local

Proiectul separă **conținutul educațional**, care poate fi comun, de **datele elevilor și ale școlilor**, care nu trebuie să fie.

**OpenEdu Central** se ocupă de curriculum, concepte, lecții, resurse, versiuni, contribuțiile profesorilor, validare, publicare și distribuirea conținutului.

**OpenEdu Local** este instanța unei școli și gestionează elevii, tutorii, clasele, activitățile locale, progresul, răspunsurile la quiz-uri, rezultatele și celelalte date interne.

> Conținutul poate fi comun. Datele copilului rămân la instituția care răspunde de ele.

Integrarea Central–Local, izolarea datelor, retenția, backup-ul, securitatea și conformitatea GDPR cer muncă și verificări separate înainte de orice utilizare cu date reale.

## Ce avem până acum

| Zonă | Stadiu |
| --- | --- |
| Catalog educațional (niveluri, materii, concepte, resurse) | Funcțional |
| Import curricular | Implementat |
| Catalog public, cu căutare, filtrare, sortare și paginare | Funcțional |
| Pagina publică a unei resurse | Funcțional |
| Model de lecții, legături lecție–concept, resurse pe versiuni | Implementat |
| Autentificare, roluri și autorizare | Funcțional |
| Zona profesorului, moderare, publicare | Funcțional |
| Validare pedagogică și model de reputație | Implementat |
| Administrarea utilizatorilor centrali (activare/dezactivare, resetare parolă) | Funcțional |
| Protecția rutelor în frontend | Funcțional |
| API `/api/v1` | Funcțional |
| Teste backend / frontend | 217 / 128 |
| Build frontend | Funcțional |

Rolurile definite sunt `learner`, `guardian`, `teacher`, `moderator` și `admin`. Rolurile de elev și tutore există în model, dar modulele OpenEdu Local nu sunt construite încă.

## Roadmap

Roadmap-ul arată direcția de acum și se poate schimba pe măsură ce deciziile tehnice și pedagogice se confirmă. Nu e doar o listă de funcții, ci ordinea în care vrem să trecem de la un nucleu editorial la un ecosistem complet.

### Bifat (etapele 00–06)

Viziunea și cerințele, auditul prototipului din 2020 (și decizia de a reconstrui), arhitectura Central/Local, modelul de date, autentificarea cu roluri și permisiuni, backend-ul cu API versionat și frontend-ul central, cu catalog, căutare, zona profesorului, moderare și teste.

### 07 – Curriculum, lecții și comunitatea profesorilor

Gata până acum: modelul curricular și importul, modelul de lecții, asocierea cu conceptele, resursele pe versiuni, consumul public, validarea pedagogică, reputația, administrarea utilizatorilor și conturi demonstrative pentru dezvoltare.

Urmează **07.7, curriculum colaborativ și deduplicare**: propuneri de clase, materii, domenii și concepte, propuneri de modificare, circuit de revizuire cu cereri de corecturi, propuneri de unire cu previzualizarea efectelor, aliasuri, normalizarea denumirilor, detectarea duplicatelor, audit complet, contexte și variante educaționale și posibilitatea de a lucra pe o propunere aflată încă în moderare.

### 08 – Editor avansat de lecții

Una dintre piesele centrale ale platformei. Detalii [mai jos](#editorul-de-lecții).

### 09 – Explicații alternative și experiența elevului

Mai multe lecții independente pentru același concept, cu autori și versiuni proprii. Fiecare va avea o descriere a abordării, timp estimat, nivel de dificultate și resurse necesare. Elevul va putea apăsa „Încearcă altă explicație", va naviga între abordări și va spune cât de utilă i s-a părut una. Scopul nu e să alegem o lecție câștigătoare, ci ca elevul să găsească explicația care funcționează pentru el.

### 10 – Activități interactive și evaluare

Quiz-uri în lecții, exerciții interactive cu feedback imediat și încercări multiple, seturi de exerciții, evaluări formative, recapitulări, activități de grup. Activitățile se distribuie central, dar răspunsurile și progresul elevilor aparțin instanțelor locale.

### 11 – OpenEdu Local

Instituții, clase, elevi, tutori, profesori locali, înscrieri, progres, rezultate, setări și permisiuni locale. Proiectarea trebuie să lase școala stăpână pe datele ei, fără ca OpenEdu Central să devină o bază națională cu informațiile personale ale tuturor elevilor.

### 12 – Sincronizare Central–Local

Distribuirea curriculumului, a lecțiilor și a actualizărilor de resurse, cu versiuni, sincronizare incrementală, rezolvarea conflictelor, funcționare controlată cu conexiune slabă și verificarea integrității.

### 13 – Securitate și protecția datelor

Obligatorii înainte de orice folosire cu date ale copiilor: threat modeling, control strict al accesului, audit, protecția sesiunilor, rate limiting, politici de parole, HTTPS, backup și restaurare, retenție și ștergere, jurnalizare, separarea datelor între instituții, revizuire GDPR și teste de securitate.

### 14 – Deployment și operare

Instalare reproductibilă, configurare pentru producție, containere, migrații controlate, backup automat, monitorizare, loguri, health checks, proceduri de upgrade și documentație pentru administratori.

### 15 – Accesibilitate și multi-device

WCAG, navigare completă din tastatură, suport pentru cititoare de ecran, contrast și dimensiuni de font accesibile, design responsive pe telefon, tabletă și desktop, optimizare pentru conexiuni lente.

### 16 – Pilot și extindere

Pilot cu profesori și cu elevi, feedback de la părinți, corectarea problemelor apărute în practică, documentație și onboarding pentru școli, măsurarea impactului, apoi extindere treptată.

## Editorul de lecții

Editorul e una dintre direcțiile cu cea mai mare prioritate pentru experiența finală. Nu vrem un simplu câmp de text, ci un **editor modular pe blocuri**, făcut special pentru conținut educațional.

Blocurile planificate:

- **Conținut:** text formatat, titluri, liste, tabele, citate, casete informative, „De reținut", exemple, observații.
- **Multimedia:** imagini, galerii, audio, video, YouTube, resurse externe.
- **Matematică și științe:** formule, ecuații, grafice de funcții, tabele de valori, elemente de geometrie, diagrame, reprezentări interactive.
- **Activități:** exerciții, exemple rezolvate, indicii și soluții, alegere multiplă, adevărat/fals, răspuns liber, asociere, ordonare, quiz-uri.

În editor, profesorul va putea trage și muta blocuri, le va putea duplica, vedea previzualizarea (inclusiv „mod elev" și variantele pentru desktop, tabletă și telefon), salva drafturi, păstra versiuni și porni de la șabloane de lecție. Nu trebuie să știe HTML, CSS sau programare: să se poată concentra pe conținut și pe pedagogie.

Un exemplu de aspect:

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

Lecțiile nu se vor stoca drept pagini HTML oarecare, ci ca blocuri structurate. Așa același conținut poate fi afișat în contexte diferite și poate evolua independent de design:

```json
{
  "blocks": [
    { "type": "paragraph", "data": {} },
    { "type": "youtube",   "data": {} },
    { "type": "geometry",  "data": {} },
    { "type": "quiz",      "data": {} }
  ]
}
```

Ce introduc utilizatorii nu are voie să ruleze HTML sau JavaScript arbitrar.

## Tehnologii

| Zonă | Ce folosim |
| --- | --- |
| Backend central | PHP, Laravel 13, Laravel Sanctum, Eloquent |
| Bază de date (dezvoltare) | PostgreSQL 18 |
| Frontend central | Vue 3, TypeScript, Vite, Vue Router, Pinia |
| Teste backend | PHPUnit (SQLite în memorie) |
| Teste frontend | Vitest, Vue Test Utils, jsdom |
| Infrastructură de dezvoltare | Docker Compose |

Alegerile acestea pot să se schimbe dacă apare un motiv tehnic serios.

## Structura repository-ului

| Director / fișier | Ce conține |
| --- | --- |
| `apps/central/backend/` | API, modele, migrări, seedere, teste backend |
| `apps/central/frontend/` | Interfața OpenEdu Central și testele ei |
| `infra/compose.dev.yml` | PostgreSQL pentru dezvoltare |
| `infra/.env.example` | Configurație exemplu pentru infrastructura locală |

Prototipul din 2020 rămâne în istoricul Git.

## Pornire pentru dezvoltare

Ai nevoie de Git, Docker cu Compose, PHP, Composer, Node.js și npm. Eu am lucrat cu PHP 8.5 și Node.js 24.

### 1. Repository și baza de date

```bash
git clone https://github.com/neckroRO/OpenEdu-Romania.git
cd OpenEdu-Romania
cp infra/.env.example infra/.env
```

Pune o parolă la `POSTGRES_PASSWORD` în `infra/.env`, apoi:

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

Un exemplu de configurare în `.env`:

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

Apoi migrările, datele demonstrative și serverul:

```bash
php artisan migrate
php artisan db:seed --class=OpenEduMvpSeeder
php artisan db:seed --class=OpenEduDevelopmentUsersSeeder

php artisan serve --host=127.0.0.1 --port=8000
```

`OpenEduMvpSeeder` creează date educaționale demonstrative. `OpenEduDevelopmentUsersSeeder` creează conturi doar pentru dezvoltare locală:

| Rol | E-mail | Parolă inițială |
| --- | --- | --- |
| Administrator | `admin@exemplu.ro` | `admin` |
| Moderator | `moderator@exemplu.ro` | `moderator` |
| Profesor | `profesor@exemplu.ro` | `profesor` |
| Elev | `elev@exemplu.ro` | `elev` |
| Tutore | `tutore@exemplu.ro` | `tutore` |

> **Important:** conturile acestea sunt strict pentru dezvoltare. Nu le folosi într-un mediu public sau de producție și schimbă parolele când e cazul.

Seederul refuză să ruleze în mediul `production`, iar dacă îl rulezi din nou nu suprascrie parolele deja schimbate.

### 3. Frontend

Într-un terminal separat:

```bash
cd apps/central/frontend
npm ci
npm run dev -- --host 127.0.0.1
```

Frontend-ul pornește pe `http://localhost:5173`, iar API-ul pe `http://localhost:8000/api/v1`. Vite trimite cererile `/api` către backendul local.

### 4. Teste

```bash
# backend
cd apps/central/backend
php artisan test

# frontend
cd apps/central/frontend
npm run test:run
npm run build
```

Testele automate nu înlocuiesc verificarea pe PostgreSQL și încercarea manuală în browser.

## Contribuții

Ne trebuie mai mult decât programatori. Sunt bineveniți profesori, elevi, părinți, dezvoltatori, oameni din UX, accesibilitate și securitate, precum și instituții care vor să facă un pilot.

Poți ajuta cu cod, teste, documentație, curriculum, lecții, explicații alternative, exerciții, verificare pedagogică, feedback, testare cu utilizatori, accesibilitate sau securitate.

Pentru probleme și propuneri tehnice, deschide un issue: <https://github.com/neckroRO/OpenEdu-Romania/issues>. Modificările de cod vin prin pull request. README-ul și documentația publică sunt în română; identificatorii din cod pot rămâne în engleză.

## Întrebări frecvente

**Ce încearcă să rezolve platforma?**
Un elev poate să nu înțeleagă o explicație de la clasă fără ca cineva să fi greșit ceva. OpenEdu îi arată alte explicații ale aceleiași teme.

**Va exista o singură lecție pentru fiecare temă?**
Nu, tocmai asta e ideea. Mai mulți profesori pot scrie lecții diferite pentru același concept.

**Își vor modifica profesorii lecțiile între ei?**
Nu! O lecție e o abordare distinctă, cu autor și versiuni proprii. Alt profesor poate scrie o explicație alternativă pentru același concept.

**Ce se întâmplă când o lecție e actualizată?**
Conținutul publicat nu se suprascrie fără istoric. Schimbările trec prin versiuni editoriale.

**Cine verifică materialele?**
Profesorii contribuie, iar moderatorii și administratorii se ocupă de validare și publicare. Modelul de validare pedagogică și reputație încă se dezvoltă.

**Poate un profesor să propună un concept care nu există?**
Da, dar abia în etapa de curriculum colaborativ, care e planificată. La fel și propunerile de unire a duplicatelor.

**Cum evităm denumirile duplicate?**
Prin normalizare, căutare, aliasuri, sugestii, propuneri de unire, moderare și audit.

**Cum tratăm alternativele educaționale?**
Modelul e generic: contextele se definesc ca date, nu în cod, terminologia locală se păstrează prin aliasuri sau etichete, iar conținutul comun nu se duplică.

**Înlocuiește OpenEdu școala?**
Nu! Completează munca profesorului și oferă resurse în plus pentru cine are nevoie de altă explicație, de recapitulare sau de aprofundare.

**E doar pentru urgențe sau pentru elevii absenți?**
Nu. Se poate folosi pentru recuperarea unei lecții, pentru o explicație grea, pentru recapitulare, exercițiu, aprofundare sau pregătire individuală.

**De ce nu ajunge un manual digital?**
Un manual dă o singură structură și o singură explicație. OpenEdu leagă conceptele din programă de mai multe explicații făcute de profesori diferiți.

**Programa e baza platformei?**
Da. Manualele sunt surse și resurse, nu structura unică.

**E complet curriculumul de acum?**
Nu. Datele existente sunt demonstrative și le vom extinde treptat.

**Există deja OpenEdu Local?**
Nu. Arhitectura Central–Local e stabilită ca direcție, dar modulele pentru instituții, elevi și clase locale urmează să fie construite.

**De ce separăm Central de Local?**
Ca resursele să poată fi comune fără ca toate datele elevilor să ajungă în aceeași aplicație.

**Există aplicație mobilă?**
Nu. Prioritatea e interfața web responsive; vedem mai târziu dacă e nevoie de o aplicație dedicată.

**Are proiectul avizul Ministerului Educației?**
Nu pretindem așa ceva. Orice aviz, acreditare, parteneriat sau adoptare oficială o vom anunța doar dacă există o confirmare documentată.

**Cine poate contribui?**
Oricine, tehnic, prin mecanismele repository-ului. La conținutul educațional contează în mod special rolul profesorilor și validarea pedagogică.

## Licență și drepturi

Intenția noastră este ca proiectul să fie deschis, dar la data acestei actualizări repository-ul nu are încă un fișier `LICENSE` în rădăcină, deci licența întregului proiect nu e stabilită. O vom alege și o vom publica separat.

Materialele educaționale au propriile drepturi de autor. Faptul că un manual, un video, o imagine sau un document se găsește online nu înseamnă că poate fi copiat și redistribuit. Înainte de o utilizare publică la scară largă vom stabili explicit politica pentru licența codului, licența conținutului, atribuirea autorilor, surse și materiale externe.

## De unde vine proiectul

OpenEdu-Romania a pornit în 2020, în perioada pandemiei, în comunitatea **Grupul IT-știlor**.

- Repository: <https://github.com/neckroRO/OpenEdu-Romania>
- Comunitate: <https://discord.gg/2zZwSU9>

---

*Stadiu documentat la 7 octombrie 2026: nucleul OpenEdu Central funcționează, dezvoltarea curriculară și editorială a ajuns la etapa 07.6.2.5. Urmează curriculumul colaborativ, apoi editorul avansat de lecții.*
