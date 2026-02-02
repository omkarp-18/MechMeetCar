if (isset($_GET['cancel_booking_id'])) {
    $cancel_booking_id = $_GET['cancel_booking_id'];

    // First, fetch the booking details to send an email
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
