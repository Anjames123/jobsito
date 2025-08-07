<?php
// Connect to MySQL
$conn = new mysqli("localhost", "root", "", "jobsito_db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all jobs
$sql = "SELECT * FROM jobs ORDER BY posted_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Job Listings</title>
</head>
<body>
    <h1>Available Jobs</h1>

    <?php if ($result->num_rows > 0): ?>
        <ul>
            <?php while($row = $result->fetch_assoc()): ?>
                <li>
                    <strong><?php echo $row['title']; ?></strong> at 
                    <?php echo $row['company']; ?> - 
                    <?php echo $row['location']; ?><br>
                    <?php echo $row['description']; ?><br>
                    <small>Posted on: <?php echo $row['posted_date']; ?></small>
                    echo "<a href='apply.php?job_id=" . $row["id"] . "'>Apply for this Job</a>";
                    echo " | <a href='view_applications.php?job_id=" . $row['id'] . "'>View Applicants</a>";


                    <hr>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No jobs found.</p>
    <?php endif; ?>

</body>
</html>

<?php $conn->close(); ?>
<?php include('header.php'); ?>
</body>
</html>

