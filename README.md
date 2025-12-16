# Wunschlisten-App (PHP + MySQL)

Eine schlanke, moderne Wunschlisten-Anwendung, die auf gängigem Webspace (z.B. Netcup) mit PHP und MySQL läuft. Sie bietet ein Admin-Panel für Geschenke, Kategorien und Einstellungen sowie einen sicheren Gastlink ohne Login.

## Features
- Installer (ähnlich wie WordPress): Datenbankzugang und Admin-Passwort eingeben, Tabellen werden automatisch erstellt.
- Admin-Panel: Geschenke anlegen/ändern/löschen, Kategorien verwalten, Passwort ändern, Gastlink neu generieren, Option zur Anzeige der Schenkenden aktivieren/deaktivieren.
- Gästeansicht: Zugriff über sicheren Link ohne Registrierung, Reservierung mit Namensangabe und Bestätigungsdialog. Reservierte Geschenke werden für alle sichtbar blockiert.
- Modernes, responsives Design mit klarer Typografie.

## Installation
1. Code auf den Webspace kopieren (z.B. per FTP).
2. Stelle sicher, dass PHP 8+ und eine MySQL-Datenbank verfügbar sind.
3. Rufe im Browser `install.php` auf und trage die Datenbank-Zugangsdaten sowie ein Admin-Passwort ein. Der Installer legt `config.php` an und erstellt die Tabellen.
4. Nach der Installation:
   - Admin-Panel: `admin.php`
   - Gäste-Link: `guest.php?token=<dein_token>` (Token wird im Installer angezeigt und kann im Admin-Panel neu erzeugt werden).

## Admin Panel
- Login mit dem im Installer gesetzten Passwort.
- Geschenke mit Titel, Beschreibung, Preis/Hinweis und optionaler Kategorie verwalten.
- Kategorien frei anlegen/löschen (z.B. Küche, Dekoration, Reise).
- Einstellungen: Passwort ändern, Gastlink neu generieren, Anzeige der Schenkenden für Gäste an/aus.

## Gästeansicht & Reservierung
- Gäste besuchen den sicheren Link ohne Anmeldung.
- Jede Reservierung erfordert eine Namensbestätigung. Optional wird der Name in der Liste angezeigt, abhängig von der Einstellung im Admin-Panel.
- Sobald ein Geschenk reserviert ist, wird es für alle anderen als „Reserviert“ markiert.

## Sicherheit & Hinweise
- Bewahre `config.php` außerhalb des öffentlichen Repos auf und stelle passende Dateirechte ein.
- Der Installer sollte nach erfolgreicher Einrichtung vom Server entfernt oder durch einen Server-Config-Schutz gesichert werden.
