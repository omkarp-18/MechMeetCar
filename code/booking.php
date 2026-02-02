<?php include('header.php'); ?>
<?php
// Start session
session_start();
var_dump($_SESSION['user_id']);
// Check if the user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    // Not logged in or not a user, redirect to login page
    header("Location: login.php");
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'user_registration';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}

// Fetch user's location from session
$userId = $_SESSION['user_id'];
$sql = "SELECT location FROM users WHERE id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$userLocation = $user['location'];

// Fetch available mechanics from the same location as the user
$sql = "SELECT id, full_name, location FROM users WHERE user_type = 'mechanic' AND location = :location";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':location', $userLocation);
$stmt->execute();
$mechanics = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'c:\xampp\htdocs\mail\vendor\autoload.php';  // Add this at the top of your PHP file

// Process the booking form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mechanicId = $_POST['mechanic_id'];
    $serviceType = htmlspecialchars(trim($_POST['service_type']));
    $bookingDate = htmlspecialchars(trim($_POST['booking_date']));

    // Insert the booking into the database
    $sql = "INSERT INTO booking (user_id, mechanic_id, service_type, booking_date, status)
            VALUES (:user_id, :mechanic_id, :service_type, :booking_date, 'pending')";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':mechanic_id', $mechanicId);
    $stmt->bindParam(':service_type', $serviceType);
    $stmt->bindParam(':booking_date', $bookingDate);

    if ($stmt->execute()) {
        // Fetch user email for confirmation
        $sql = "SELECT email FROM users WHERE id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Send confirmation email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Set your SMTP server here
            $mail->SMTPAuth = true;
            $mail->Username = 'mechmeetcar@gmail.com'; // SMTP username
            $mail->Password = 'abc example password'; // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('no-reply@example.com', 'Mechanic Booking');
            $mail->addAddress($user['email']); // Add user's email as recipient

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Booking Confirmation';
            $mail->Body    = "
                <h3>Your booking has been successfully received!</h3>
                <p>Thank you for booking a mechanic with us. Here are the details of your booking:</p>
                <ul>
                    <li><strong>Mechanic:</strong> {$mechanicId}</li>
                    <li><strong>Service Type:</strong> {$serviceType}</li>
                    <li><strong>Booking Date:</strong> {$bookingDate}</li>
                </ul>
                <p>Your booking is currently pending.</p>
            ";

            $mail->send();
            echo "Booking successful! Your booking is now pending. A confirmation email has been sent.";
            header('Location: user_dashboard.php');  // Redirect to dashboard after booking
        } catch (Exception $e) {
            echo "Booking successful! However, there was an error sending the confirmation email: {$mail->ErrorInfo}";
        }
    } else {
        echo "Error creating booking.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Mechanic</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        h1 {
            text-align: center;
            color: #4CAF50;
            margin-bottom: 30px;
        }

        .mechanic-card-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .mechanic-card {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 200px;
            text-align: center;
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .mechanic-card:hover {
            transform: scale(1.05);
        }

        .mechanic-card h3 {
            margin: 10px 0;
            color: #333;
        }

        .mechanic-card p {
            color: #777;
            font-size: 0.9em;
        }

        .mechanic-card button {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        .mechanic-card button:hover {
            background-color: #45a049;
        }

        form {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            box-sizing: border-box;
        }

        label, input, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            font-size: 1em;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            background-color: #4CAF50;
            color: white;
            padding: 12px;
            font-size: 1.2em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

<h1>Book a Mechanic</h1>

<div class="mechanic-card-container">
    <?php foreach ($mechanics as $mechanic): ?>
        <div class="mechanic-card" onclick="selectMechanic(<?php echo $mechanic['id']; ?>)">
            <h3><?php echo htmlspecialchars($mechanic['full_name']); ?></h3>
            <p>Location: <?php echo htmlspecialchars($mechanic['location']); ?></p>
            <button>Select Mechanic</button>
        </div>
    <?php endforeach; ?>
</div>

<form method="POST" action="booking.php">
    <input type="hidden" name="mechanic_id" id="selected_mechanic_id">
    <label for="service_type">Service Type:</label>
    <input type="text" name="service_type" id="service_type" required>
    
    <label for="booking_date">Booking Date:</label>
    <input type="datetime-local" name="booking_date" id="booking_date" required>
    
    <button type="submit">Book Now</button>
</form>

<script>
    function selectMechanic(mechanicId) {
        document.getElementById('selected_mechanic_id').value = mechanicId;
    }
</script>

</body>
</html>

<?php include('footer.php'); ?>
