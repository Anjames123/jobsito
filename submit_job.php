<?php
// Connect to MySQL
$conn = new mysqli("localhost", "root", "", "jobsito_db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get data from form
$title = $_POST['title'];
$company = $_POST['company'];
$description = $_POST['description'];
$location = $_POST['location'];
$posted_date = date("Y-m-d H:i:s");

// Insert into database
$sql = "INSERT INTO jobs (title, company, description, location, posted_date)
        VALUES ('$title', '$company', '$description', '$location', '$posted_date')";

if ($conn->query($sql) === TRUE) {
    echo "🎉 Job posted successfully!";
} else {
    echo "❌ Error: " . $conn->error;
}

$conn->close();
?>
