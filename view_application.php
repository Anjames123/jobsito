<?php
// 1. Connect to the database
$conn = new mysqli("localhost", "root", "", "jobsito_db");

// 2. Check the connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// 3. Get all applications from the database
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$sql = "SELECT * FROM applications WHERE job_id = $job_id ORDER BY applied_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Applications</title>
</head>
<body>
    <h1>Job Applications</h1>

    <?php
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Job ID</th><th>Name</th><th>Email</th><th>Resume</th><th>Date Applied</th></tr>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['job_id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['email'] . "</td>";
            echo "<td>" . $row['resume'] . "</td>";
            echo "<td>" . $row['applied_at'] . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "<p>No applications found.</p>";
    }

    $conn->close();
    ?>
</body>
</html>
<?php include('header.php'); ?>
</body>
</html>
