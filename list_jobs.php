<?php
// Connect to MySQL
$conn = new mysqli("localhost", "root", "", "jobsito_db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch jobs from database
$sql = "SELECT * FROM jobs ORDER BY posted_date DESC";
$result = $conn->query($sql);

echo "<h1>Available Jobs</h1>";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<h3>" . $row["title"] . " at " . $row["company"] . "</h3>";
        echo "<p><strong>Location:</strong> " . $row["location"] . "</p>";
        echo "<p>" . $row["description"] . "</p>";
        echo "<hr>";
    }
} else {
    echo "No job postings found.";
}

$conn->close();
?>
