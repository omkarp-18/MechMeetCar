<?php
session_start();

// Check if the mechanic is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connection.php'; // Include the database connection

// Fetch mechanic data from the `users` table (mechanic profile)
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);

// Check if the query preparation is successful
if ($stmt === false) {
    die('Error in preparing the statement: ' . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch mechanic data
$mechanic = $result->fetch_assoc();

// Fetch pending job requests from the `booking` table (where status is 'pending')
$pending_jobs_sql = "SELECT b.id, u.full_name AS user_name, b.service_type, b.booking_date, b.status, b.location
                     FROM booking b
                     JOIN users u ON b.user_id = u.id
                     WHERE b.mechanic_id = ? AND b.status = 'pending'";
$stmt_pending_jobs = $conn->prepare($pending_jobs_sql);

// Check if the query preparation is successful
if ($stmt_pending_jobs === false) {
    die('Error in preparing the statement: ' . $conn->error);
}

$stmt_pending_jobs->bind_param("i", $user_id);
$stmt_pending_jobs->execute();
$pending_jobs_result = $stmt_pending_jobs->get_result();

// Fetch completed job history from the `booking` table (where status is 'completed')
$completed_jobs_sql = "SELECT b.id, u.full_name AS user_name, b.service_type, b.booking_date, b.status, b.location 
                       FROM booking b 
                       JOIN users u ON b.user_id = u.id 
                       WHERE b.mechanic_id = ? AND b.status = 'completed'";
$stmt_completed_jobs = $conn->prepare($completed_jobs_sql);

// Check if the query preparation is successful
if ($stmt_completed_jobs === false) {
    die('Error in preparing the statement: ' . $conn->error);
}

$stmt_completed_jobs->bind_param("i", $user_id);
$stmt_completed_jobs->execute();
$completed_jobs_result = $stmt_completed_jobs->get_result();

// Fetch job statistics for the chart (monthly count of completed jobs)
$job_stats_sql = "SELECT MONTH(b.booking_date) AS month, COUNT(*) AS completed_count
                  FROM booking b
                  WHERE b.mechanic_id = ? AND b.status = 'completed'
                  GROUP BY MONTH(b.booking_date)
                  ORDER BY MONTH(b.booking_date)";
$stmt_job_stats = $conn->prepare($job_stats_sql);
$stmt_job_stats->bind_param("i", $user_id);
$stmt_job_stats->execute();
$job_stats_result = $stmt_job_stats->get_result();

// Prepare data for the chart (month labels and completed job counts)
$months = [];
$completed_counts = [];

while ($row = $job_stats_result->fetch_assoc()) {
    $months[] = date('F', mktime(0, 0, 0, $row['month'], 1)); // Convert month number to month name
    $completed_counts[] = $row['completed_count'];
}

// Handle status change request
if (isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['new_status'];

    // Update the booking status
    $update_sql = "UPDATE booking SET status = ? WHERE id = ?";
    $stmt_update = $conn->prepare($update_sql);
    $stmt_update->bind_param("si", $new_status, $booking_id);
    $stmt_update->execute();

    // Fetch user email to send the notification
    $email_sql = "SELECT u.email FROM booking b JOIN users u ON b.user_id = u.id WHERE b.id = ?";
    $stmt_email = $conn->prepare($email_sql);
    $stmt_email->bind_param("i", $booking_id);
    $stmt_email->execute();
    $result_email = $stmt_email->get_result();
    $user_email = $result_email->fetch_assoc()['email'];

    // Send email notification to the client
    sendStatusUpdateEmail($user_email, $new_status, $booking_id);
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to send email notifications
function sendStatusUpdateEmail($clientEmail, $status, $bookingId) {
    require 'c:\xampp\htdocs\mail\vendor\autoload.php'; // Include the PHPMailer files

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'mechmeetcar@gmail.com';
        $mail->Password = 'vfbu uxur wgwm yhii';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('no-reply@example.com', 'Mechanic Booking');
        $mail->addAddress($clientEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Booking Status Update';
        $mail->Body    = "
            <h3>Your booking status has been updated!</h3>
            <p>Booking ID: $bookingId</p>
            <p>Status: $status</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        echo "Error sending email: {$mail->ErrorInfo}";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mechanic Dashboard - MechMeetCar</title>
    <link rel="stylesheet" href="styleh.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Chart.js Library -->
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>MechMeetCar</h2>
    <ul>
        <li><a href="mech_dashboard.php">Dashboard</a></li>
        
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <header>
        <h1>Welcome, <?php echo $mechanic['full_name']; ?></h1>
    </header>

    <!-- Profile Section -->
    <section class="profile">
        <h2>Your Profile</h2>
        <div class="profile-info">
            <p><strong>Name:</strong> <?php echo $mechanic['full_name']; ?></p>
            <p><strong>Email:</strong> <?php echo $mechanic['email']; ?></p>
            <p><strong>Specialization:</strong> <?php echo $mechanic['specialization']; ?></p>
            <p><strong>Experience:</strong> <?php echo $mechanic['experience']; ?> years</p>
            <p><strong>Hourly Rate:</strong> $<?php echo $mechanic['hourly_rate']; ?></p>
            <p><strong>Availability:</strong> <?php echo $mechanic['availability']; ?></p>
        </div>
    </section>

    <!-- Achievements Section (Graph) -->
    <section class="achievements">
        <h2>Your Achievements</h2>
        <div class="achievement-info">
            <canvas id="achievementChart"></canvas>
        </div>
    </section>

    <!-- Pending Jobs Section -->
    <section class="job-requests">
        <h2>Pending Job Requests</h2>
        <div class="requests-list">
            <?php if ($pending_jobs_result->num_rows > 0): ?>
                <?php while ($request = $pending_jobs_result->fetch_assoc()): ?>
                    <div class="request-card">
                        <h3>Booking #<?php echo $request['id']; ?></h3>
                        <p><strong>User:</strong> <?php echo $request['user_name']; ?></p>
                        <p><strong>Location:</strong> <?php echo $request['location']; ?></p>
                        <p><strong>Service Type:</strong> <?php echo $request['service_type']; ?></p>
                        <p><strong>Booking Date:</strong> <?php echo $request['booking_date']; ?></p>
                        
                        <!-- Dropdown to select the booking status -->
                        <form method="POST" action="mech_dashboard.php">
                            <input type="hidden" name="booking_id" value="<?php echo $request['id']; ?>">
                            <select name="new_status" required>
                                <option value="pending" <?php echo ($request['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo ($request['status'] == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="completed" <?php echo ($request['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="rejected" <?php echo ($request['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                            <button type="submit" name="update_status">Update Status</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No pending job requests.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Job History Section (Completed Jobs) -->
    <section class="job-history">
        <h2>Your Completed Job History</h2>
        <div class="history-list">
            <?php if ($completed_jobs_result->num_rows > 0): ?>
                <?php while ($job = $completed_jobs_result->fetch_assoc()): ?>
                    <div class="history-card">
                        <p><strong>User:</strong> <?php echo $job['user_name']; ?></p>
                        <p><strong>Service Type:</strong> <?php echo $job['service_type']; ?></p>
                        <p><strong>Booking Date:</strong> <?php echo $job['booking_date']; ?></p>
                        <p><strong>Status:</strong> <?php echo $job['status']; ?></p>
                        <p><strong>Location:</strong> <?php echo $job['location']; ?></p> <!-- Displaying location -->

                        <!-- Achievement Badge -->
                        <div class="achievement-badge">
                            <span class="badge">Completed</span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No completed jobs yet.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
    // Pass PHP data to JavaScript
    const months = <?php echo json_encode($months); ?>;
    const completedCounts = <?php echo json_encode($completed_counts); ?>;

    // Example data for the chart (replace with actual dynamic data)
    const jobStats = {
        labels: months, // Month names from PHP
        datasets: [{
            label: 'Completed Jobs',
            data: completedCounts, // Job count per month
            backgroundColor: 'rgba(52, 227, 192, 0.75)',
            borderColor: 'rgb(0, 0, 0)',
            borderWidth: 1
        }]
    };

    const config = {
        type: 'bar',
        data: jobStats,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    };

    const ctx = document.getElementById('achievementChart').getContext('2d');
    new Chart(ctx, config);
</script>

</body>
</html>

<?php
$conn->close();
?>
