<?php
/**
 * SEUP - Test instalacije tablica za obavjesti
 * Ova stranica testira da li su tablice uspješno kreirane
 */

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

require_once __DIR__ . '/class/obavjesti_helper.class.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>SEUP - Test Obavjesti Tablica</title>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} pre{background:#f5f5f5;padding:10px;border-left:4px solid #333;}</style>";
echo "</head><body>";

echo "<h1>SEUP - Test Instalacije Obavjesti</h1>";

echo "<h2>1. Pokušaj kreiranja tablica</h2>";

$result = Obavjesti_helper::createNotificationTables($db);

if ($result) {
    echo "<p class='success'>✓ Funkcija createNotificationTables() izvršena uspješno</p>";
} else {
    echo "<p class='error'>✗ Greška pri kreiranju tablica</p>";
    echo "<p>Greška: " . $db->lasterror() . "</p>";
}

echo "<h2>2. Provjera postojanja tablica</h2>";

$tables_to_check = [
    MAIN_DB_PREFIX . 'a_obavjesti',
    MAIN_DB_PREFIX . 'a_procitane_obavjesti',
    MAIN_DB_PREFIX . 'a_obrisane_obavjesti'
];

foreach ($tables_to_check as $table) {
    $sql = "SHOW TABLES LIKE '" . $table . "'";
    $result = $db->query($sql);

    if ($result && $db->num_rows($result) > 0) {
        echo "<p class='success'>✓ Tablica <strong>$table</strong> postoji</p>";

        $sql_desc = "DESCRIBE " . $table;
        $result_desc = $db->query($sql_desc);

        if ($result_desc) {
            echo "<details><summary>Struktura tablice</summary><pre>";
            while ($obj = $db->fetch_object($result_desc)) {
                echo $obj->Field . " | " . $obj->Type . " | " . $obj->Null . " | " . $obj->Key . "\n";
            }
            echo "</pre></details>";
        }
    } else {
        echo "<p class='error'>✗ Tablica <strong>$table</strong> NE postoji</p>";
    }
}

echo "<h2>3. Informacije o bazi</h2>";
echo "<ul>";
echo "<li><strong>DB Prefix:</strong> " . MAIN_DB_PREFIX . "</li>";
echo "<li><strong>DB Type:</strong> " . $db->type . "</li>";
echo "<li><strong>DB Name:</strong> " . $db->database_name . "</li>";
echo "</ul>";

echo "<h2>4. Test kreiranja obavjesti</h2>";

if ($user && $user->id) {
    $test_result = Obavjesti_helper::createObavjest(
        $db,
        'Test Obavjest',
        'info',
        'Ovo je testna obavjest kreirana automatski.',
        null,
        $user->id
    );

    if ($test_result) {
        echo "<p class='success'>✓ Testna obavjest kreirana! ID: $test_result</p>";

        $count = Obavjesti_helper::getUnreadCountForUser($db, $user->id);
        echo "<p class='success'>✓ Broj nepročitanih obavjesti: $count</p>";
    } else {
        echo "<p class='error'>✗ Greška pri kreiranju testne obavjesti</p>";
        echo "<p>SQL Greška: " . $db->lasterror() . "</p>";
    }
} else {
    echo "<p class='error'>✗ Korisnik nije prijavljen, ne mogu kreirati testnu obavjest</p>";
}

echo "<hr>";
echo "<p><a href='seupindex.php'>← Natrag na SEUP</a> | <a href='admin/obavjesti.php'>Admin Obavjesti →</a></p>";

echo "</body></html>";

$db->close();
