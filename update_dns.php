<?php
// Konfiguration aus .env Datei laden
if (!file_exists('.env')) {
    error_exit("Fehler: .env Datei nicht gefunden. Bitte erstellen Sie eine .env Datei mit den Netcup API-Zugangsdaten.");
}

$config = parse_ini_file('.env', false, INI_SCANNER_TYPED);

if (!$config || !isset($config['customerNumber'], $config['apiKey'], $config['apiPassword'], $config['domainname'], $config['hostname'])) {
    error_exit("Fehler: Ungültige Konfiguration. Bitte überprüfen Sie die .env Datei.");
}

// Authentifizierung
if (!isset($_GET['username'], $_GET['password']) || $_GET['username'] !== $config['username'] || $_GET['password'] !== $config['password']) {
    error_exit("Fehler: Ungültige Anmeldedaten.");
}

// IP-Adressen aus Anfrage holen und validieren
$ipv4 = isset($_GET['ipv4']) ? $_GET['ipv4'] : null;
$ipv6 = isset($_GET['ipv6']) ? $_GET['ipv6'] : null;

if (empty($ipv4) && empty($ipv6)) {
    error_exit("Keine IP-Adresse angegeben.");
}

if (!empty($ipv4) && !filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    error_exit("Ungültige IPv4-Adresse: $ipv4");
}

if (!empty($ipv6) && !filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
    error_exit("Ungültige IPv6-Adresse: $ipv6");
}

try {
    // Netcup API Login (Session-ID holen)
    $login_response = api_request('login', [
        'customernumber' => $config['customerNumber'],
        'apikey' => $config['apiKey'],
        'apipassword' => $config['apiPassword']
    ]);

    if (!$login_response || !isset($login_response['responsedata']['apisessionid'])) {
        error_exit("Login fehlgeschlagen.", json_encode($login_response));
    }

    $apisessionid = $login_response['responsedata']['apisessionid'];

    // Aktuelle DNS-Einträge abrufen
    $dns_info_response = api_request('infoDnsRecords', [
        'customernumber' => $config['customerNumber'],
        'apikey' => $config['apiKey'],
        'apisessionid' => $apisessionid,
        'domainname' => $config['domainname']
    ]);

    if (!$dns_info_response || !isset($dns_info_response['responsedata']['dnsrecords'])) {
        error_exit("Fehler beim Abrufen der DNS-Einträge.", json_encode($dns_info_response));
    }

    // ID des bestehenden Eintrags herausfinden
    $dnsrecords = [];
    foreach ($dns_info_response['responsedata']['dnsrecords'] as $record) {
        if ($record['hostname'] == $config['hostname']) {
            $dnsrecords[] = [
                'id' => $record['id'],
                'hostname' => $record['hostname'],
                'type' => $record['type'],
                'destination' => ($record['type'] == 'A') ? $ipv4 : $ipv6
            ];
        }
    }

    if (empty($dnsrecords)) {
        error_exit("Fehler: Kein passender DNS-Eintrag gefunden.");
    }

    // DNS Einträge aktualisieren
    $update_response = api_request('updateDnsRecords', [
        'customernumber' => $config['customerNumber'],
        'apikey' => $config['apiKey'],
        'apisessionid' => $apisessionid,
        'domainname' => $config['domainname'],
        'dnsrecordset' => ["dnsrecords" => $dnsrecords],
    ]);

    if (!$update_response || $update_response["status"] !== "success") {
        error_exit("Fehler beim Aktualisieren der DNS-Einträge.", json_encode($update_response));
    }

    echo "DNS-Einträge erfolgreich aktualisiert für {$config['hostname']}.{$config['domainname']}.<br>";
} finally {
    // Netcup API Logout (Session-ID beenden)
    $logout_response = api_request('logout', [
        'customernumber' => $config['customerNumber'],
        'apikey' => $config['apiKey'],
        'apisessionid' => $apisessionid
    ]);

    if (!$logout_response || $logout_response["status"] !== "success") {
        error_exit("Logout fehlgeschlagen.", json_encode($logout_response));
    }
}

// Funktion zum Senden von API-Anfragen
function api_request($action, $params) {
    $url = "https://ccp.netcup.net/run/webservice/servers/endpoint.php?JSON";
    $data = json_encode(["action" => $action, "param" => $params]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Funktion zum Beenden des Skripts mit einer Fehlermeldung
function error_exit($message, $response = null) {
    http_response_code(400); // Setzt HTTP-Status auf 400 (Bad Request)
    echo json_encode(["error" => $message, "details" => $response]);
    exit();
}
