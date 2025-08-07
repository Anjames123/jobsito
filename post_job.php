<!DOCTYPE html>
<html>
<head>
    <title>Post a Job</title>
</head>
<body>
    <h2>Post a New Job</h2>
    <form method="POST" action="submit_job.php">
        <label>Job Title:</label><br>
        <input type="text" name="title" required><br><br>

        <label>Company Name:</label><br>
        <input type="text" name="company" required><br><br>

        <label>Description:</label><br>
        <textarea name="description" required></textarea><br><br>

        <label>Location:</label><br>
        <input type="text" name="location" required><br><br>

        <button type="submit">Post Job</button>
    </form>
</body>
</html>
<?php include('header.php'); ?>
</body>
</html>
