# Netcup DDNS PHP Updater

Dieses PHP-Skript ermöglicht die automatische Aktualisierung von DNS-Einträgen über die Netcup DNS API – ideal für dynamische IP-Adressen (DDNS), wenn dein Internetanbieter regelmäßig deine IP-Adresse ändert.

## Funktionsweise

Das Skript:
- Authentifiziert sich über die Netcup DNS API,
- Ruft den aktuellen DNS-Eintrag für die angegebene Subdomain ab,
- Aktualisiert den A- und/oder AAAA-Eintrag mit der übermittelten IP-Adresse.

## Voraussetzungen

- PHP ≥ 7.0
- Zugriff auf die Netcup DNS API (API-Key, API-Passwort, Kundennummer)
- Eine bei Netcup registrierte Domain mit DNS-Zugriff

## Konfiguration

### 1. `.env`-Datei erstellen

Lege im gleichen Verzeichnis wie das Skript eine Datei namens `.env` mit folgendem Inhalt an:

```ini
username="deinUsernameZurAbsicherung"
password="deinPasswortZurAbsicherung"
apiKey="deinNetcupAPIKey"
apiPassword="deinNetcupAPIPasswort"
customerNumber="deineNetcupKundennummer"
domainname="example.de"
hostname="subdomain" ; z. B. "home" für home.example.de (auch Wildcard möglich, z.B. "*.home" für *.home.example.de)
```

> Die Felder `username` und `password` dienen dem Schutz des Skripts vor unbefugtem Zugriff. Verwende sichere Zugangsdaten!

### 2. Aufruf des Skripts

Du kannst das Skript regelmäßig von deiner Fritz!Box, einem Raspberry Pi oder einem anderen Gerät mit Internetzugang aufrufen:

```
https://yourdomain.tld/update_dns.php?username=deinUsernameZurAbsicherung&password=deinPasswortZurAbsicherung&ipv4=1.2.3.4"
```

Optional kannst du auch einen `ipv6`-Parameter mitgeben.

## Sicherheit

Das Skript verwendet Zugangsdaten über GET-Parameter – **nutze es nur über HTTPS!**

Stelle sicher, dass deine .env-Datei nicht öffentlich zugänglich ist. Du kannst sie z. B. mit einer .htaccess-Regel schützen:

```apache
<FilesMatch "^\.env$">
    Require all denied
</FilesMatch>
```

## Lizenz

Dieses Projekt steht unter der MIT-Lizenz. Siehe LICENSE für Details.
