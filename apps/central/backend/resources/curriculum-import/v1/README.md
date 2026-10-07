# OpenEdu Curriculum Import Contract — v1

Acest director definește formatul JSON utilizat pentru importul programelor
școlare în OpenEdu.

## Versiune

Fiecare document trebuie să declare câmpul:

    "schema_version": "1.0"

Importatorul trebuie să respingă versiunile de contract pe care nu le cunoaște.

## Structură principală

Documentul conține următoarele secțiuni:

- `schema_version`
- `curriculum`
- `curriculum_version`
- `education_level`
- `subject`
- `curriculum_subject`
- `domains`
- `concepts`
- `competencies`

## curriculum

Descrie curriculumul național sau instituțional.

Câmpuri:

- `code` — identificator stabil, unic
- `name`
- `country_code`
- `status`

## curriculum_version

Descrie versiunea OpenEdu a curriculumului.

Câmpuri:

- `version`
- `valid_from`
- `valid_until`
- `status`

Datele folosesc formatul ISO `YYYY-MM-DD`.

## education_level

Descrie nivelul sau clasa.

Câmpuri:

- `code`
- `name`
- `ordinal`
- `education_stage`

## subject

Descrie disciplina.

Câmpuri:

- `code`
- `name`
- `description`
- `status`

## curriculum_subject

Leagă disciplina de curriculum, versiune și nivel.

Câmpuri:

- `display_order`
- `status`
- `program`

### program

Metadatele sursei oficiale:

- `reference`
- `source_url`
- `approved_at`

## domains

Lista domeniilor curriculare.

Fiecare domeniu conține:

- `key` — identificator local documentului de import
- `parent_key` — cheia domeniului părinte sau `null`
- `title`
- `description`
- `display_order`

`key` nu este stocat ca identificator public OpenEdu. Este folosit pentru
referințe interne în documentul de import.

## concepts

Lista conceptelor educaționale.

Fiecare concept conține:

- `code` — identificator stabil OpenEdu
- `title`
- `description`
- `status`
- `placements`

### placements

Leagă conceptul de unul sau mai multe domenii.

Câmpuri:

- `domain_key`
- `display_order`
- `is_core`

`domain_key` trebuie să indice către un `domains[].key` existent.

## competencies

Lista competențelor curriculare.

Fiecare competență conține:

- `code`
- `type`
- `parent_code`
- `title`
- `description`
- `display_order`
- `status`
- `concepts`

`parent_code` este `null` pentru competențele rădăcină.

Pentru competențele copil, `parent_code` trebuie să indice către o altă
competență din același document.

### concepts

Leagă competența de concepte.

Câmpuri:

- `code`
- `display_order`
- `is_core`

`code` trebuie să indice către un `concepts[].code` existent în document.

## Reguli generale

- toate codurile sunt tratate ca string;
- identificatorii interni ai bazei de date nu apar în fișier;
- importul trebuie să fie idempotent;
- referințele între elemente folosesc coduri sau chei stabile;
- datele trebuie să folosească format ISO `YYYY-MM-DD`;
- valorile `status` trebuie validate de importator;
- referințele către domenii, concepte și competențe trebuie validate înainte
  de orice modificare a bazei de date;
- întregul import trebuie executat atomic într-o tranzacție;
- un document invalid nu trebuie să modifice baza de date.

## Domeniul contractului V1

V1 importă structura curriculară:

Curriculum
→ versiune
→ nivel
→ disciplină
→ programă oficială
→ domenii
→ concepte
→ competențe
→ relații competență–concept.

Resursele educaționale și lecțiile nu fac parte din acest contract.
