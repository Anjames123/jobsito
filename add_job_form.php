<!DOCTYPE html>
<html>
<head>
    <title>Add New Job</title>
</head>
<body>
    <h1>Add a Job</h1>
    <form action="add_job.php" method="POST">
        <label>Job Title:</label><br>
        <input type="text" name="title" required><br><br>

        <label>Company:</label><br>
        <input type="text" name="company" required><br><br>

        <label>Description:</label><br>
        <textarea name="description" required></textarea><br><br>

        <label>Location:</label><br>
        <input type="text" name="location" required><br><br>

        <button type="submit">Submit Job</button>
    </form>
</body>
</html>
