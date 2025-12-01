<?php
/**
 * Test Notification Bell AJAX Endpoint
 * Direct test of obavjesti_ajax.php functionality
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

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Notification Bell</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #4f46e5; padding-bottom: 10px; }
        .test-section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #4f46e5; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        button { background: #4f46e5; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #3730a3; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .info { background: #e0f2fe; padding: 10px; border-left: 4px solid #0284c7; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔔 Notification Bell - AJAX Test</h1>

        <div class="info">
            <strong>User ID:</strong> <?php echo $user->id; ?><br>
            <strong>User Name:</strong> <?php echo $user->firstname . ' ' . $user->lastname; ?>
        </div>

        <div class="test-section">
            <h2>1. Database Test</h2>
            <p>Provjera tablica i podataka...</p>
            <?php
            $count = Obavjesti_helper::getUnreadCountForUser($db, $user->id);
            echo "<p>Broj nepročitanih obavjesti: <strong>$count</strong></p>";

            $obavjesti = Obavjesti_helper::getUnreadObavjestiForUser($db, $user->id);
            echo "<p>Dohvaćeno obavjesti: <strong>" . count($obavjesti) . "</strong></p>";

            if (count($obavjesti) > 0) {
                echo "<details><summary>Prikaži obavjesti</summary><pre>";
                print_r($obavjesti);
                echo "</pre></details>";
            }
            ?>
        </div>

        <div class="test-section">
            <h2>2. AJAX Endpoint Test</h2>
            <p>Testiranje direktnih poziva na obavjesti_ajax.php</p>

            <button onclick="testGetNotifications()">Test GET Notifications</button>
            <button onclick="testMarkAllRead()">Test Mark All Read</button>

            <div id="ajax-result" style="margin-top: 20px;"></div>
        </div>

        <div class="test-section">
            <h2>3. Create Test Notification</h2>
            <button onclick="createTestNotification()">Kreiraj Test Obavjest</button>
            <div id="create-result" style="margin-top: 10px;"></div>
        </div>

        <div class="test-section">
            <h2>4. Console Output</h2>
            <p>Otvori Browser Console (F12) za detaljne logove</p>
        </div>

        <hr>
        <p><a href="seupindex.php">← Natrag na SEUP</a></p>
    </div>

    <script>
        function testGetNotifications() {
            console.log('Testing GET notifications...');
            const resultDiv = document.getElementById('ajax-result');
            resultDiv.innerHTML = '<p>Loading...</p>';

            fetch('/custom/seup/class/obavjesti_ajax.php?action=get_notifications')
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    resultDiv.innerHTML = `
                        <p class="success">✓ AJAX Call Successful!</p>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultDiv.innerHTML = `
                        <p class="error">✗ AJAX Call Failed!</p>
                        <pre>${error.message}</pre>
                    `;
                });
        }

        function testMarkAllRead() {
            console.log('Testing Mark All Read...');
            const resultDiv = document.getElementById('ajax-result');
            resultDiv.innerHTML = '<p>Loading...</p>';

            fetch('/custom/seup/class/obavjesti_ajax.php?action=mark_all_read')
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    resultDiv.innerHTML = `
                        <p class="success">✓ Mark All Read Successful!</p>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultDiv.innerHTML = `
                        <p class="error">✗ Mark All Read Failed!</p>
                        <pre>${error.message}</pre>
                    `;
                });
        }

        function createTestNotification() {
            const resultDiv = document.getElementById('create-result');
            resultDiv.innerHTML = '<p>Creating test notification...</p>';

            // Use a simple XMLHttpRequest to create notification
            fetch('test_notification_ajax.php?create_test=1')
                .then(response => response.text())
                .then(data => {
                    resultDiv.innerHTML = `<p class="success">✓ ${data}</p>`;
                    // Reload count
                    testGetNotifications();
                })
                .catch(error => {
                    resultDiv.innerHTML = `<p class="error">✗ Error: ${error.message}</p>`;
                });
        }
    </script>
</body>
</html>

<?php
// Handle create test notification request
if (isset($_GET['create_test']) && $_GET['create_test'] == 1) {
    $result = Obavjesti_helper::createObavjest(
        $db,
        'Test Obavjest ' . date('H:i:s'),
        'info',
        'Ovo je automatski kreirana test obavjest za provjeru funkcionalnosti notification bell sustava.',
        'https://8core.hr',
        $user->id
    );

    if ($result) {
        echo "Test obavjest kreirana! ID: $result";
    } else {
        echo "Greška pri kreiranju obavjesti: " . $db->lasterror();
    }
    exit;
}

$db->close();
?>
