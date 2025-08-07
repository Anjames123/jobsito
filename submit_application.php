<?php
$conn = new mysqli("localhost", "root", "", "jobsito_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$job_id = intval($_POST['job_id']);
$name = $_POST['applicant_name'];
$email = $_POST['applicant_email'];
$cover = $_POST['cover_letter'];

// Insert application into database
$sql = "INSERT INTO applications (job_id, applicant_name, applicant_email, cover_letter)
        VALUES ('$job_id', '$name', '$email', '$cover')";

if ($conn->query($sql) === TRUE) {
    echo "🎉 Application submitted successfully!";
} else {
    echo "❌ Error: " . $conn->error;
}

$conn->close();
?>
