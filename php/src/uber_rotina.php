<?php
require_once 'database.php'; // Inclui a classe de conexão com o banco de dados
require_once 'uber_func.php'; // Inclui as funções de manipulação de dados
session_start();

$log = "Start " . date('Y-m-d H:i:s') . " | ";
$dir = __DIR__;

$hostname = '{'.M_HOST.'/imap/ssl/novalidate-cert}INBOX';
$username = M_USER; 
$password = getpass(); 
// Connect to the IMAP server
$inbox = imap_open($hostname, $username, $password) or die('Cannot connect to Outlook: ' . imap_last_error());

// Search criteria
$searchFrom = M_from;
$searchText = M_body;
$searchSubject = M_subject; 

// Search for emails from noreply@uber.com containing 'Beatriz' in the body
$emails = imap_search($inbox, 'FROM "' . $searchFrom . '" TEXT "' . $searchText .  '" SUBJECT "' . $searchSubject . '"');

if ($emails) {
    // Sort emails by date (newest first)
    rsort($emails);
    foreach ($emails as $email_number) {
        $header = imap_headerinfo($inbox, $email_number);
        $subject = imap_mime_header_decode($header->subject);
        $decoded_subject = '';
        foreach ($subject as $part) {
            $decoded_subject .= $part->text;
        }
        $date = $header->date;
        
        $structure = imap_fetchstructure($inbox, $email_number);
        $body = imap_body($inbox, $email_number, FT_PEEK);
        // For multipart emails, try to get the text part
        if (isset($structure->parts) && is_array($structure->parts)) {
            // Try to get the plain text part (part 1)
            $body = imap_fetchbody($inbox, $email_number, 1);
            // If empty, try part 2 (might be HTML)
            if (empty(trim($body))) {
                $body = imap_fetchbody($inbox, $email_number, 2);
            }
        }
        
        $body = imap_body($inbox, $email_number, FT_PEEK);
        // Verifica a estrutura do e-mail para decodificação correta
        if (isset($structure->parts) && is_array($structure->parts)) {
            $body = imap_fetchbody($inbox, $email_number, 1);
            // Verifica o encoding (3 = BASE64, 4 = QUOTED-PRINTABLE)
            if ($structure->parts[0]->encoding == 3) {
                $body = base64_decode($body);
            } elseif ($structure->parts[0]->encoding == 4) {
                $body = quoted_printable_decode($body);
            }
            // Se for HTML, remove as tags
            if ($structure->parts[0]->subtype == 'HTML') {
                $body = strip_tags($body);
            }
        } else {
            // Para e-mails não multipart
            if ($structure->encoding == 3) {
                $body = base64_decode($body);
            } elseif ($structure->encoding == 4) {
                $body = quoted_printable_decode($body);
            }
        }
        // Remove espaços múltiplos e quebras de linha extras
        $body = preg_replace('/\s+/', ' ', trim($body));

        // Extração dos dados específicos da Uber
        $trip_data = [
            'motorista' => null,
            'distancia' => null,
            'tempo' => null,
            'origem' => null,
            'destino' => null,
            'valor_total' => null
        ];

        if (preg_match('/Você viajou com ([A-Za-z\s]+)\s+([0-9]{1,1}\.[0-9]{1,2})/', $body, $matches)) {
            $trip_data['motorista'] = trim($matches[1])." ".trim($matches[2]);
        }
        if (preg_match('/\d{2}\s+de\s+(jan(eiro)?|fev(ereiro)?|mar(ço)?|abr(il)?|mai(o)?|jun(ho)?|jul(ho)?|ago(sto)?|set(embro)?|out(ubro)?|nov(embro)?|dez(embro)?)\s+de\s+\d{4}/', $body, $matches)) {
            $trip_data['data'] = trim($matches[0]);
        }
        if (preg_match('/Obrigado por viajar,.*?(Beatriz)/i', $body, $matches)) {
            $trip_data['passageiro'] = trim($matches[1]);
        }
        if (preg_match('/([A-Za-z\s]+)\s+(\d+\.\d+)\s*Quilômetros\s*\|\s*(\d+\s*min)/', $body, $matches)) {
            $trip_data['tipo'] = trim($matches[1]);
            $trip_data['distancia'] = $matches[2] . ' km';
            $trip_data['tempo'] = $matches[3];
        }
        if (preg_match_all('/\d{2}:\d{2}\s+([^\[\]@]+?)\s+\d{5}-\d{3}\s+/', $body, $matches)) {
            if (count($matches[1]) >= 2) {
                $trip_data['origem'] = trim($matches[0][0]);
                $trip_data['destino'] = trim($matches[0][1]);
            }
        }
        if (preg_match('/Total\s+R\$\s*([\d,]+)/', $body, $matches)) {
            $trip_data['valor_total'] = 'R$ ' . $matches[1];
        }
        if (preg_match('/^(\d{2}:\d{2})\s+(.*)$/', $trip_data['origem'], $matches)) {
            $trip_data['hr_origem'] = trim($matches[1]);
            $trip_data['origem'] = trim($matches[2]);
        }
        if (preg_match('/^(\d{2}:\d{2})\s+(.*)$/', $trip_data['destino'], $matches)) {
            $trip_data['hr_destino'] = trim($matches[1]);
            $trip_data['destino'] = trim($matches[2]);
        }

        $trip_data['data_db'] = convertDateToDatabaseFormat($trip_data['data']);

        try {
            $db = DatabaseConnection::getInstance()->getConnection();
            // Valida se o registro já está na base de dados
            $stmt = $db->prepare("SELECT COUNT(*) FROM uber WHERE data_hr = :data_hr AND hr_origem = :hr_origem");
            $stmt->execute([
                ':data_hr' => $trip_data['data_db'],
                ':hr_origem' => $trip_data['hr_origem']
            ]);
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $log .= " skipped $trip_data[motorista] - $trip_data[tipo] - $trip_data[distancia] - $trip_data[valor_total] - $trip_data[data_db] - $trip_data[hr_origem] | ";
                continue; // Pula para o próximo e-mail
            }
            // Insere os dados na tabela 'uber'
            $stmpt = $db->prepare("REPLACE INTO uber (motorista, passageiro, tipo, distancia, tempo, origem, destino, valor_total, hr_origem, hr_destino, data_hr) 
                VALUES (:motorista, :passageiro, :tipo, :distancia, :tempo, :origem, :destino, :valor_total, :hr_origem, :hr_destino, :data_hr)");
            $stmpt->execute([
                ':motorista' => $trip_data['motorista'],
                ':passageiro' => $trip_data['passageiro'],
                ':tipo' => $trip_data['tipo'],
                ':distancia' => floatval(str_replace(' km', '',$trip_data['distancia'])),
                ':tempo' => $trip_data['tempo'],
                ':origem' => $trip_data['origem'],
                ':destino' => $trip_data['destino'],
                ':valor_total' => formatarValor($trip_data['valor_total']),
                ':hr_origem' => $trip_data['hr_origem'],
                ':hr_destino' => $trip_data['hr_destino'],
                ':data_hr' => $trip_data['data_db']
            ]);
            $log .= " Email $trip_data[motorista] - $trip_data[tipo] - $trip_data[distancia] - $trip_data[valor_total] - $trip_data[data_db] - $trip_data[hr_origem] insert sucess | ";
        } catch (PDOException $e) {
            $log .= " Error $trip_data[motorista] - $trip_data[tipo] - $trip_data[distancia] - $trip_data[valor_total] - $trip_data[data_db] - $trip_data[hr_origem] " . $e->getMessage() . "  | ";
        }
    
    }

} else {
    $log .= " No emails found! | ";    
}
// Close the connection
imap_close($inbox);
$log .= "End " . date('Y-m-d H:i:s') . " \n";
//echo $log;
file_put_contents($dir . '/uber.log', $log, FILE_APPEND);

?>