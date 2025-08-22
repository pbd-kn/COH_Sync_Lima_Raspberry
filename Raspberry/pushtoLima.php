<?php
// pushtoLima.php – läuft auf dem Raspi
/*
-------------------------------------------------
Ablauf:
1. Der Raspberry ruft zuerst api.php?action=lastsync auf (per HTTPS).
   -> Die API liefert den letzten gespeicherten Zeitstempel zurück.

2. Der Raspberry liest alle lokalen Datensätze aus tl_coh_sensorvalue,
   die neuer sind als dieser Zeitstempel.

3. Diese Datensätze werden als JSON an api.php geschickt (POST).

4. Die API speichert sie in der Hoster-Datenbank (ON DUPLICATE KEY UPDATE).

-------------------------------------------------
Cronjob auf dem Raspberry:
*/5 * * * * /usr/bin/php /home/pi/push.php >> /home/pi/push.log 2>&1

-------------------------------------------------
Sicherheit:
- Zugriff nur mit API-Key (?apikey=MEIN_GEHEIMER_KEY)
- HTTPS verwenden
- Optional: IP-Restriktion auf Raspi

-------------------------------------------------
Mit dieser Lösung bleibt der Raspberry sicher hinter der Fritzbox.
Die Kommunikation erfolgt nur ausgehend über HTTPS zum Hoster.
*/

$baseUrl = "https://sync.deinedomain.de/api.php?apikey=MEIN_GEHEIMER_KEY";

// 1. Letzten Sync vom Hoster holen
$lastSyncUrl = $baseUrl . "&action=lastsync";
$lastSyncResponse = file_get_contents($lastSyncUrl);
$lastSyncData = json_decode($lastSyncResponse, true);
$lastSync = $lastSyncData['lastSync'] ?? 0;

echo "Letzter Sync laut Hoster: $lastSync\n";

// 2. Neue Daten aus der lokalen DB holen
$slaveDb = new mysqli("localhost", "raspiuser", "raspipass", "co5_solar");
if ($slaveDb->connect_error) {
    die("Lokale DB-Verbindung fehlgeschlagen: " . $slaveDb->connect_error);
}

$stmt = $slaveDb->prepare("SELECT sensorID, sensorValue, sensorEinheit, sensorValueType, sensorSource, tstamp
                           FROM tl_coh_sensorvalue WHERE tstamp > ?");
$stmt->bind_param("i", $lastSync);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($r = $result->fetch_assoc()) {
    $data[] = $r; // enthält alle Spalten
}
$stmt->close();
$slaveDb->close();

if (empty($data)) {
    echo "Keine neuen Werte.\n";
    exit;
}

// 3. Neue Werte als JSON an Hoster senden
$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
        'timeout' => 20
    ]
];
$context = stream_context_create($options);
$response = file_get_contents($baseUrl . "&action=push", false, $context);

echo "Antwort vom Hoster: $response\n";
