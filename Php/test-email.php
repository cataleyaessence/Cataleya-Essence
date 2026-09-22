<?php
// Test email sending functionality
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';

$testEmail = $_GET['email'] ?? 'cataleyaessence23@gmail.com';
$testOTP = '123456';

echo "<h2>Testing Email Configuration</h2>";
echo "<p>Sending test email to: <strong>$testEmail</strong></p>";
echo "<p>Test OTP: <strong>$testOTP</strong></p>";
echo "<hr>";

$result = sendOTPEmail($testEmail, $testOTP, 'reset');

if ($result) {
    echo "<h3 style='color: green;'>✓ Email sent successfully!</h3>";
    echo "<p>Please check your inbox (and spam folder) for the test email.</p>";
} else {
    echo "<h3 style='color: red;'>✗ Email failed to send</h3>";
    echo "<p>Check the error logs for detailed information.</p>";
    echo "<p>Common issues:</p>";
    echo "<ul>";
    echo "<li>App password might be incorrect or expired</li>";
    echo "<li>2FA might not be enabled on the Gmail account</li>";
    echo "<li>Gmail account might have security restrictions</li>";
    echo "<li>SMTP settings might be incorrect</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='test-email.php?email=" . urlencode($testEmail) . "'>Test again</a></p>";
echo "<p><a href='test-email.php?email=" . urlencode($_GET['email'] ?? '') . "'>Test with different email</a></p>";
?>
