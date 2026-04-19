<?php
/**
 * SMTP for PHPMailer (reminders, contact form, etc.).
 * Reads from environment variables (Docker/Railway)
 */
return [
    'host'            => getenv('MAIL_HOST')          ?: 'smtp.gmail.com',
    'port'            => getenv('MAIL_PORT')          ?: 587,
    'secure'          => getenv('MAIL_SECURE')        ?: 'tls', // tls or ssl
    'username'        => getenv('MAIL_USERNAME')      ?: 'tooltraceofficial@gmail.com',
    'password'        => getenv('MAIL_PASSWORD')      ?: '',
    'from_email'      => getenv('MAIL_FROM_EMAIL')    ?: 'tooltraceofficial@gmail.com',
    'from_name'       => getenv('MAIL_FROM_NAME')     ?: 'ToolTrace',
    // Must be reachable from the internet for Gmail users to display the logo
    'public_base_url' => getenv('PUBLIC_BASE_URL')    ?: 'http://localhost:8080',
    'contact_inbox'   => getenv('MAIL_CONTACT_INBOX') ?: 'tooltraceofficial@gmail.com',
];
?>