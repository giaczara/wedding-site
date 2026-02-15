<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ========== RSVP EMAIL CONFIGURATION ==========
// CHANGE THIS EMAIL ADDRESS TO WHERE YOU WANT TO RECEIVE THE RSVP SUBMISSIONS
$recipient_email = "zaragiacomo95@gmail.com";  // <-- CHANGE TO YOUR EMAIL
$wedding_email = "emanuela.giacomo@example.com"; // Optional: From address
$subject = "Nuova RSVP ricevuta - Emanuela & Giacomo";

// ================================================

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo "<div class='alert alert-danger'>Richiesta non valida.</div>";
    exit;
}

// Get form data
$name = isset($_POST['name']) ? sanitize_input($_POST['name']) : "";
$email = isset($_POST['email']) ? sanitize_input($_POST['email']) : "";
$guests = isset($_POST['guests']) ? sanitize_input($_POST['guests']) : "1";
$message = isset($_POST['message']) ? sanitize_input($_POST['message']) : "";

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo "<div class='alert alert-danger'>Per favore inserisci un'email valida.</div>";
    exit;
}

// Validate name
if (empty($name)) {
    http_response_code(400);
    echo "<div class='alert alert-danger'>Per favore inserisci il tuo nome.</div>";
    exit;
}

// Prepare email content
$email_body = "
Nuova RSVP ricevuta dalla pagina del matrimonio:

Nome: $name
Email: $email
Numero Ospiti: " . get_guest_count($guests) . "
Messaggio Aggiuntivo: " . (!empty($message) ? nl2br($message) : "Nessun messaggio") . "

---
Data e ora della ricezione: " . date('d/m/Y H:i:s') . "
Indirizzo IP: " . $_SERVER['REMOTE_ADDR'] . "
";

// Email headers
$headers = "From: " . $wedding_email . "\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Send email
$mail_sent = false;
$error_message = "";

try {
    // Add required headers for better email delivery
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    if (mail($recipient_email, $subject, $email_body, $headers)) {
        $mail_sent = true;
        echo "<div class='alert alert-success'>Grazie! La tua RSVP è stata ricevuta con successo. Ti contatteremo presto per confermare.</div>";
    } else {
        $error_message = "Mail function returned false";
        echo "<div class='alert alert-danger'>Si è verificato un errore durante l'invio della tua RSVP. Per favore riprova più tardi.</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Si è verificato un errore durante l'invio della tua RSVP. Per favore riprova più tardi.</div>";
}

// Log the submission
error_log("RSVP Submission - Name: $name, Email: $email, Guests: " . get_guest_count($guests) . ", Mail sent: " . ($mail_sent ? 'Yes' : 'No') . ($error_message ? ", Error: $error_message" : ""));

// ========== HELPER FUNCTIONS ==========

function sanitize_input($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input);
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
?>
