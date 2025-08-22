<?php
// api.php – läuft auf dem Hoster
/*
Das Script hat zwei Modi:

GET action=lastsync ? gibt den letzten Zeitstempel zurück

POST mit JSON ? nimmt neue Werte an
*/
header('Content-Type: application/json');


// API-Key prüfen
$apikey = $_GET['apikey'] ?? '';
if ($apikey !== 'MEIN_GEHEIMER_KEY') {
    http_response_code(403);
    echo json_encode(["error" => "Ungültiger API-Key"]);
    exit;
}
array $limaDB = [
        'host' => 'localhost',            // bei Lima nicht db.lima-city.de!
        'port' => 3306,
        'user' => 'USER261774',
        'pass' => 'sql666sql',
        'db'   => 'db_261774_20',
    ];
// DB-Verbindung

//$mysqli = new mysqli("localhost", "u123456", "geheim", "db123456");  // default 3306
//$mysqli = new mysqli("localhost", "u123456", "geheim", "db123456", 3306);
$masterDb = new mysqli($limaDB['localhost'], $limaDB['user'],$limaDB['pass'],$limaDB['db']);

if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "DB-Verbindung fehlgeschlagen"]);
    exit;
}

// -------------------------
// Modus 1: Letzten Sync abfragen
// -------------------------
if ($_GET['action'] === 'lastsync') {
    // Erlaubte Werte
    $allowed = ['config_push', 'sensorvalue_pull'];

    // $_GET['action'] prüfen (Fallback = null)
    $type = $_GET['action'] ?? null;

    if (!in_array($type, $allowed, true)) {

        $res = $masterDb->query("SELECT COUNT(*) FROM tl_coh_sync_log WHERE sync_type='$type'");
        if ($res && ($res->fetch_row()[0] == 0)) { // wenn nich da, neu anlegen
            $masterDb->query("
                INSERT INTO tl_coh_sync_log (sync_type, last_sync, tstamp)
                    VALUES ('$type', '1970-01-01 00:00:00', UNIX_TIMESTAMP())
                    ");
                $output?->writeln("<comment>Sync-Eintrag für '$type' automatisch angelegt.</comment>");
                $this->logger->debugMe("Sync-Eintrag für '$type' automatisch angelegt.");
        }
        $sql="SELECT tstamp FROM `tl_coh_sync_log` WHERE `sync_type` = $type"; 
        $res = $mysqli->query($sql);
        $row = $res->fetch_assoc();
        $lastSync = (int)($row['lastSync'] ?? 0);
        echo json_encode(["lastSync" => $lastSync]);
    }
    exit;
}

// -------------------------
// Modus 2: Neue Werte per POST annehmen
// -------------------------
// 5. Aktion: Neue Werte annehmen (Push)
if ($action === 'push') {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(["error" => "Ungültiges JSON"]);
        exit;
    }

    $sql = "INSERT INTO tl_coh_sensorvalue 
            (sensorID, sensorValue, sensorEinheit, sensorValueType, sensorSource, tstamp)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                sensorValue = VALUES(sensorValue),
                sensorEinheit = VALUES(sensorEinheit),
                sensorValueType = VALUES(sensorValueType),
                sensorSource = VALUES(sensorSource),
                tstamp = VALUES(tstamp)";

    $stmt = $mysqli->prepare($sql);

    foreach ($data as $row) {
        $stmt->bind_param(
            "sssssi",
            $row['sensorID'],
            $row['sensorValue'],
            $row['sensorEinheit'],
            $row['sensorValueType'],
            $row['sensorSource'],
            $row['tstamp']
        );
        $stmt->execute();
    }

    $stmt->close();
    $mysqli->close();

    echo json_encode(["status" => "ok", "rows" => count($data)]);
    exit;
}