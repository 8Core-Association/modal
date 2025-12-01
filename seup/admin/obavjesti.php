<?php
/**
 * Plaćena licenca
 * (c) 2025 Tomislav Galić <tomislav@8core.hr>
 * Suradnik: Marko Šimunović <marko@8core.hr>
 * Web: https://8core.hr
 * Kontakt: info@8core.hr | Tel: +385 099 851 0717
 * Sva prava pridržana. Ovaj softver je vlasnički i zabranjeno ga je
 * distribuirati ili mijenjati bez izričitog dopuštenja autora.
 */

/**
 * Admin page for managing SEUP notifications
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once __DIR__ . '/../class/obavjesti_helper.class.php';

// Load translations
$langs->loadLangs(array("seup@seup", "admin"));

// Security check - only admin
if (!$user->admin) {
    accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$obavjest_id = GETPOST('id', 'int');

// Initialize tables - automatski kreira tablice ako ne postoje
$table_creation_result = Obavjesti_helper::createNotificationTables($db);

if (!$table_creation_result) {
    dol_syslog("SEUP Obavjesti: Failed to create/verify tables - " . $db->lasterror(), LOG_ERR);
}

$error = 0;
$errors = array();
$success_message = '';

// Handle actions
if ($action == 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $naslov = GETPOST('naslov', 'alphanohtml');
    $subjekt = GETPOST('subjekt', 'aZ09');
    $sadrzaj = GETPOST('sadrzaj', 'restricthtml');
    $vanjski_link = GETPOST('vanjski_link', 'alpha');

    if (empty($naslov)) {
        $errors[] = 'Naslov je obavezan';
        $error++;
    }

    if (empty($subjekt)) {
        $errors[] = 'Subjekt je obavezan';
        $error++;
    }

    if (empty($sadrzaj)) {
        $errors[] = 'Sadržaj je obavezan';
        $error++;
    }

    if (!$error) {
        $result = Obavjesti_helper::createObavjest($db, $naslov, $subjekt, $sadrzaj, $vanjski_link, $user->id);

        if ($result) {
            $success_message = 'Obavjest uspješno kreirana!';
            header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
            exit;
        } else {
            $errors[] = 'Greška pri kreiranju obavjesti';
            $error++;
        }
    }
}

if ($action == 'deactivate' && $obavjest_id > 0) {
    if (Obavjesti_helper::deactivateObavjest($db, $obavjest_id)) {
        $success_message = 'Obavjest deaktivirana';
        header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
        exit;
    } else {
        $errors[] = 'Greška pri deaktivaciji obavjesti';
        $error++;
    }
}

if (GETPOST('success', 'int') == 1) {
    $success_message = 'Akcija uspješno izvršena';
}

// Get all active notifications
$obavjesti = Obavjesti_helper::getActiveObavjesti($db);

// View
$form = new Form($db);

llxHeader("", "SEUP - Upravljanje Obavjestima", '', '', 0, 0, '', '', '', 'mod-seup page-admin-obavjesti');

print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="../css/seup-modern.css" rel="stylesheet">';
print '<link href="../css/obavjesti.css" rel="stylesheet">';

print '<div class="seup-admin-container">';

print '<div class="seup-page-header">';
print '<h1><i class="fas fa-bell"></i> Upravljanje Obavjestima</h1>';
print '<p>Kreirajte i upravljajte obavjestima za sve korisnike sustava</p>';
print '</div>';

// Success/Error messages
if ($success_message) {
    print '<div class="seup-alert seup-alert-success">';
    print '<i class="fas fa-check-circle"></i> ' . $success_message;
    print '</div>';
}

if ($error) {
    print '<div class="seup-alert seup-alert-danger">';
    foreach ($errors as $err) {
        print '<i class="fas fa-exclamation-triangle"></i> ' . $err . '<br>';
    }
    print '</div>';
}

// Create notification form
print '<div class="seup-card">';
print '<div class="seup-card-header">';
print '<h3><i class="fas fa-plus-circle"></i> Nova Obavjest</h3>';
print '</div>';
print '<div class="seup-card-body">';

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="create">';

print '<div class="seup-form-group">';
print '<label for="naslov">Naslov <span class="required">*</span></label>';
print '<input type="text" class="seup-form-control" id="naslov" name="naslov" required maxlength="255" placeholder="Kratak naslov obavjesti">';
print '</div>';

print '<div class="seup-form-group">';
print '<label for="subjekt">Subjekt <span class="required">*</span></label>';
print '<select class="seup-form-control" id="subjekt" name="subjekt" required>';
print '<option value="">-- Odaberite subjekt --</option>';
print '<option value="info">ℹ️ Info - Opća informacija</option>';
print '<option value="upozorenje">⚠️ Upozorenje - Važno upozorenje</option>';
print '<option value="nadogradnja">🔄 Nadogradnja - Ažuriranje sustava</option>';
print '<option value="hitno">🚨 Hitno - Zahtijeva hitnu akciju</option>';
print '<option value="vazno">⭐ Važno - Značajna obavijest</option>';
print '</select>';
print '</div>';

print '<div class="seup-form-group">';
print '<label for="sadrzaj">Sadržaj <span class="required">*</span></label>';
print '<textarea class="seup-form-control" id="sadrzaj" name="sadrzaj" rows="4" required placeholder="Kratak opis obavjesti (max 500 znakova)" maxlength="500"></textarea>';
print '<small class="seup-form-text">Preostalo znakova: <span id="charCount">500</span></small>';
print '</div>';

print '<div class="seup-form-group">';
print '<label for="vanjski_link">Vanjski Link (opcionalno)</label>';
print '<input type="url" class="seup-form-control" id="vanjski_link" name="vanjski_link" placeholder="https://primjer.hr/detalji">';
print '<small class="seup-form-text">Link na stranicu s više informacija</small>';
print '</div>';

print '<div class="seup-form-actions">';
print '<button type="submit" class="seup-btn seup-btn-primary">';
print '<i class="fas fa-paper-plane"></i> Objavi Obavjest';
print '</button>';
print '</div>';

print '</form>';

print '</div>';
print '</div>';

// List of notifications
print '<div class="seup-card" style="margin-top: 30px;">';
print '<div class="seup-card-header">';
print '<h3><i class="fas fa-list"></i> Sve Aktivne Obavjesti</h3>';
print '</div>';
print '<div class="seup-card-body">';

if (count($obavjesti) > 0) {
    print '<div class="seup-obavjesti-list">';

    foreach ($obavjesti as $obavjest) {
        $subjekt_icon = '';
        $subjekt_class = '';

        switch ($obavjest->subjekt) {
            case 'info':
                $subjekt_icon = 'ℹ️';
                $subjekt_class = 'info';
                break;
            case 'upozorenje':
                $subjekt_icon = '⚠️';
                $subjekt_class = 'warning';
                break;
            case 'nadogradnja':
                $subjekt_icon = '🔄';
                $subjekt_class = 'upgrade';
                break;
            case 'hitno':
                $subjekt_icon = '🚨';
                $subjekt_class = 'urgent';
                break;
            case 'vazno':
                $subjekt_icon = '⭐';
                $subjekt_class = 'important';
                break;
        }

        print '<div class="seup-obavjest-item ' . $subjekt_class . '">';
        print '<div class="seup-obavjest-header">';
        print '<span class="seup-obavjest-subjekt">' . $subjekt_icon . ' ' . ucfirst($obavjest->subjekt) . '</span>';
        print '<span class="seup-obavjest-datum">' . dol_print_date(strtotime($obavjest->datum_kreiranja), '%d.%m.%Y %H:%M') . '</span>';
        print '</div>';
        print '<h4 class="seup-obavjest-naslov">' . dol_escape_htmltag($obavjest->naslov) . '</h4>';
        print '<p class="seup-obavjest-sadrzaj">' . nl2br(dol_escape_htmltag($obavjest->sadrzaj)) . '</p>';

        if ($obavjest->vanjski_link) {
            print '<p class="seup-obavjest-link">';
            print '<i class="fas fa-external-link-alt"></i> ';
            print '<a href="' . dol_escape_htmltag($obavjest->vanjski_link) . '" target="_blank">' . dol_escape_htmltag($obavjest->vanjski_link) . '</a>';
            print '</p>';
        }

        print '<div class="seup-obavjest-footer">';
        print '<small>Kreirao: ' . dol_escape_htmltag($obavjest->firstname . ' ' . $obavjest->lastname) . '</small>';
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=deactivate&id=' . $obavjest->rowid . '&token=' . newToken() . '" class="seup-btn seup-btn-sm seup-btn-danger" onclick="return confirm(\'Deaktivirati ovu obavjest?\');">';
        print '<i class="fas fa-trash"></i> Deaktiviraj';
        print '</a>';
        print '</div>';
        print '</div>';
    }

    print '</div>';
} else {
    print '<div class="seup-empty-state">';
    print '<i class="fas fa-inbox"></i>';
    print '<p>Nema aktivnih obavjesti</p>';
    print '</div>';
}

print '</div>';
print '</div>';

print '</div>';

// JavaScript
print '<script>';
print 'document.getElementById("sadrzaj").addEventListener("input", function() {';
print '  var remaining = 500 - this.value.length;';
print '  document.getElementById("charCount").textContent = remaining;';
print '});';
print '</script>';

llxFooter();
$db->close();
