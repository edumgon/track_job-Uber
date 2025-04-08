<?php
//Arquivo para centralizar as funções do sistema Uber

$dir = __DIR__;
require_once dirname(dirname($dir)) .'/env/env.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

define('M_HOST', $host);
define('M_USER', $user);
define('F_JSON', $ujson);
define('F_HASH', $hash);
define('M_to_filter', $to_filter);
define('M_from', $m_from);
define('M_subject', $m_subject);
define('M_body', $m_body);

// Converte a data do formato "dd de mês de aaaa" para "aaaa-mm-dd"
function convertDateToDatabaseFormat($dataTexto) {
    // Mapear meses em português para números
    $meses = [
        'janeiro' => '01',
        'fevereiro' => '02',
        'março' => '03',
        'abril' => '04',
        'maio' => '05',
        'junho' => '06',
        'julho' => '07',
        'agosto' => '08',
        'setembro' => '09',
        'outubro' => '10',
        'novembro' => '11',
        'dezembro' => '12',
    ];
    // Extrair apenas a parte relevante da data usando regex
    if (preg_match('/(\d{1,2}) de ([a-zA-Zç]+) de (\d{4})/', $dataTexto, $matches)) {
        $dia = str_pad($matches[1], 2, '0', STR_PAD_LEFT); // Garantir dois dígitos
        $mes = strtolower($matches[2]); // Nome do mês em minúsculas
        $ano = $matches[3];
        // Verificar se o mês existe no mapeamento
        if (isset($meses[$mes])) {
            $mesNumero = $meses[$mes];
            return "$ano-$mesNumero-$dia"; // Formato Y-m-d
        } else {
            die("Mês inválido: $mes");
        }
    } else {
        die("Formato de data inválido.");
    }
}

// Formata o valor removendo R$ e espaços
function formatarValor($valor) {
    return str_replace([',', 'R$', ' '], ['.', '', ''], $valor);
}
// New geraCode
function generateCode($email) {
    $code = bin2hex(random_bytes(7)); // Gera um código aleatório (ex.: "a1b2c3d4")
    $data = [
        $code => [
            'email' => $email,
            'created_at' => time(),
            'expires_at' => time() + 600, // Expira em 1 hora
            'used' => false
        ]
    ];

    // Lê o arquivo existente ou cria um novo
    $file = F_JSON;
    if (file_exists($file)) {
        $currentData = json_decode(file_get_contents($file), true);
        if(!is_array($currentData)) {
            $currentData = [];
        }
        $data = array_merge($currentData, $data);
    }

    // Salva no arquivo
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    return $code;
}
//Nem valida sessão
function validateCode($code) {
    $file = F_JSON;
    // Verifica se o arquivo existe
    if (!file_exists($file)) {
        return false;
    }
    // Lê o arquivo e decodifica o JSON
    $data = json_decode(file_get_contents($file), true);
    if (!isset($data[$code])) {
        return false; // Código não existe
    }
    $codeData = $data[$code];
    // Verifica se está expirado ou já foi usado
    /*if ($now > $codeData['expires_at'] || $codeData['used']) {
        return false;
    }*/
    // Marca como usado (opcional)
    $data[$code]['used'] = true;
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    return $codeData; // Retorna o e-mail associado
}

function cleanExpiredCodes() {
    $file = F_JSON;
    if (!file_exists($file)) return;

    $data = json_decode(file_get_contents($file), true);
    $now = time();
    if (!is_array($data)) {
        $data = [];
    } else {
        foreach ($data as $code => $info) {
            if ($now > $info['expires_at']) {
                unset($data[$code]);
            }
        }
    }
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}
function removeCode($code) {
    $file = F_JSON;
    if (!file_exists($file)) return;

    $data = json_decode(file_get_contents($file), true);
    if (isset($data[$code])) {
        unset($data[$code]);
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
        return true;
    }
    return false;
}

function sendAccessEmail($to, $code) {
    // Configurações de e-mail
    $email_host = M_HOST;
    $email_user = M_USER;
    $email_pass = getPass();
    $email_port = 993;
    //Defini link de acesso
    $host = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
    $host .= $_SERVER['HTTP_HOST'];
    $accessLink = $host . $_SERVER['PHP_SELF'] . "?code=" . $code;
        
    $subject = "Acesso ao Dashboard Uber";
    $message = "Ola,<br><br>Acesse o dashboard Uber atraves do <a href='$accessLink'>link</a> abaixo:<br>$accessLink<br><br>Este link e valido por apenas 5 minutos.<br><br>Atenciosamente,<br>Sistema Dashboard";
    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $email_host;
        $mail->SMTPAuth = true;
        $mail->Username = $email_user;
        $mail->Password = $email_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
    
        // Reipients
        $mail->setFrom($email_user, 'InfoEG');
        $mail->addAddress($to, $to);
        $mail->addReplyTo($email_user, 'InfoEG');
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $result = $mail->send();
        //echo "E-mail enviado com sucesso!";
    } catch (Exception $e) {
        $result = $mail->ErrorInfo;
        //echo "Erro ao enviar e-mail: {$mail->ErrorInfo}";
    }
    
    return $result;
}

function getPass()  {
    // Outlook.com IMAP settings
    if (!file_exists(F_HASH)) {
        die("Arquivo de senha não encontrado.");
    }
    list($ivBase64, $passBit64) = explode(':', file_get_contents(F_HASH));
    $pass = openssl_decrypt(base64_decode($passBit64), "aes-256-cbc", "f88f91453e8756b80d8cb34764ad5d65", 0, base64_decode($ivBase64));
    if ($pass === false) {
        die("Falha ao descriptografar a senha.");
    }
    return $pass;
}
?>