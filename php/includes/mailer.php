<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require_once 'vendor/autoload.php';
require_once 'config.php'; // Configuration file for SMTP credentials

/**
 * Sends an OTP email to the user
 *
 * @param string $recipientEmail
 * @param string $otp
 * @return bool
 */
function sendOTPEmail(string $recipientEmail, string $otp): bool
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_EMAIL;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom(SMTP_EMAIL, SMTP_NAME);
        $mail->addAddress($recipientEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your One-Time Password (OTP)';
        $mail->Body = "
            <p style='font-size: 16px;'>Hello,</p>
            <p style='font-size: 16px;'>Please use the following code to complete your login:</p>
        
            <div style='
                margin: 20px auto;
                padding: 15px 20px;
                background-color: #f4f4f4;
                border: 1px dashed #ccc;
                width: fit-content;
                font-size: 28px;
                font-weight: bold;
                letter-spacing: 6px;
                text-align: center;
                border-radius: 8px;
            '>
                {$otp}
            </div>
        
            <p style='font-size: 14px; color: #555;'>This code will expire in 5 minutes.</p>
            <p style='font-size: 14px; color: #555;'>If you didn't request this, please ignore this email.</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer error: " . $mail->ErrorInfo);
        return false;
    }
}
