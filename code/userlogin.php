<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - MechMeetCar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- For icons -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .form-container {
            width: 40%;
            margin: 50px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .form-container h2 {
            text-align: center;
            color: #333;
        }
        label {
            font-weight: bold;
            color: #333;
        }
        input[type="text"], input[type="email"], input[type="password"], input[type="date"], input[type="tel"], select, textarea {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        input[type="file"] {
            margin-top: 10px;
        }
        button {
            background-color: #007bff;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background-color: #0056b3;
        }
        .user-type-fields {
            display: none;
        }
        .user-type-fields.active {
            display: block;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Sign Up for MechMeetCar</h2>
    <form action="signup_process.php" method="POST" enctype="multipart/form-data">

        <!-- Personal Information -->
        <label for="full_name">Full Name:</label>
        <input type="text" name="full_name" id="full_name" required>

        <label for="email">Email Address:</label>
        <input type="email" name="email" id="email" required>

        <label for="phone">Phone Number:</label>
        <input type="tel" name="phone" id="phone" required>

        <label for="dob">Date of Birth:</label>
        <input type="date" name="dob" id="dob" required>

        <label for="location">Location:</label>
        <input type="text" name="location" id="location" required>

        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>

        <label for="user_type">User Type:</label>
        <select name="user_type" id="user_type" required onchange="toggleBusinessFields()">
            <option value="user">Client</option>
            <option value="mechanic">Mechanic</option>
        </select>

        <!-- Mechanic Fields (Only shown if 'mechanic' is selected) -->
        <div id="mechanic-fields" class="user-type-fields">
            <label for="specialization">Specialization:</label>
            <textarea name="specialization" id="specialization"></textarea>

            <label for="experience">Experience (years):</label>
            <input type="number" name="experience" id="experience">

            <label for="hourly_rate">Hourly Rate ($):</label>
            <input type="number" step="0.01" name="hourly_rate" id="hourly_rate">

            <label for="availability">Availability:</label>
            <textarea name="availability" id="availability"></textarea>

            <label for="preferred_service">Preferred Service:</label>
            <textarea name="preferred_service" id="preferred_service"></textarea>
        </div>

        <button type="submit" name="submit">Sign Up</button>
    </form>
</div>

<script>
    function toggleBusinessFields() {
        const userType = document.getElementById('user_type').value;
        const mechanicFields = document.getElementById('mechanic-fields');
        if (userType === 'mechanic') {
            mechanicFields.classList.add('active');
        } else {
            mechanicFields.classList.remove('active');
        }
    }
</script>

</body>
</html>
