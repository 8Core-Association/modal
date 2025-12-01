<?php
/**
 * SEUP - Instalacija modula obavjesti
 * Ova stranica kreira potrebne tablice za sustav obavjesti
 */

echo "<h1>SEUP - Instalacija Sustava Obavjesti</h1>";
echo "<pre>";

// Prikaži upute za pokretanje
echo "=== UPUTE ZA INSTALACIJU ===\n\n";
echo "Da biste instalirali sustav obavjesti, potrebno je:\n\n";

echo "1. Pokrenuti SQL skriptu na vašoj MariaDB bazi:\n";
echo "   mysql -u [korisnik] -p [baza] < /tmp/cc-agent/60933575/project/seup/sql/a_obavjesti.sql\n\n";

echo "2. ALTERNATIVNO - izvršiti SQL skriptu direktno:\n\n";

// Pročitaj i prikaži SQL
$sql_file = __DIR__ . '/sql/a_obavjesti.sql';
if (file_exists($sql_file)) {
    echo "--- SQL ZA IZVRŠITI ---\n\n";
    $sql = file_get_contents($sql_file);
    echo $sql;
    echo "\n\n";
}

echo "3. Nakon instalacije SQL tablica, admin stranica za upravljanje obavjestima bit će dostupna na:\n";
echo "   /custom/seup/admin/obavjesti.php\n\n";

echo "4. Obavjesti se prikazuju na glavnoj stranici (seupindex.php) u žutom zvoncetu.\n\n";

echo "=== DATOTEKE SUSTAVA ===\n\n";

$files = [
    'SQL migracija' => 'sql/a_obavjesti.sql',
    'Helper klasa' => 'class/obavjesti_helper.class.php',
    'AJAX endpoint' => 'class/obavjesti_ajax.php',
    'Admin stranica' => 'admin/obavjesti.php',
    'JavaScript' => 'js/notification-bell.js',
    'CSS - Bell' => 'css/notification-bell.css',
    'CSS - Admin' => 'css/obavjesti.css'
];

foreach ($files as $name => $path) {
    $full_path = __DIR__ . '/' . $path;
    $exists = file_exists($full_path) ? '✓ POSTOJI' : '✗ NE POSTOJI';
    $size = file_exists($full_path) ? ' (' . filesize($full_path) . ' bytes)' : '';
    echo sprintf("%-20s: %s %s%s\n", $name, $exists, $path, $size);
}

echo "\n=== STRUKTURA TABLICA ===\n\n";

echo "llx_a_obavjesti:\n";
echo "  - rowid (PK)\n";
echo "  - naslov\n";
echo "  - subjekt (info|upozorenje|nadogradnja|hitno|vazno)\n";
echo "  - sadrzaj\n";
echo "  - vanjski_link\n";
echo "  - kreirao_user_id\n";
echo "  - datum_kreiranja\n";
echo "  - aktivna\n\n";

echo "llx_a_procitane_obavjesti:\n";
echo "  - rowid (PK)\n";
echo "  - obavjest_id (FK)\n";
echo "  - user_id\n";
echo "  - datum_procitano\n\n";

echo "llx_a_obrisane_obavjesti:\n";
echo "  - rowid (PK)\n";
echo "  - obavjest_id\n";
echo "  - user_id\n";
echo "  - datum_brisanja\n";
echo "  - naslov (arhiva)\n";
echo "  - sadrzaj (arhiva)\n\n";

echo "</pre>";

echo "<h2>Sljedeći koraci:</h2>";
echo "<ol>";
echo "<li>Kopirajte SQL kod iznad i izvršite ga u svojoj MariaDB bazi</li>";
echo "<li>Provjerite da li tablice llx_a_obavjesti, llx_a_procitane_obavjesti i llx_a_obrisane_obavjesti postoje</li>";
echo "<li>Pristupite admin stranici /custom/seup/admin/obavjesti.php kao administrator</li>";
echo "<li>Kreirajte testnu obavjest</li>";
echo "<li>Provjerite žuto zvonce na glavnoj stranici (seupindex.php)</li>";
echo "</ol>";
?>
