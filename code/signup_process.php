<?php
session_start();

// Include the PHPMailer files
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'c:\xampp\htdocs\mail\vendor\autoload.php'; // If you are using Composer, or update the path if manually including PHPMailer

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_registration";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if (isset($_POST['submit'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $dob = $_POST['dob'];
    $location = $_POST['location'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user_type = $_POST['user_type'];

    // Default values for mechanic fields (if user type is mechanic)
    $specialization = isset($_POST['specialization']) ? $_POST['specialization'] : NULL;
    $experience = isset($_POST['experience']) ? $_POST['experience'] : NULL;
    $hourly_rate = isset($_POST['hourly_rate']) ? $_POST['hourly_rate'] : NULL;
    $availability = isset($_POST['availability']) ? $_POST['availability'] : NULL;
    $preferred_service = isset($_POST['preferred_service']) ? $_POST['preferred_service'] : NULL;
    
    // Handle file upload (portfolio)
    $portfolioPath = NULL; // You should implement file upload handling if required

    // Generate OTP
    $otp = rand(100000, 999999); // 6 digit OTP
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes')); // OTP valid for 15 minutes

    // Prepare SQL statement to insert user data into the database
    $stmt = $conn->prepare("INSERT INTO users 
                            (full_name, email, phone, password, dob, location, user_type, otp, otp_expiry, is_verified) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $is_verified = 0;  // Default user verification status (0 = unverified)

    // Bind parameters to the prepared statement using bind_param
    $stmt->bind_param("sssssssssi", $full_name, $email, $phone, $password, $dob, $location, $user_type, $otp, $otp_expiry, $is_verified);

    // Execute the query
    if ($stmt->execute()) {
        // Get the user ID
        $user_id = $stmt->insert_id;

        // If the user is a mechanic, insert the mechanic-specific data into the 'mechanics' table
        if ($user_type == 'mechanic') {
            $stmt_mechanic = $conn->prepare("INSERT INTO users 
                                             (id, specialization, experience, hourly_rate, availability, preferred_service) 
                                             VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_mechanic->bind_param("issdss", $user_id, $specialization, $experience, $hourly_rate, $availability, $preferred_service);
            $stmt_mechanic->execute();
            $stmt_mechanic->close();
        }

        // Send OTP email using PHPMailer
        $mail = new PHPMailer(true); // Passing `true` enables exceptions
        
        try {
            // Server settings
            $mail->isSMTP();  // Set mailer to use SMTP
            $mail->Host = 'smtp.gmail.com';  // Set the SMTP server to Gmail
            $mail->SMTPAuth = true;  // Enable SMTP authentication
            $mail->Username = 'mechmeetcar@gmail.com';  // Your Gmail address
            $mail->Password = 'vfbu uxur wgwm yhii';  // Your Gmail password or App-specific password if 2FA is enabled
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // Enable TLS encryption
            $mail->Port = 587;  // TCP port for TLS

            // Sender and recipient
            $mail->setFrom('your_email@gmail.com', 'MechMeetCar');  // Sender's email
            $mail->addAddress($email);  // Recipient's email

            // Content
            $mail->isHTML(true);  // Set email format to HTML
            $mail->Subject = 'MechMeetCar Email Verification OTP';
            $mail->Body    = "Your OTP for verifying your email is: <b>$otp</b>. It is valid for 15 minutes.";

            // Send email
            $mail->send();

            // Display success message
            echo '<div style="padding: 15px; background-color: #28a745; color: white; border-radius: 5px; text-align: center;">';
            echo "Registration successful! An OTP has been sent to your email for verification.";
            echo '<br><a href="verify_otp.php" style="color: #ffffff; font-weight: bold;">Click here to verify your email</a>';
            echo '</div>';
        } catch (Exception $e) {
            // Display error message
            echo '<div style="padding: 15px; background-color: #dc3545; color: white; border-radius: 5px; text-align: center;">';
            echo "Error sending OTP email: {$mail->ErrorInfo}";
            echo '</div>';
        }
    } else {
        // Display error message for database failure
        echo '<div style="padding: 15px; background-color: #dc3545; color: white; border-radius: 5px; text-align: center;">';
        echo "Error: " . $stmt->error;
        echo '</div>';
    }

    $stmt->close();
    $conn->close();
}
?>
