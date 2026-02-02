<!-- process_login.php -->
<?php
session_start();
include('db_connection.php');  // Include your database connection file

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];

    // Get user data from the database
    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($user_id, $name, $hashed_password);
    $stmt->fetch();

    // Verify the password
    if ($stmt->num_rows == 1 && password_verify($password, $hashed_password)) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;

        // Redirect to the dashboard after successful login
        header("Location: dashboard.php");
        exit();
    } else {
        echo "<p>Invalid credentials. Please try again.</p>";
    }

    $stmt->close();
    $conn->close();
}
?>
