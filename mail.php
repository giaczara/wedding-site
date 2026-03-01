<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Create logs directory if it doesn't exist
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// ========== LOAD ENVIRONMENT VARIABLES ==========
// Load from .env file if it exists (for local development)
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove quotes if present
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            putenv("$key=$value");
        }
    }
}

// ========== RSVP EMAIL CONFIGURATION ==========
// Get configuration from environment variables (or use defaults)
$recipient_email = getenv('RECIPIENT_EMAIL') ?: '';
$sender_email = getenv('SENDER_EMAIL') ?: '';
$sender_name = getenv('SENDER_NAME') ?: 'Wedding RSVP';
$subject = "Nuova RSVP ricevuta - Emanuela & Giacomo";

// GMAIL SMTP SETTINGS
$use_smtp = true;
$smtp_host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
$smtp_port = (int)(getenv('SMTP_PORT') ?: 587);
$smtp_username = getenv('SMTP_USERNAME') ?: '';
$smtp_password = getenv('SMTP_PASSWORD') ?: '';

// Verify configuration
if (empty($recipient_email) || empty($sender_email) || empty($smtp_username) || empty($smtp_password)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore di configurazione del server. Per favore contatta l\'amministratore.'
    ]);
    error_log("RSVP Configuration Error: Missing required environment variables");
    exit;
}

// ================================================

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Richiesta non valida.']);
    exit;
}

// Get form data
$name = isset($_POST['name']) ? sanitize_input($_POST['name']) : "";
$email = isset($_POST['email']) ? sanitize_input($_POST['email']) : "";
$guests = isset($_POST['guests']) ? sanitize_input($_POST['guests']) : "0";
$message = isset($_POST['message']) ? sanitize_input($_POST['message']) : "";

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Per favore inserisci un\'email valida.']);
    exit;
}

// Validate name
if (empty($name) || strlen($name) < 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Per favore inserisci il tuo nome.']);
    exit;
}

// Prepare email content
$guest_count = get_guest_count($guests);
$email_body = "Nuova RSVP ricevuta dalla pagina del matrimonio:\n\n";
$email_body .= "Nome: " . $name . "\n";
$email_body .= "Email: " . $email . "\n";
$email_body .= "Numero Ospiti: " . $guest_count . "\n";
$email_body .= "Messaggio Aggiuntivo: " . (!empty($message) ? $message : "Nessun messaggio") . "\n\n";
$email_body .= "---\n";
$email_body .= "Data e ora della ricezione: " . date('d/m/Y H:i:s') . "\n";
$email_body .= "Indirizzo IP: " . $_SERVER['REMOTE_ADDR'] . "\n";

// Send email
$mail_sent = false;
$error_message = "";
$log_file = $log_dir . '/rsvp_' . date('Y-m-d') . '.log';

try {
    if ($use_smtp && !empty($smtp_password)) {
        // Use SMTP (recommended for reliability)
        $mail_sent = send_via_smtp(
            $recipient_email,
            $sender_email,
            $sender_name,
            $subject,
            $email_body,
            $smtp_host,
            $smtp_port,
            $smtp_username,
            $smtp_password,
            $error_message
        );
    } else {
        // Fallback to native mail() function
        $headers = "From: " . $sender_name . " <" . $sender_email . ">\r\n";
        $headers .= "Reply-To: " . $email . "\r\n";
        $headers .= "Return-Path: " . $sender_email . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "X-Mailer: Wedding-RSVP\r\n";

        ini_set('sendmail_from', $sender_email);
        
        if (mail($recipient_email, $subject, $email_body, $headers)) {
            $mail_sent = true;
        } else {
            $error_message = "mail() function returned false - May need SMTP configuration";
        }
    }

    if ($mail_sent) {
        $response = [
            'success' => true,
            'message' => 'Grazie! La tua RSVP è stata ricevuta con successo. Ti contatteremo presto per confermare.'
        ];
        echo json_encode($response);
    } else {
        $response = [
            'success' => false,
            'message' => 'Si è verificato un errore durante l\'invio della tua RSVP. Per favore riprova più tardi.'
        ];
        http_response_code(500);
        echo json_encode($response);
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $response = [
        'success' => false,
        'message' => 'Si è verificato un errore durante l\'invio della tua RSVP. Per favore riprova più tardi.'
    ];
    http_response_code(500);
    echo json_encode($response);
}

// Log the submission with detailed information
$log_entry = sprintf(
    "[%s] Name: %s | Email: %s | Guests: %s | Mail Sent: %s | Error: %s | IP: %s\n",
    date('Y-m-d H:i:s'),
    $name,
    $email,
    $guest_count,
    ($mail_sent ? 'YES' : 'NO'),
    ($error_message ?: 'None'),
    $_SERVER['REMOTE_ADDR']
);

file_put_contents($log_file, $log_entry, FILE_APPEND);

// Also log to PHP error log for server admin
if (!$mail_sent) {
    error_log("RSVP Submission Failed - Name: $name, Email: $email, Error: " . $error_message);
}

// ========== HELPER FUNCTIONS ==========

function sanitize_input($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

function get_guest_count($value) {
    $guests = array(
        "0" => "1 Ospite",
        "1" => "2 Ospiti",
        "2" => "3 Ospiti",
        "3" => "4 Ospiti"
    );
    return isset($guests[$value]) ? $guests[$value] : "1 Ospite";
}

function send_via_smtp($to, $from, $from_name, $subject, $body, $host, $port, $username, $password, &$error) {
    try {
        // Create socket connection with stream for better TLS support
        $context = stream_context_create();
        $socket = @stream_socket_client("tcp://$host:$port", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
        
        if (!$socket) {
            $error = "Could not connect to SMTP server: $errstr ($errno)";
            return false;
        }

        stream_set_blocking($socket, true);
        stream_set_timeout($socket, 10);

        // Read server response
        $response = read_smtp_response($socket);
        if (strpos($response, '220') === false) {
            $error = "SMTP connection failed: " . trim($response);
            fclose($socket);
            return false;
        }

        // Send EHLO
        fputs($socket, "EHLO wedding-site\r\n");
        $response = read_smtp_response($socket);
        if (strpos($response, '250') === false) {
            $error = "EHLO failed: " . trim($response);
            fclose($socket);
            return false;
        }

        // Start TLS
        fputs($socket, "STARTTLS\r\n");
        $response = read_smtp_response($socket);
        if (strpos($response, '220') === false) {
            $error = "STARTTLS failed: " . trim($response);
            fclose($socket);
            return false;
        }

        // Enable encryption
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
            $error = "Could not enable TLS encryption";
            fclose($socket);
            return false;
        }

        // Send EHLO again after TLS
        fputs($socket, "EHLO wedding-site\r\n");
        $response = read_smtp_response($socket);
        if (strpos($response, '250') === false) {
            $error = "EHLO after TLS failed: " . trim($response);
            fclose($socket);
            return false;
        }

        // Authenticate
        fputs($socket, "AUTH LOGIN\r\n");
        $response = read_smtp_response($socket);
        if (strpos($response, '334') === false) {
            $error = "AUTH LOGIN failed: " . trim($response);
            fclose($socket);
            return false;
        }

        fputs($socket, base64_encode($username) . "\r\n");
        $response = read_smtp_response($socket);
        if (strpos($response, '334') === false) {
            $error = "Username authentication failed: " . trim($response);
            fclose($socket);
            return false;
        }

        fputs($socket, base64_encode($password) . "\r\n");
        $response = read_smtp_response($socket);
        if (strpos($response, '235') === false) {
            $error = "Password authentication failed: " . trim($response);
            fclose($socket);
            return false;
        }

        // Send FROM
        fputs($socket, "MAIL FROM: <$from>\r\n");
        $response = read_smtp_response($socket);
        if (strpos($response, '250') === false) {
            $error = "MAIL FROM failed: " . trim($response);
            fclose($socket);
            return false;
        }

        // Send TO
        fputs($socket, "RCPT TO: <$to>\r\n");
        $response = read_smtp_response($socket);
        if (strpos($response, '250') === false) {
            $error = "RCPT TO failed: " . trim($response);
            fclose($socket);
            return false;
        }

        // Send DATA
        fputs($socket, "DATA\r\n");
        $response = read_smtp_response($socket);
        if (strpos($response, '354') === false) {
            $error = "DATA command failed: " . trim($response);
            fclose($socket);
            return false;
        }

        // Prepare headers
        $headers = "From: $from_name <$from>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "X-Mailer: Wedding-RSVP\r\n\r\n";

        // Send message
        fputs($socket, $headers . $body . "\r\n.\r\n");
        $response = read_smtp_response($socket);

        if (strpos($response, '250') === false) {
            $error = "Failed to send message: " . trim($response);
            fclose($socket);
            return false;
        }

        // Close connection
        fputs($socket, "QUIT\r\n");
        fclose($socket);

        return true;

    } catch (Exception $e) {
        $error = $e->getMessage();
        return false;
    }
}

function read_smtp_response($socket) {
    $response = "";
    while (!feof($socket)) {
        $line = fgets($socket, 512);
        if ($line === false) break;
        $response .= $line;
        // Check if this is the last line (no dash after code)
        if (preg_match('/^\d{3} /', $line)) {
            break;
        }
    }
    return $response;
}
?>
