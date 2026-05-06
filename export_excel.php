<?php
// Connect to your database (change to 'db_config.php' if that's what you normally use)
require 'connection.php';

// Set headers to tell the browser to download a CSV file instead of displaying a web page
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Attendance_Logs_' . date('Y-m-d') . '.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output the column headings for the Excel/CSV file
fputcsv($output, array('Date', 'Student Name', 'Time In', 'Time Out', 'Status'));

// Check if there are active search or date filters from the URL
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

$sql = "SELECT date, student_name, time_in, time_out, status FROM attendance_logs WHERE 1=1";

if (!empty($search)) {
    $safe_search = $conn->real_escape_string($search);
    $sql .= " AND (student_name LIKE '%$safe_search%' OR student_id LIKE '%$safe_search%')";
}
if (!empty($date_filter)) {
    $safe_date = $conn->real_escape_string($date_filter);
    $sql .= " AND date = '$safe_date'";
}

$sql .= " ORDER BY date DESC, time_in DESC";
$result = $conn->query($sql);

// Loop through the database results and write them to the CSV file
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Format the date and time exactly like your logs dashboard
        $date = date('M d, Y', strtotime($row['date']));
        $time_in = date('h:i A', strtotime($row['time_in']));
        $time_out = !empty($row['time_out']) ? date('h:i A', strtotime($row['time_out'])) : '--:--';
        $status = $row['status'];

        // Add the row to the CSV
        fputcsv($output, array($date, $row['student_name'], $time_in, $time_out, $status));
    }
}

fclose($output);
exit();
?>