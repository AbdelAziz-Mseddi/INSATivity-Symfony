<?php

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST["username"];
    $password = $_POST["password"];

    $errors = [];

    // Username validation
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters.";
    }

    // Password validation
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    // Show result
    if (count($errors) > 0) {
        echo "<h3>Errors:</h3>";
        foreach ($errors as $error) {
            echo "<p style='color:red;'>$error</p>";
        }
        echo "<a href='register.html'>Go Back</a>";
    } else {
        echo "<h3 style='color:green;'>Registration data is valid!</h3>";
        echo "<p>Username: $username</p>";
        echo "<p>Email: $email</p>";
    }
}

?>