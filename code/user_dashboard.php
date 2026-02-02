<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connection.php'; // Include the database connection

// Fetch user data from the users table
$user_id = $_SESSION['user_id'];

// Prepare the query for fetching user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    // If the prepare statement fails, output the error
    die('MySQL prepare statement failed: ' . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result === false) {
    die('Error executing query: ' . $stmt->error);
}

$user = $result->fetch_assoc();

// Fetch the user's booking history from the bookings table
$booking_history_sql = "SELECT b.id, b.service_type, b.booking_date, b.status, m.full_name AS mechanic_name 
                        FROM booking b 
                        JOIN users m ON b.mechanic_id = m.id 
                        WHERE b.user_id = ?";
$stmt_history = $conn->prepare($booking_history_sql);

if ($stmt_history === false) {
    // If the prepare statement fails, output the error
    die('MySQL prepare statement failed: ' . $conn->error);
}

$stmt_history->bind_param("i", $user_id);
$stmt_history->execute();
$booking_history_result = $stmt_history->get_result();

if ($booking_history_result === false) {
    die('Error executing query: ' . $stmt_history->error);
}

// Fetch available mechanics from the users table
$mechanics_sql = "SELECT id, full_name, specialization, experience, hourly_rate, availability 
                  FROM users WHERE user_type = 'mechanic'";

$mechanics_result = $conn->query($mechanics_sql);

if ($mechanics_result === false) {
    die('Error executing query: ' . $conn->error);
}

// Handle booking cancellation
if (isset($_GET['cancel_booking_id'])) {
    $cancel_booking_id = $_GET['cancel_booking_id'];

    // Fetch booking details to send an email
    $booking_sql = "SELECT b.id, b.user_id, b.service_type, b.booking_date, u.email AS user_email, m.full_name AS mechanic_name 
                    FROM booking b
                    JOIN users u ON b.user_id = u.id
                    JOIN users m ON b.mechanic_id = m.id
                    WHERE b.id = ?";
    $stmt_booking = $conn->prepare($booking_sql);

    if ($stmt_booking === false) {
        die('MySQL prepare statement failed: ' . $conn->error);
    }

    $stmt_booking->bind_param("i", $cancel_booking_id);
    $stmt_booking->execute();
    $result_booking = $stmt_booking->get_result();

    if ($result_booking->num_rows > 0) {
        $booking = $result_booking->fetch_assoc();
        $user_email = $booking['user_email'];
        $service_type = $booking['service_type'];
        $booking_date = $booking['booking_date'];
        $mechanic_name = $booking['mechanic_name'];

        // Send cancellation email to the user
        $subject = "Booking Cancellation Confirmation";
        $message = "
        <html>
        <head><title>Booking Cancelled</title></head>
        <body>
        <p>Dear user,</p>
        <p>Your booking for the service <strong>$service_type</strong> scheduled on <strong>$booking_date</strong> has been cancelled.</p>
        <p>Mechanic: $mechanic_name</p>
        <p>If you need further assistance, feel free to contact us.</p>
        <p>Best regards,</p>
        <p>The MechMeetCar Team</p>
        </body>
        </html>";

        // Set headers for HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: no-reply@mechmeetcar.com" . "\r\n";

        // Send email to the user
        if (mail($user_email, $subject, $message, $headers)) {
            // Successfully sent email, now cancel the booking
            $cancel_sql = "UPDATE booking SET status = 'cancelled' WHERE id = ? AND user_id = ?";
            $stmt_cancel = $conn->prepare($cancel_sql);

            if ($stmt_cancel === false) {
                die('MySQL prepare statement failed: ' . $conn->error);
            }

            $stmt_cancel->bind_param("ii", $cancel_booking_id, $user_id);
            $stmt_cancel->execute();

            if ($stmt_cancel->affected_rows > 0) {
                // Now, delete the booking from the database
                $delete_sql = "DELETE FROM booking WHERE id = ?";
                $stmt_delete = $conn->prepare($delete_sql);

                if ($stmt_delete === false) {
                    die('MySQL prepare statement failed: ' . $conn->error);
                }

                $stmt_delete->bind_param("i", $cancel_booking_id);
                $stmt_delete->execute();

                // Booking successfully cancelled and deleted
                header("Location: user_dashboard.php?message=Booking cancelled and deleted successfully");
            } else {
                // If updating the booking status failed
                header("Location: user_dashboard.php?message=Error cancelling booking");
            }
        } else {
            // Failed to send email
            header("Location: user_dashboard.php?message=Failed to send cancellation email");
        }
    } else {
        // Booking not found
        header("Location: user_dashboard.php?message=Booking not found");
    }
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - MechMeetCar</title>
    <link rel="stylesheet" href="styleh.css">
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>MechMeetCar</h2>
    <ul>
        <li><a href="user_dashboard.php">Dashboard</a></li>
        <li><a href="logout.php">Logout</a></li>
        <li><a href="booking.php">Book Mechanic</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <header>
        <h1>Welcome, <?php echo $user['full_name']; ?></h1>
    </header>

    <!-- Profile Section -->
    <section class="profile">
        <h2>Your Profile</h2>
        <div class="profile-info">
            <p><strong>Name:</strong> <?php echo $user['full_name']; ?></p>
            <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
            <p><strong>Location:</strong> <?php echo $user['location']; ?></p>
            <p><strong>Phone:</strong> <?php echo $user['phone']; ?></p>
            <p><strong>Date of Birth:</strong> <?php echo $user['dob']; ?></p>
        </div>
    </section>

    <!-- Available Mechanics Section -->
    <section class="mechanics">
        <h2>Available Mechanics</h2>
        <div class="mechanics-list">
            <?php while ($mechanic = $mechanics_result->fetch_assoc()): ?>
                <div class="mechanic-card">
                    <h3><?php echo $mechanic['full_name']; ?> - <?php echo $mechanic['specialization']; ?></h3>
                    <p>Experience: <?php echo $mechanic['experience']; ?> years</p>
                    <p>Hourly Rate: $<?php echo $mechanic['hourly_rate']; ?></p>
                    <p>Availability: <?php echo $mechanic['availability']; ?></p>
                    <button onclick="bookMechanic(<?php echo $mechanic['id']; ?>)">Book Now</button>
                </div>
            <?php endwhile; ?>
        </div>
    </section>

    <!-- Booking History Section -->
    <section class="booking-history">
        <h2>Your Booking History</h2>
        <div class="history-list">
            <?php if ($booking_history_result->num_rows > 0): ?>
                <?php while ($booking = $booking_history_result->fetch_assoc()): ?>
                    <div class="history-card">
                        <p><strong>Mechanic:</strong> <?php echo $booking['mechanic_name']; ?></p>
                        <p><strong>Service Type:</strong> <?php echo $booking['service_type']; ?></p>
                        <p><strong>Status:</strong> <?php echo $booking['status']; ?></p>
                        <p><strong>Booking Date:</strong> <?php echo $booking['booking_date']; ?></p>
                        <?php if ($booking['status'] !== 'cancelled'): ?>
                            <a href="user_dashboard.php?cancel_booking_id=<?php echo $booking['id']; ?>" class="cancel-booking">Cancel Booking</a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No completed bookings yet.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
    function bookMechanic(mechanicId) {
        // Redirect to booking page with mechanic ID
        window.location.href = "booking.php?mechanic_id=" + mechanicId;
    }
</script>

</body>
</html>

<?php
$conn->close();
?>
