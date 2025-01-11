<?php
session_start();

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    exit('Invalid request');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $name = filter_var(trim($_POST["name"]), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $message = filter_var(trim($_POST["message"]), FILTER_SANITIZE_STRING);
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        exit('Invalid email address');
    }
    
    // Rate limiting
    if (isset($_SESSION['last_submission_time']) && 
        time() - $_SESSION['last_submission_time'] < 60) {
        http_response_code(429);
        exit('Please wait before submitting another message');
    }
    
    try {
        // Use a secure mail sending library like PHPMailer
        // This is just a basic example
        $to = base64_decode('bXV0YW50cmF0Z2FtZXNAZ21haWwuY29t');
        $subject = "New Contact Form Submission";
        $body = "Name: " . htmlspecialchars($name) . "\n";
        $body .= "Email: " . htmlspecialchars($email) . "\n";
        $body .= "Message: " . htmlspecialchars($message);
        
        $headers = "From: noreply@yourdomain.com\r\n";
        $headers .= "Reply-To: " . $email . "\r\n";
        
        if (mail($to, $subject, $body, $headers)) {
            $_SESSION['last_submission_time'] = time();
            http_response_code(200);
            echo "Message sent successfully";
        } else {
            throw new Exception("Mail sending failed");
        }
    } catch (Exception $e) {
        http_response_code(500);
        error_log($e->getMessage());
        echo "An error occurred while sending the message";
    }
}
?>