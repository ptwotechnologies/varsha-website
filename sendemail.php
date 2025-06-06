<?php
// Start session for CSRF protection
session_start();

// Configuration
define("RECIPIENT_NAME", "Sumanth ");
define("RECIPIENT_EMAIL", "sumanth1659@gmail.com");
define("MAX_MESSAGE_LENGTH", 5000);
define("MAX_NAME_LENGTH", 100);
define("MAX_SUBJECT_LENGTH", 200);

// Function to sanitize input
function sanitizeInput($input, $maxLength = null) {
    $input = trim($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    if ($maxLength && strlen($input) > $maxLength) {
        $input = substr($input, 0, $maxLength);
    }
    return $input;
}

// Function to validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Function to remove line breaks (prevent header injection)
function removeLineBreaks($string) {
    return str_replace(array("\r", "\n", "\r\n"), '', $string);
}

// Initialize variables
$success = false;
$errors = array();

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Security token mismatch. Please try again.";
    }
    
    // Sanitize and validate inputs
    $name = isset($_POST['name']) ? sanitizeInput($_POST['name'], MAX_NAME_LENGTH) : "";
    $senderEmail = isset($_POST['email']) ? sanitizeInput($_POST['email'], 255) : "";
    $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone'], 20) : "";
    $subject = isset($_POST['subject']) ? sanitizeInput($_POST['subject'], MAX_SUBJECT_LENGTH) : "";
    $message = isset($_POST['message']) ? sanitizeInput($_POST['message'], MAX_MESSAGE_LENGTH) : "";
    
    // Validation
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    
    if (empty($senderEmail)) {
        $errors[] = "Email is required.";
    } elseif (!isValidEmail($senderEmail)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required.";
    }
    
    if (strlen($message) < 10) {
        $errors[] = "Message must be at least 10 characters long.";
    }
    
    // Phone validation (if provided)
    if (!empty($phone) && !preg_match('/^[\d\s\-\+\(\)]+$/', $phone)) {
        $errors[] = "Please enter a valid phone number.";
    }
    
    // If no errors, send email
    if (empty($errors)) {
        
        // Prepare email subject
        $mail_subject = 'Contact Form: ' . (!empty($subject) ? $subject : 'New Message from ' . $name);
        $mail_subject = removeLineBreaks($mail_subject);
        
        // Prepare email body
        $body = "New contact form submission:\n\n";
        $body .= "Name: " . $name . "\n";
        $body .= "Email: " . $senderEmail . "\n";
        
        if (!empty($phone)) {
            $body .= "Phone: " . $phone . "\n";
        }
        
        if (!empty($subject)) {
            $body .= "Subject: " . $subject . "\n";
        }
        
        $body .= "\nMessage:\n" . $message;
        $body .= "\n\n---\n";
        $body .= "Sent from: " . $_SERVER['HTTP_HOST'] . "\n";
        $body .= "IP Address: " . $_SERVER['REMOTE_ADDR'] . "\n";
        $body .= "Time: " . date('Y-m-d H:i:s');
        
        // Prepare headers (secure)
        $headers = array();
        $headers[] = 'From: ' . removeLineBreaks($name) . ' <' . $senderEmail . '>';
        $headers[] = 'Reply-To: ' . $senderEmail;
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        
        $recipient = RECIPIENT_EMAIL;
        
        // Send email
        $success = mail($recipient, $mail_subject, $body, implode("\r\n", $headers));
        
        if ($success) {
            // Regenerate CSRF token for security
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            echo "<div class='inner success'><p class='success'>Thanks for contacting us! We will get back to you soon.</p></div>";
        } else {
            echo "<div class='inner error'><p class='error'>Sorry, there was an error sending your message. Please try again later.</p></div>";
        }
    } else {
        // Display errors
        echo "<div class='inner error'>";
        echo "<p class='error'>Please fix the following errors:</p>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>" . $error . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>