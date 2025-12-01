<?php
/**
 * Plaćena licenca
 * (c) 2025 8Core Association
 * Tomislav Galić <tomislav@8core.hr>
 * Marko Šimunović <marko@8core.hr>
 * Web: https://8core.hr
 */

/**
 * AJAX endpoint for notification operations
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
    die(json_encode(['success' => false, 'error' => 'Cannot load Dolibarr environment']));
}

require_once __DIR__ . '/obavjesti_helper.class.php';

header('Content-Type: application/json');

// Security check - must be logged in
if (!$user || !$user->id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = GETPOST('action', 'aZ09');

try {
    switch ($action) {
        case 'get_notifications':
            $obavjesti = Obavjesti_helper::getUnreadObavjestiForUser($db, $user->id);
            $count = count($obavjesti);

            $notifications = [];
            foreach ($obavjesti as $obavjest) {
                $notifications[] = [
                    'id' => $obavjest->rowid,
                    'naslov' => $obavjest->naslov,
                    'subjekt' => $obavjest->subjekt,
                    'sadrzaj' => $obavjest->sadrzaj,
                    'vanjski_link' => $obavjest->vanjski_link,
                    'datum' => date('d.m.Y H:i', strtotime($obavjest->datum_kreiranja)),
                    'kreirao' => trim($obavjest->firstname . ' ' . $obavjest->lastname)
                ];
            }

            echo json_encode([
                'success' => true,
                'count' => $count,
                'notifications' => $notifications
            ]);
            break;

        case 'mark_read':
            $obavjest_id = GETPOST('id', 'int');

            if (!$obavjest_id) {
                throw new Exception('ID obavjesti nije pronađen');
            }

            $result = Obavjesti_helper::markAsRead($db, $obavjest_id, $user->id);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Obavjest označena kao pročitana'
                ]);
            } else {
                throw new Exception('Greška pri označavanju obavjesti');
            }
            break;

        case 'mark_all_read':
            $result = Obavjesti_helper::markAllAsRead($db, $user->id);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Sve obavjesti označene kao pročitane'
                ]);
            } else {
                throw new Exception('Greška pri označavanju obavjesti');
            }
            break;

        case 'delete':
            $obavjest_id = GETPOST('id', 'int');

            if (!$obavjest_id) {
                throw new Exception('ID obavjesti nije pronađen');
            }

            $result = Obavjesti_helper::deleteForUser($db, $obavjest_id, $user->id);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Obavjest obrisana'
                ]);
            } else {
                throw new Exception('Greška pri brisanju obavjesti');
            }
            break;

        case 'delete_all':
            $result = Obavjesti_helper::deleteAllForUser($db, $user->id);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Sve obavjesti obrisane'
                ]);
            } else {
                throw new Exception('Greška pri brisanju obavjesti');
            }
            break;

        default:
            throw new Exception('Nepoznata akcija');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$db->close();
