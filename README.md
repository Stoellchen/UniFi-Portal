Hier ist eine strukturierte README.md, die du direkt in dein GitHub-Projekt hochladen kannst. Sie ist so geschrieben, dass du (oder jemand anderes) das Projekt auf einem komplett leeren Server von Null an aufsetzen kann.

🏰 UniFi Guest Portal - Résidence Setup
Dieses Projekt bietet ein professionelles Captive Portal für UniFi-Netzwerke mit automatischer Bandbreitenzuweisung (VIP, Friend, Guest, Standard) basierend auf Zugangscodes.

📋 Voraussetzungen
Ein Webserver mit PHP 7.4 oder höher.

Installiertes curl PHP-Modul.

Ein UniFi Controller (Self-hosted oder Cloud Key), der vom Webserver aus per HTTPS erreichbar ist.

🚀 Installation & Setup
1. Dateien auf den Server laden
Kopiere alle Dateien aus diesem Repository in das Web-Verzeichnis deines Servers (z. B. /var/www/html/ oder per FTP).

2. Konfiguration erstellen (WICHTIG)
Da die Datei config.php aus Sicherheitsgründen nicht im Repository enthalten ist (siehe .gitignore), musst du diese manuell erstellen:

Erstelle eine Datei namens config.php im Hauptordner.

Kopiere den folgenden Inhalt hinein und setze deine echten Daten ein:

PHP
<?php
return [
    'controller' => [
        'user'     => 'DEIN_UNI_USER',
        'password' => 'DEIN_PASSWORT',
        'url'      => 'https://DEINE-IP-ODER-URL:11443',
        'site_id'  => 'DEINE_SITE_ID',
    ],
    'profiles' => [
        'VIP' => [
            'passwords'  => ['ResidanceVIP24'],
            'duration'   => 10080, // 7 Tage
            'speed_down' => 100000, // 100 Mbps
            'speed_up'   => 100000,
            'label'      => 'VIP',
            'welcome'    => "Bienvenue, cher VIP %s !"
        ],
        'STANDARD' => [
            'passwords'  => [],
            'duration'   => 480, // 8 Stunden
            'speed_down' => 25000, // 25 Mbps
            'speed_up'   => 25000,
            'label'      => 'Standard',
            'welcome'    => "Merci %s !"
        ],
        // Weitere Profile (FRIEND, GUEST) hier ergänzen...
    ]
];
3. Schreibrechte setzen
Damit das System E-Mails loggen und Bilder verarbeiten kann, müssen folgende Ordner/Dateien für den Webserver beschreibbar sein:

Bash
chmod 666 emails.txt
chmod 755 assets/background_images/
4. UniFi Controller Einstellungen
Damit die Gäste auf dieses Portal geleitet werden, musst du im UniFi Controller folgendes einstellen:

Settings > Profiles > Guest Control:

Authentication: External Portal Server.

IP Address: Die IP deines Webservers.

Settings > WiFi:

Dein Gäste-WLAN bearbeiten.

Guest Hotline: Aktivieren.

Bandbreiten-Profile:

Erstelle die Profile (VIP, Amis, Invite, Etranger) unter Settings > Profiles > Bandwidth Profiles mit den entsprechenden Limits.

📂 Ordnerstruktur
index.php - Die Hauptseite (Logik & Formular).

config.php - Deine privaten Zugangsdaten (wird von Git ignoriert).

Client.php - Die UniFi API Klasse.

assets/ - Enthält CSS und Hintergrundbilder.

emails.txt - Hier werden die Logins gespeichert.

🛡️ Sicherheitshinweise
Lade niemals deine config.php auf ein öffentliches GitHub-Repository hoch.

Das Projekt ist aktuell so eingestellt, dass es in einem privaten Repository aufbewahrt werden sollte.

Die .gitignore Datei sorgt dafür, dass deine Passwörter und die Gästeliste (emails.txt) lokal auf deinem Server bleiben.

📝 Lizenz
Privates Projekt für die Résidence. Alle Rechte vorbehalten.

Tipp für GitHub:
Wenn du diese Datei als README.md speicherst und hochlädst, zeigt GitHub sie automatisch schön formatiert auf der Startseite deines Projekts an!
