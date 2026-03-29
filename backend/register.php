<?php

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullName = $_POST["fullName"];
    $username = $_POST["username"];
    $email = $_POST["email"] . "@insat.ucar.tn";
    $major = $_POST["major"];
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirmPassword"];
    $acceptTerms = isset($_POST["acceptTerms"]) ? $_POST["acceptTerms"] : "";

    $errors = [];

    // Full Name validation
    if (empty($fullName)) {
        $errors[] = "Full Name is required.";
    }

    // Username validation
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters.";
    }

    // Email validation
    if (empty($email)) {
        $errors[] = "Email is required.";
    }

    // Major validation
    if (empty($major)) {
        $errors[] = "Major is required.";
    }

    // Password validation
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    // Password confirmation validation
    if (empty($confirmPassword)) {
        $errors[] = "Password confirmation is required.";
    } elseif ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    // Terms acceptance validation
    if (empty($acceptTerms)) {
        $errors[] = "You must accept the terms & data privacy policy.";
    }

    // Show result
    if (count($errors) > 0) {
        echo "<h3>Errors:</h3>";
        foreach ($errors as $error) {
            echo "<p style='color:red;'>$error</p>";
        }
        echo "<a href='../pages/register.html'>Go Back</a>";
    } else {
        echo "<h3 style='color:green;'>Registration successful!</h3>";
        echo "<p>Full Name: $fullName</p>";
        echo "<p>Username: $username</p>";
        echo "<p>Email: $email</p>";
        echo "<p>Major: $major</p>";
    }
}

?>
