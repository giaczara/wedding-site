<?php
// Test version that saves to file instead of sending email
error_reporting(E_ALL);
ini_set('display_errors', 0);

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

// Save to file in the same directory
$data = array(
    'timestamp' => date('Y-m-d H:i:s'),
    'name' => $name,
    'email' => $email,
    'guests' => get_guest_count($guests),
    'message' => $message,
    'ip' => $_SERVER['REMOTE_ADDR']
);

// Append to JSON file
$file = 'rsvp_submissions.json';
$submissions = file_exists($file) ? json_decode(file_get_contents($file), true) : array();
$submissions[] = $data;

if (file_put_contents($file, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo "<div class='alert alert-success'>Grazie! La tua RSVP è stata ricevuta con successo. Ti contatteremo presto per confermare.</div>";
} else {
    echo "<div class='alert alert-danger'>Si è verificato un errore durante l'invio della tua RSVP. Per favore riprova più tardi.</div>";
}

// Helper functions
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
