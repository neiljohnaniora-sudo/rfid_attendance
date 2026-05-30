<?php
session_start(); // Kinahanglan mag-una gyud ni

// 1. I-check kung wala ba maka-login ang user
if (!isset($_SESSION['admin_id'])) {
    // Kung wala, i-redirect sila balik sa login page (index.php)
    header("Location: index.php");
    exit();
}

require 'connection.php'; 
date_default_timezone_set('Asia/Manila'); 

$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

$admin_role = isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : '';
$assigned_grade = isset($_SESSION['assigned_grade']) ? $_SESSION['assigned_grade'] : '';

$sql = "SELECT a.date, a.student_name, s.grade, a.time_in, a.time_out, a.status 
        FROM attendance_logs a LEFT JOIN students s ON a.student_id = s.rfid WHERE 1=1";

// KINI ANG FILTER PARA SA TEACHER
if ($admin_role === 'Teacher' && !empty($assigned_grade)) {
    $sql .= " AND s.grade = '" . $conn->real_escape_string($assigned_grade) . "'";
}

if (!empty($search)) $sql .= " AND (a.student_name LIKE '%" . $conn->real_escape_string($search) . "%')";
if (!empty($date_filter)) $sql .= " AND a.date = '" . $conn->real_escape_string($date_filter) . "'";
$sql .= " ORDER BY a.date DESC, a.time_in DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Logs</title>
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <link rel="apple-touch-icon" href="icon-192.png">
    <script>
      // I-register ang Service Worker
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('sw.js')
            .then(reg => console.log('Service worker registered', reg))
            .catch(err => console.log('Service worker not registered', err));
        });
      }
    </script>
    <style>
        /* Limpyo nga styles para sa table */
        .logs-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .table-container { margin-top: 20px; display: flex; flex-direction: column; min-height: 650px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; color: #64748b; font-size: 12px; text-transform: uppercase; padding: 15px; text-align: left; border-bottom: 2px solid #f1f5f9; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-size: 14px; }
        td:nth-child(2) { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .bg-success { background: #dcfce7; color: #166534; }
        .bg-warning { background: #fef9c3; color: #854d0e; }
        .filter-row { display: flex; gap: 15px; margin-bottom: 25px; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 12px; font-weight: 700; color: #64748b; }
        .filter-group input { padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; outline: none; }
        .btn-filter { background: #1e3a8a; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        
        /* Pagination Styles */
        .pagination-controls { display: flex; justify-content: space-between; align-items: center; padding-top: 15px; margin-top: auto; border-top: 1px solid #f1f5f9; }
        .page-info { font-size: 13px; font-weight: 600; color: #64748b; }
        .page-nav button { background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; padding: 8px 14px; border-radius: 8px; cursor: pointer; font-weight: 600; margin-left: 8px; transition: 0.2s; }
        .page-nav button:hover:not(:disabled) { background: #eff6ff; border-color: #a5b4fc; }
        .page-nav button:disabled { opacity: 0.5; cursor: not-allowed; }
        
        @media (max-width: 768px) {
            .filter-row { flex-direction: column; align-items: stretch; }
            .btn-filter { width: 100%; }
            .logs-header { flex-direction: column; align-items: flex-start !important; gap: 10px; }
            .logs-header > div { text-align: left !important; }
            
            /* Responsive Card View */
            .logs-card { padding: 15px; background: transparent; border: none; box-shadow: none; }
            .table-container { min-height: auto; overflow: visible; margin-top: 10px; border: none; }
            table { border: none; }
            thead { display: none; }
            tr { display: block; background: #fff; margin-bottom: 15px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; padding: 10px; }
            td { display: flex; justify-content: space-between; align-items: center; padding: 12px 10px; border-bottom: 1px solid #f1f5f9; }
            td:last-child { border-bottom: none; }
            td::before { content: attr(data-label); font-weight: 700; color: #64748b; font-size: 11px; text-transform: uppercase; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="logs-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h2 style="font-weight: 800; color: #1e293b; font-size: 28px;">Attendance Logs</h2>
                <p style="color: #64748b;">Monitoring daily student attendance</p>
            </div>
            <div style="text-align: right;">
                <h3 id="liveClock" style="font-size: 24px; font-weight: 800; color: #1e3a8a;">00:00:00</h3>
                <p style="color: #64748b; font-weight: 600;"><?php echo date('F d, Y'); ?></p>
            </div>
        </div>

        <div class="logs-card">
            <form method="GET" class="filter-row">
                <div class="filter-group">
                    <label>Search Name</label>
                    <input type="text" name="search" placeholder="Enter student name..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <label>Filter Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                </div>
                <button type="submit" class="btn-filter"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
                <a href="logs.php" style="text-decoration:none; color:#64748b; font-size:14px; margin-bottom:10px;">Clear</a>
            </form>

            <div class="table-container">
                <table id="logsTable">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Date</th>
                            <th>Student Name</th>
                            <th style="width: 90px;">Grade</th>
                            <th style="width: 100px;">Time In</th>
                            <th style="width: 100px;">Time Out</th>
                            <th style="width: 100px;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="logsBody">
                        <?php if ($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): 
                            $b_class = ($row['status'] == 'On-Time') ? 'bg-success' : 'bg-warning';
                        ?>
                            <tr>
                                <td data-label="Date"><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                <td data-label="Student Name"><strong><?php echo htmlspecialchars($row['student_name']); ?></strong></td>
                                <td data-label="Grade"><?php echo htmlspecialchars($row['grade'] ?? 'N/A'); ?></td>
                                <td data-label="Time In"><?php echo date('h:i A', strtotime($row['time_in'])); ?></td>
                                <td data-label="Time Out"><?php echo $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '--'; ?></td>
                                <td data-label="Status"><span class="badge <?php echo $b_class; ?>"><?php echo $row['status']; ?></span></td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="6" style="text-align:center; padding: 30px; color: #94a3b8;">No records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="pagination-controls" id="paginationControls">
                    <div class="page-info">Page 1 of 1</div>
                    <div class="page-nav">
                        <button class="prev-btn" disabled>Previous</button>
                        <button class="next-btn" disabled>Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sync with Server Time
        const serverTimeMs = <?php echo time() * 1000; ?>;
        const localTimeMs = Date.now();
        const timeDiff = serverTimeMs - localTimeMs;

        setInterval(() => {
            let d = new Date(Date.now() + timeDiff);
            document.getElementById('liveClock').innerText = d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'});
        }, 1000);

        // Pagination Logic
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.getElementById('logsBody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const paginationControls = document.getElementById('paginationControls');
            
            // I-hide ang pagination kung walay sulod
            if (rows.length === 1 && rows[0].cells[0].colSpan > 1) {
                paginationControls.style.display = 'none';
                return;
            }

            const rowsPerPage = 10;
            const numPages = Math.ceil(rows.length / rowsPerPage);
            
            // I-hide kung usa ra ka page
            if (numPages <= 1) {
                paginationControls.style.display = 'none';
                return;
            }

            const prevBtn = paginationControls.querySelector('.prev-btn');
            const nextBtn = paginationControls.querySelector('.next-btn');
            const pageInfo = paginationControls.querySelector('.page-info');
            let currentPage = 1;

            function displayPage(page) {
                currentPage = page;
                const start = (page - 1) * rowsPerPage;
                const end = start + rowsPerPage;
                rows.forEach((row, index) => {
                    row.style.display = (index >= start && index < end) ? 'table-row' : 'none';
                });
                prevBtn.disabled = (page === 1);
                nextBtn.disabled = (page === numPages);
                pageInfo.textContent = `Page ${currentPage} of ${numPages}`;
            }
            prevBtn.addEventListener('click', () => displayPage(currentPage - 1));
            nextBtn.addEventListener('click', () => displayPage(currentPage + 1));
            displayPage(1);
        });
    </script>

    <?php include 'chat_widget.php'; ?>
</body>
</html>