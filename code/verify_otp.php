<?php
session_start(); // Move session_start to the top

// Database connection
$conn = new mysqli("localhost", "root", "", "user_registration");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$message_type = ''; // 'success' or 'error'

if (isset($_POST['submit_otp'])) {
    $otp = $_POST['otp'];
    $email = $_POST['email'];

    // Check OTP validity
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND otp = ?");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // OTP is valid, mark email as verified
        $user = $result->fetch_assoc(); // Fetch the user details
        $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE email = ?");
        $stmt->bind_param("s", $email);

        if ($stmt->execute()) {
            $_SESSION['email'] = $email; // Store email in session for dashboard routing
            $_SESSION['user_type'] = $user['user_type']; // Store role to check for dashboard

            $message = "Email successfully verified!";
            $message_type = 'success';

            // Redirect to appropriate dashboard based on role
            if ($user['user_type'] == 'user') {
                header("Location: user_dashboard.php");
                exit(); // Make sure to exit after redirect
            } elseif ($user['user_type'] == 'mechanic') {
                header("Location: mech_dashboard.php");
                exit(); // Make sure to exit after redirect
            }
        } else {
            $message = "Error verifying email.";
            $message_type = 'error';
        }
    } else {
        // OTP is invalid - Instead of deleting, show an error message
        $message = "Invalid OTP. Please try again or contact support.";
        $message_type = 'error';
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        h2 { margin-bottom: 20px; }
        input[type="email"], input[type="text"] {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
            box-sizing: border-box;
        }
        button {
            padding: 10px;
            font-size: 16px;
            background-color: #0066cc;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover { background-color: #005bb5; }
        .message {
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .message.success { background-color: #d4edda; color: #155724; }
        .message.error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

    <div class="container">
        <h2>Verify OTP</h2>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <form action="verify_otp.php" method="POST">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required>

            <label for="otp">OTP:</label>
            <input type="text" name="otp" id="otp" required>
            <br>
            <button type="submit" name="submit_otp">Verify OTP</button>
        </form>
    </div>

</body>
</html>
