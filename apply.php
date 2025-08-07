<?php
// Check if job_id is passed in the URL
if (!isset($_GET['job_id'])) {
    die("Job ID is missing.");
}

$job_id = intval($_GET['job_id']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Apply for Job</title>
</head>
<body>
    <h2>Apply for Job #<?php echo $job_id; ?></h2>
    <form action="submit_application.php" method="POST">
        <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
        <label>Your Name:</label><br>
        <input type="text" name="applicant_name" required><br><br>

        <label>Your Email:</label><br>
        <input type="email" name="applicant_email" required><br><br>

        <label>Cover Letter:</label><br>
        <textarea name="cover_letter" rows="5" required></textarea><br><br>

        <input type="submit" value="Submit Application">
    </form>
</body>
</html>
<?php include('header.php'); ?>
</body>
</html>
