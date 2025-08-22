Push-Strategie für Sensordaten (Variante B)

Dieses Paket enthält zwei PHP-Skripte und eine Anleitung, wie der Raspberry Pi 
Sensordaten sicher an den Hoster überträgt.

-------------------------------------------------
Dateien:
- api.php   (auf dem Hoster, z.B. sync.deinedomain.de)
- push.php  (auf dem Raspberry Pi)
- README.txt (diese Anleitung)

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
