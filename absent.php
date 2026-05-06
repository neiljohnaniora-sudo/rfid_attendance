<?php
session_start();
require 'connection.php'; 
date_default_timezone_set('Asia/Manila'); 

$admin_role = isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : '';
$assigned_grade = isset($_SESSION['assigned_grade']) ? $_SESSION['assigned_grade'] : '';

$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$display_date = date('F d, Y', strtotime($filter_date));

// FILTER LOGIC
if ($admin_role === 'Teacher' && !empty($assigned_grade)) {
    $absentees_by_grade = [$assigned_grade => []];
    $sql = "SELECT rfid, name, grade FROM students 
            WHERE status = 'Active' AND grade = '$assigned_grade'
            AND rfid NOT IN (SELECT student_id FROM attendance_logs WHERE date = '$filter_date') 
            ORDER BY name ASC";
} else {
    $absentees_by_grade = ['Grade 1'=>[],'Grade 2'=>[],'Grade 3'=>[],'Grade 4'=>[],'Grade 5'=>[],'Grade 6'=>[]];
    $sql = "SELECT rfid, name, grade FROM students 
            WHERE status = 'Active' 
            AND rfid NOT IN (SELECT student_id FROM attendance_logs WHERE date = '$filter_date') 
            ORDER BY name ASC";
}

$result = $conn->query($sql);
$total_absent = 0;

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if(array_key_exists($row['grade'], $absentees_by_grade)) {
            $absentees_by_grade[$row['grade']][] = $row;
            $total_absent++;
        }
    }
}
?>  
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absentees List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f0f2f5; }
        
        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .top-header h1 { color: #1e293b; font-size: 24px; }
        
        .toolbar { display: flex; gap: 10px; align-items: center; background: #fff; padding: 15px 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.03); margin-bottom: 25px; }
        .toolbar input[type="date"] { padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; outline: none; font-size: 14px; }
        .btn-print { background-color: #10b981; color: white; border: none; padding: 10px 18px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 14px; text-decoration: none; }
        .badge-total { background-color: #fee2e2; color: #ef4444; padding: 8px 15px; border-radius: 8px; font-weight: bold; font-size: 14px; margin-left: auto; }

        /* GRID LAYOUT PARA COMPACT */
        .grades-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; align-items: start; }
        .table-container { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.03); border-top: 5px solid #ef4444; }
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .table-header h2 { font-size: 18px; color: #1e293b; margin: 0; }
        
        /* SCROLLABLE TABLE */
        .table-wrapper { max-height: 250px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px; }
        table { width: 100%; border-collapse: collapse; min-width: 500px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { position: sticky; top: 0; background: #f8fafc; color: #64748b; font-size: 12px; text-transform: uppercase; font-weight: bold; box-shadow: 0 1px 0 #e2e8f0; z-index: 1; }
        tr:hover { background-color: #fef2f2; }
        .badge-absent { background: #fee2e2; color: #ef4444; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; }

        /* PRINT SETTINGS */
        .print-only { display: none; }
        @media print {
            body { height: auto; overflow: visible; background: #fff; }
            #sidebar-container, .toolbar, .badge-total { display: none !important; }
            .main-content { padding: 0 !important; width: 100%; }
            .table-container { box-shadow: none; border: none; break-inside: avoid; margin-bottom: 30px; }
            .table-wrapper { max-height: none; overflow: visible; border: none; }
            .grades-grid { grid-template-columns: 1fr; }
            .print-only { display: block; text-align: center; margin-bottom: 20px; }
        }

        /* MOBILE */
        @media (max-width: 768px) {
            .grades-grid { grid-template-columns: 1fr; }
            .toolbar { flex-direction: column; align-items: stretch; }
            .badge-total { margin-left: 0; text-align: center; }
            .top-header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .btn-print { width: 100%; text-align: center; }
            .table-wrapper { overflow-x: auto; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="top-header">
            <h1><i class="fa-solid fa-user-xmark" style="color: #ef4444;"></i> Absentees Record</h1>
        </div>

        <div class="print-only">
            <h2>Absentees Report</h2>
            <p>Date: <?php echo $display_date; ?></p>
            <hr style="margin-top: 10px;">
        </div>

        <div class="toolbar">
            <form method="GET" action="absent.php" style="display: flex; gap: 10px; align-items: center;">
                <label style="font-weight: bold; color: #475569;">Select Date:</label>
                <input type="date" name="date" value="<?php echo $filter_date; ?>" onchange="this.form.submit()">
            </form>
            <button class="btn-print" onclick="window.print()"><i class="fa-solid fa-print"></i> Print Report</button>
            <div class="badge-total">Total Absent: <?php echo $total_absent; ?></div>
        </div>

        <div class="grades-grid">
            <?php foreach ($absentees_by_grade as $grade => $students): $total = count($students); ?>
            <div class="table-container">
                <div class="table-header">
                    <h2><?php echo $grade; ?> (<?php echo $total; ?>)</h2>
                </div>
                
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>RFID No.</th>
                                <th>Student Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total > 0): foreach ($students as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['rfid']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                    <td><span class="badge-absent">Absent</span></td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="3" style="text-align:center; padding: 20px; color: #10b981; font-weight: bold;"><i class="fa-solid fa-check-circle"></i> Perfect Attendance!</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>


    <?php include 'chat_widget.php'; ?>
</body>
</html>