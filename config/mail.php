<?php
// config/mail.php – PHPMailer configuration for better deliverability

// Load PHPMailer using absolute path
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    die('Composer autoload.php not found. Please run: composer install');
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

function sendOTPEmail($toEmail, $otpCode, $purpose = 'signup') {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';         // your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'cataleyaessence23@gmail.com';   // UPDATE: your business email
        $mail->Password   = 'otqf wlwy xxqu iten';      // UPDATE: your app password (not regular password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Debug output OFF – SMTP::DEBUG_SERVER was echoing the raw SMTP conversation
        // (including base64 auth data) straight into the response body. On
        // forgot_password.php that text lands in front of the JSON, so
        // JSON.parse() on the frontend fails and the flow looks like the OTP
        // never sent even when PHPMailer actually delivered it. On the page-based
        // flow (forgot-password.php) it also triggers "headers already sent",
        // breaking the header("Location: ...") redirect. If you need to debug
        // SMTP again, log it instead of echoing it:
        // $mail->Debugoutput = function ($str) { error_log("PHPMailer: $str"); };
        // $mail->SMTPDebug   = SMTP::DEBUG_SERVER;
        $mail->SMTPDebug = SMTP::DEBUG_OFF;

        // Important for deliverability - disabled for now to fix Gmail issues
        // $mail->DKIM_domain = 'gmail.com';
        // $mail->DKIM_private = '';
        // $mail->DKIM_selector = '';
        // $mail->DKIM_passphrase = '';
        // $mail->DKIM_identity = $mail->Username;

        // Recipients
        $mail->setFrom('cataleyaessence23@gmail.com', 'Cataleya Essence of Beauty');
        $mail->addAddress($toEmail);
        $mail->addReplyTo('cataleyaessence23@gmail.com', 'Cataleya Essence Support');
        
        // Return-Path for bounce handling
        $mail->Sender = 'cataleyaessence23@gmail.com';

        // Content
        $mail->isHTML(true);
        
        // Professional subject line
        $subject = ($purpose === 'signup') 
            ? 'Verify Your Email - Cataleya Essence' 
            : (($purpose === 'email_change')
                ? 'Email Change Verification - Cataleya Essence'
                : 'Password Reset Code - Cataleya Essence');
        $mail->Subject = $subject;

        // Professional HTML email template
        $purposeText = ($purpose === 'signup') ? 'email verification' : (($purpose === 'email_change') ? 'email change verification' : 'password reset');
        
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Verification Code</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: #E91E8C; margin: 0;'>Cataleya Essence</h1>
                    <p style='color: #666; margin: 5px 0 0 0;'>of Beauty</p>
                </div>
                
                <div style='background-color: #FFF5F8; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;'>
                    <p style='margin: 0 0 10px 0; color: #333; font-size: 16px;'>Your $purposeText code is:</p>
                    <h2 style='color: #E91E8C; font-size: 36px; margin: 10px 0; letter-spacing: 5px;'>$otpCode</h2>
                </div>
                
                <p style='color: #666; line-height: 1.6;'>
                    This code will expire in <strong>5 minutes</strong> for your security.
                </p>
                
                <p style='color: #666; line-height: 1.6;'>
                    If you didn't request this code, please ignore this email or contact our support team.
                </p>
                
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center;'>
                    <p style='color: #999; font-size: 12px; margin: 5px 0;'>
                        © 2024 Cataleya Essence of Beauty. All rights reserved.
                    </p>
                    <p style='color: #999; font-size: 12px; margin: 5px 0;'>
                        This is an automated email. Please do not reply.
                    </p>
                </div>
            </div>
        </body>
        </html>";

        // Plain text alternative for email clients that don't support HTML
        $mail->AltBody = "Cataleya Essence of Beauty\n\n" .
                        "Your $purposeText code is: $otpCode\n\n" .
                        "This code expires in 5 minutes.\n\n" .
                        "If you didn't request this code, please ignore this email.\n\n" .
                        "© 2024 Cataleya Essence of Beauty";

        // Additional headers for deliverability
        $mail->addCustomHeader('X-Priority', '3');
        $mail->addCustomHeader('X-MSMail-Priority', 'Normal');
        $mail->addCustomHeader('X-Mailer', 'PHPMailer ' . PHPMailer::VERSION);
        $mail->addCustomHeader('List-Unsubscribe', '<mailto:cataleyaessence23@gmail.com?subject=unsubscribe>');

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail error: " . $mail->ErrorInfo);
        return false;
    }
}

function sendBookingConfirmationEmail($toEmail, $bookingData) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'cataleyaessence23@gmail.com';
        $mail->Password   = 'otqf wlwy xxqu iten';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Important for deliverability - disabled for now to fix Gmail issues
        // $mail->DKIM_domain = 'gmail.com';
        // $mail->DKIM_private = '';
        // $mail->DKIM_selector = '';
        // $mail->DKIM_passphrase = '';
        // $mail->DKIM_identity = $mail->Username;

        // Recipients
        $mail->setFrom('cataleyaessence23@gmail.com', 'Cataleya Essence of Beauty');
        $mail->addAddress($toEmail);
        $mail->addReplyTo('cataleyaessence23@gmail.com', 'Cataleya Essence Support');
        $mail->Sender = 'cataleyaessence23@gmail.com';

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Booking Confirmed - Cataleya Essence of Beauty';

        $bookingRef = 'CAT-' . str_pad($bookingData['booking_id'], 6, '0', STR_PAD_LEFT);
        $serviceName = $bookingData['service_name'] ?? 'Service';
        $bookingDate = $bookingData['booking_date'] ?? '';
        $bookingTime = $bookingData['booking_time'] ?? '';
        $therapistName = $bookingData['therapist_name'] ?? 'To be assigned';
        $totalAmount = number_format($bookingData['total_amount'] ?? 0, 2);

        // Format date for display
        $displayDate = date('F j, Y', strtotime($bookingDate));
        // Format time for display (convert 24h to 12h)
        $displayTime = date('g:i A', strtotime($bookingTime));

        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Booking Confirmed</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: #E91E8C; margin: 0;'>Cataleya Essence</h1>
                    <p style='color: #666; margin: 5px 0 0 0;'>of Beauty</p>
                </div>
                
                <div style='background-color: #E8F5E9; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; border-left: 4px solid #4CAF50;'>
                    <h2 style='color: #2E7D32; margin: 0 0 10px 0; font-size: 24px;'>✓ Booking Confirmed!</h2>
                    <p style='margin: 0; color: #333; font-size: 16px;'>Your appointment has been successfully booked.</p>
                </div>
                
                <div style='background-color: #FFF5F8; padding: 25px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='color: #E91E8C; margin: 0 0 15px 0; font-size: 18px; border-bottom: 2px solid #E91E8C; padding-bottom: 10px;'>Booking Details</h3>
                    
                    <div style='margin-bottom: 15px;'>
                        <p style='margin: 0 0 5px 0; color: #666; font-size: 14px;'><strong>Booking Reference:</strong></p>
                        <p style='margin: 0; color: #333; font-size: 16px; font-weight: bold;'>$bookingRef</p>
                    </div>
                    
                    <div style='margin-bottom: 15px;'>
                        <p style='margin: 0 0 5px 0; color: #666; font-size: 14px;'><strong>Service:</strong></p>
                        <p style='margin: 0; color: #333; font-size: 16px;'>$serviceName</p>
                    </div>
                    
                    <div style='margin-bottom: 15px;'>
                        <p style='margin: 0 0 5px 0; color: #666; font-size: 14px;'><strong>Date & Time:</strong></p>
                        <p style='margin: 0; color: #333; font-size: 16px;'>$displayDate at $displayTime</p>
                    </div>
                    
                    <div style='margin-bottom: 15px;'>
                        <p style='margin: 0 0 5px 0; color: #666; font-size: 14px;'><strong>Therapist:</strong></p>
                        <p style='margin: 0; color: #333; font-size: 16px;'>$therapistName</p>
                    </div>
                    
                    <div style='margin-bottom: 15px;'>
                        <p style='margin: 0 0 5px 0; color: #666; font-size: 14px;'><strong>Total Amount:</strong></p>
                        <p style='margin: 0; color: #E91E8C; font-size: 18px; font-weight: bold;'>₱$totalAmount</p>
                    </div>
                </div>
                
                <div style='background-color: #FFF9C4; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #FBC02D;'>
                    <p style='margin: 0; color: #333; font-size: 14px;'>
                        <strong>Important:</strong> Please arrive 10 minutes before your appointment time.
                    </p>
                </div>
                
                <p style='color: #666; line-height: 1.6;'>
                    If you need to reschedule or cancel your appointment, please contact us at least 24 hours in advance.
                </p>
                
                <p style='color: #666; line-height: 1.6;'>
                    <strong>Location:</strong><br>
                    Cataleya Essence of Beauty and Wellness Center<br>
                    Building J Maigapo St. San Vicente, Gapan City, Nueva Ecija
                </p>
                
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center;'>
                    <p style='color: #999; font-size: 12px; margin: 5px 0;'>
                        © 2024 Cataleya Essence of Beauty. All rights reserved.
                    </p>
                    <p style='color: #999; font-size: 12px; margin: 5px 0;'>
                        This is an automated email. Please do not reply.
                    </p>
                </div>
            </div>
        </body>
        </html>";

        // Plain text alternative
        $mail->AltBody = "Cataleya Essence of Beauty\n\n" .
                        "BOOKING CONFIRMED\n\n" .
                        "Booking Reference: $bookingRef\n" .
                        "Service: $serviceName\n" .
                        "Date & Time: $displayDate at $displayTime\n" .
                        "Therapist: $therapistName\n" .
                        "Total Amount: ₱$totalAmount\n\n" .
                        "Please arrive 10 minutes before your appointment.\n\n" .
                        "Location:\n" .
                        "Cataleya Essence of Beauty and Wellness Center\n" .
                        "Building J Maigapo St. San Vicente, Gapan City, Nueva Ecija\n\n" .
                        "If you need to reschedule or cancel, please contact us at least 24 hours in advance.\n\n" .
                        "© 2024 Cataleya Essence of Beauty";

        // Additional headers for deliverability
        $mail->addCustomHeader('X-Priority', '3');
        $mail->addCustomHeader('X-MSMail-Priority', 'Normal');
        $mail->addCustomHeader('X-Mailer', 'PHPMailer ' . PHPMailer::VERSION);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Booking confirmation email error: " . $mail->ErrorInfo);
        return false;
    }
}
?>