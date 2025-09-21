<?php
// For the mail() function to work, you need to have a mail server configured in your php.ini file.

function send_notification($to, $subject, $message) {
    $headers = "From: no-reply@freelensing.com" . "\r\n" .
               "Reply-To: no-reply@freelensing.com" . "\r\n" .
               "X-Mailer: PHP/" . phpversion();

    // mail($to, $subject, $message, $headers);
    // For local development, we will log the email to a file instead of sending it.
    $log_message = "To: $to\nSubject: $subject\nMessage: $message\n---\n";
    file_put_contents("../email_log.txt", $log_message, FILE_APPEND);
}
?>
