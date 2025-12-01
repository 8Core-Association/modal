# SEUP - Sustav Obavjesti

## Opis

Sustav obavjesti omogućava administratorima slanje kratkih obavijesti svim korisnicima SEUP sustava. Obavijesti se prikazuju putem žutog zvonceta u gornjem desnom uglu i broje se automatski.

## Instalacija

### 1. Kreiranje baze podataka

Izvršite SQL skriptu na svojoj MariaDB bazi:

```bash
mysql -u korisnik -p baza_ime < /tmp/cc-agent/60933575/project/seup/sql/a_obavjesti.sql
```

Ili kopirajte i izvršite SQL direktno u phpMyAdmin ili drugim alatom.

### 2. Provjera instalacije

Nakon izvršavanja SQL-a, provjerite da postoje sljedeće tablice:

- `llx_a_obavjesti` - glavna tablica obavjesti
- `llx_a_procitane_obavjesti` - pročitane obavjesti po korisnicima
- `llx_a_obrisane_obavjesti` - obrisane obavjesti (audit trail)

## Struktura Tablica

### llx_a_obavjesti

| Polje | Tip | Opis |
|-------|-----|------|
| rowid | INT | Primarni ključ |
| naslov | VARCHAR(255) | Naslov obavjesti |
| subjekt | ENUM | info / upozorenje / nadogradnja / hitno / vazno |
| sadrzaj | TEXT | Glavni sadržaj obavjesti (max 500 znakova) |
| vanjski_link | VARCHAR(512) | Opcionalni link na detaljnije informacije |
| kreirao_user_id | INT | ID korisnika koji je kreirao obavjest |
| datum_kreiranja | DATETIME | Kada je obavjest kreirana |
| aktivna | TINYINT(1) | Da li je obavjest aktivna (1) ili deaktivirana (0) |

### llx_a_procitane_obavjesti

| Polje | Tip | Opis |
|-------|-----|------|
| rowid | INT | Primarni ključ |
| obavjest_id | INT | Foreign key na llx_a_obavjesti |
| user_id | INT | ID korisnika koji je pročitao |
| datum_procitano | DATETIME | Kada je obavjest pročitana |

**UNIQUE constraint**: (obavjest_id, user_id) - korisnik može pročitati istu obavjest samo jednom

### llx_a_obrisane_obavjesti

| Polje | Tip | Opis |
|-------|-----|------|
| rowid | INT | Primarni ključ |
| obavjest_id | INT | ID originalne obavjesti |
| user_id | INT | Korisnik koji je obrisao |
| datum_brisanja | DATETIME | Kada je obrisana |
| naslov | VARCHAR(255) | Arhivirana kopija naslova |
| sadrzaj | TEXT | Arhivirana kopija sadržaja |

**Napomena**: Obavjesti se ne brišu fizički već se samo evidentiraju kao obrisane za pojedinog korisnika. Ovo sprječava situacije "Nisam dobio obavijest".

## Korištenje

### Za Administratore

1. Pristupite stranici `/custom/seup/admin/obavjesti.php`
2. Ispunite formu:
   - **Naslov**: Kratak naslov obavjesti
   - **Subjekt**: Odaberite tip obavjesti
     - ℹ️ **Info** - Opća informacija
     - ⚠️ **Upozorenje** - Važno upozorenje
     - 🔄 **Nadogradnja** - Ažuriranje sustava
     - 🚨 **Hitno** - Zahtijeva hitnu akciju
     - ⭐ **Važno** - Značajna obavijest
   - **Sadržaj**: Kratak opis (max 500 znakova)
   - **Vanjski Link**: Opcionalno - link na detaljnije informacije
3. Kliknite "Objavi Obavjest"

### Za Korisnike

1. Na glavnoj stranici (`seupindex.php`) vidjet ćete žuto zvonce u gornjem desnom uglu
2. Broj na zvoncetu pokazuje koliko imate nepročitanih obavjesti
3. Klikom na zvonce otvara se modal s listom obavjesti
4. Za svaku obavjest možete:
   - **Označi pročitano** - uklanja obavjest iz liste
   - **Obriši** - trajno uklanja obavjest (evidentira se u audit tablicu)
5. Na dnu modala:
   - **Označi sve pročitanim** - označava sve kao pročitane
   - **Obriši sve** - briše sve obavjesti (s potvrdom)

## Tipovi Subjekata

| Subjekt | Ikona | Boja | Namjena |
|---------|-------|------|---------|
| Info | ℹ️ | Plava | Opće informacije, obavijesti |
| Upozorenje | ⚠️ | Žuta | Važna upozorenja, pažnja potrebna |
| Nadogradnja | 🔄 | Ljubičasta | Nadogradnje sustava, novi features |
| Hitno | 🚨 | Crvena | Hitne situacije, kritične obavijesti |
| Važno | ⭐ | Žuta | Značajne obavijesti za sve korisnike |

## API Endpointi (AJAX)

### GET /custom/seup/class/obavjesti_ajax.php

**Parametri:**
- `action` - akcija koja se izvršava

**Dostupne akcije:**

1. **get_notifications** - Dohvaća sve nepročitane obavjesti za trenutnog korisnika
   ```javascript
   fetch('/custom/seup/class/obavjesti_ajax.php?action=get_notifications')
   ```

2. **mark_read** - Označava obavjest kao pročitanu
   ```javascript
   fetch('/custom/seup/class/obavjesti_ajax.php?action=mark_read&id=123')
   ```

3. **mark_all_read** - Označava sve obavjesti kao pročitane
   ```javascript
   fetch('/custom/seup/class/obavjesti_ajax.php?action=mark_all_read')
   ```

4. **delete** - Briše obavjest za korisnika
   ```javascript
   fetch('/custom/seup/class/obavjesti_ajax.php?action=delete&id=123')
   ```

5. **delete_all** - Briše sve obavjesti za korisnika
   ```javascript
   fetch('/custom/seup/class/obavjesti_ajax.php?action=delete_all')
   ```

## Automatsko Osvježavanje

Sustav automatski provjerava nove obavjesti svakih **30 sekundi** i ažurira brojač na zvoncetu bez potrebe za osvježavanjem stranice.

## Datoteke Sustava

```
seup/
├── sql/
│   └── a_obavjesti.sql                  # SQL migracija
├── class/
│   ├── obavjesti_helper.class.php       # Helper klasa za operacije s bazom
│   └── obavjesti_ajax.php               # AJAX endpoint za frontend
├── admin/
│   └── obavjesti.php                    # Admin stranica za upravljanje
├── css/
│   ├── notification-bell.css            # Stilovi za zvonce i modal
│   └── obavjesti.css                    # Stilovi za admin stranicu
└── js/
    └── notification-bell.js             # JavaScript za frontend funkcionalnost
```

## Sigurnost

- **Admin stranica**: Dostupna samo korisnicima s admin pravima
- **AJAX endpointi**: Provjeravaju autentifikaciju korisnika
- **SQL injection zaštita**: Svi upiti koriste escape funkcije
- **XSS zaštita**: Svi outputi su escapani

## Troubleshooting

### Tablice nisu kreirane

Provjerite da li ste izvršili SQL skriptu:
```bash
mysql -u korisnik -p baza < sql/a_obavjesti.sql
```

### Zvonce se ne prikazuje

Provjerite da li je u `seupindex.php` uključen CSS i JavaScript:
```php
print '<link href="css/notification-bell.css" rel="stylesheet">';
print '<script src="js/notification-bell.js"></script>';
```

### Brojač ne radi

Provjerite da li je helper klasa uključena:
```php
require_once __DIR__ . '/class/obavjesti_helper.class.php';
Obavjesti_helper::createNotificationTables($db);
```

### AJAX ne radi

Provjerite putanju u `notification-bell.js`:
```javascript
fetch('/custom/seup/class/obavjesti_ajax.php?action=get_notifications')
```

## Autor

**Tomislav Galić** <tomislav@8core.hr>
**8Core Association**
Web: https://8core.hr
Tel: +385 099 851 0717

© 2025 Sva prava pridržana
