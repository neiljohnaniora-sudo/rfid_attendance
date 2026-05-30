<?php
session_start(); // Kinahanglan gyud mag-una ni

// 1. I-check kung wala ba maka-login ang user
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

require 'connection.php'; // I-require ang connection human sa session check

// --- PHP LOGIC: EDIT STUDENT ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_student'])) {
    $id = $_POST['student_id']; $name = $_POST['name']; $grade = $_POST['grade']; $status = $_POST['status'];
    $guardian_email = $_POST['guardian_email'] ?? '';
    $sql = "UPDATE students SET name = ?, grade = ?, status = ?, guardian_email = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $name, $grade, $status, $guardian_email, $id);
    echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body>";
    if ($stmt->execute()) {
        echo "<script>Swal.fire({ icon: 'success', title: 'Updated', text: 'Student details updated.', showConfirmButton: false, timer: 1000 }).then(() => { window.location.href='records.php'; });</script>";
    } else {
        echo "<script>Swal.fire({ icon: 'error', title: 'Failed', text: 'Could not update student.' }).then(() => { window.location.href='records.php'; });</script>";
    }
    echo "</body></html>"; exit();
}

// --- PHP LOGIC: ADD STUDENT (PABILIN) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $rfid = $_POST['rfid']; $name = $_POST['name']; $grade = $_POST['grade']; $status = 'Active'; 
    $guardian_email = $_POST['guardian_email'] ?? '';
    $sql = "INSERT INTO students (rfid, name, grade, status, guardian_email) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $rfid, $name, $grade, $status, $guardian_email);
    echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body>";
    if ($stmt->execute()) {
        echo "<script>Swal.fire({ icon: 'success', title: 'Success', text: 'Student added.', showConfirmButton: false, timer: 1000 }).then(() => { window.location.href='records.php'; });</script>";
    } else {
        echo "<script>Swal.fire({ icon: 'error', title: 'Failed', text: 'RFID already exists.' }).then(() => { window.location.href='records.php'; });</script>";
    }
    echo "</body></html>"; exit();
}

// --- PHP LOGIC: DELETE STUDENT (PABILIN) ---
if (isset($_GET['delete_id'])) {
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete_id']);
    $stmt->execute();
    header("Location: records.php"); exit();
}

// --- PHP LOGIC: FETCH DATA (GIBUTANGAN UG FILTER) ---
$admin_role = isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : '';
$assigned_grade = isset($_SESSION['assigned_grade']) ? $_SESSION['assigned_grade'] : '';

// I-set ang pagination limit: 10 para sa Teacher, 3 para sa Admin
$limit_per_page = ($admin_role === 'Teacher') ? 10 : 3;

if ($admin_role === 'Teacher' && !empty($assigned_grade)) {
    // Usa ra ka grade array ang i-ready para sa Teacher
    $students_by_grade = [$assigned_grade => []];
    $result = $conn->query("SELECT * FROM students WHERE grade = '$assigned_grade' ORDER BY name ASC");
} else {
    // Tanan grades i-ready para sa Admin
    $students_by_grade = ['Grade 1'=>[],'Grade 2'=>[],'Grade 3'=>[],'Grade 4'=>[],'Grade 5'=>[],'Grade 6'=>[]];
    $result = $conn->query("SELECT * FROM students ORDER BY name ASC");
}

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if(array_key_exists($row['grade'], $students_by_grade)) $students_by_grade[$row['grade']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Records - Smart RFID Attendance</title>
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
        /* Dugang nga styles para sa Records ra */
        .header-buttons { display: flex; gap: 10px; }
        .btn-primary { background-color: #3b82f6; color: white; border: none; padding: 10px 18px; border-radius: 8px; cursor: pointer; font-weight: bold; }
        .btn-success { background-color: #10b981; color: white; border: none; padding: 10px 18px; border-radius: 8px; cursor: pointer; font-weight: bold; }
        
        .grades-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 25px; align-items: stretch; }
        .table-container { display: flex; flex-direction: column; background: #fff; border-radius: 15px; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border-top: 5px solid #3b82f6; border: 1px solid #f1f5f9; overflow-x: auto; }
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .table-header h2 { font-size: 18px; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 10px; }
        .control-input { padding: 10px 15px; border: 1px solid #e2e8f0; border-radius: 10px; outline: none; width: 200px; font-size: 14px; background: #f8fafc; }
        
        table { width: 100%; border-collapse: collapse; table-layout: fixed; min-width: 600px; }
        th, td { padding: 14px 12px; text-align: left; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        th { color: #64748b; font-size: 12px; text-transform: uppercase; background: #f8fafc; font-weight: 700; letter-spacing: 0.5px; }
        .badge-active { background: #dcfce7; color: #166534; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; display: inline-block; }
        .badge-inactive { background: #fee2e2; color: #ef4444; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; display: inline-block; }
        .btn-delete { color: #ef4444; font-size: 16px; transition: 0.2s; background: none; border: none; cursor: pointer; text-decoration: none; }
        .btn-delete:hover { color: #991b1b; transform: scale(1.1); }
        .btn-edit { color: #3b82f6; font-size: 16px; transition: 0.2s; background: none; border: none; cursor: pointer; margin-right: 15px; }
        .btn-edit:hover { color: #1d4ed8; transform: scale(1.1); }
        
        /* New Pagination Styles */
        .pagination-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            margin-top: auto;
            border-top: 1px solid #f1f5f9;
        }
        .page-info { font-size: 13px; font-weight: 600; color: #64748b; }
        .page-nav button {
            background: #f8fafc; border: 1px solid #e2e8f0; color: #475569;
            padding: 8px 14px; border-radius: 8px; cursor: pointer;
            font-weight: 600; margin-left: 8px; transition: 0.2s;
        }
        .page-nav button:hover:not(:disabled) { background: #eff6ff; border-color: #a5b4fc; }
        .page-nav button:disabled { opacity: 0.5; cursor: not-allowed; }

        /* Modal Settings */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); z-index: 1050; justify-content: center; align-items: center; backdrop-filter: blur(4px); }
        .modal-box { background: #fff; padding: 35px; border-radius: 15px; width: 100%; max-width: 420px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
        .form-group { margin-bottom: 20px; } 
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; color: #475569; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; outline: none; }
        .form-group input:focus { border-color: #3b82f6; background: #fff; }

        @media (max-width: 768px) {
            .grades-grid { grid-template-columns: 1fr; }
            .header-buttons { margin-top: 15px; flex-direction: column; width: 100%; }
            .header-buttons button { width: 100%; }
            .top-header { flex-direction: column; align-items: flex-start !important; gap: 15px; }
            .table-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .control-input { width: 100%; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="top-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px;">
            <div>
                <h1 style="color: #1e293b; font-size: 26px; font-weight: 800;">Student Records</h1>
                <p style="color: #64748b; font-size: 14px;">Manage and monitor registered students per grade level.</p>
            </div>
            <div class="header-buttons">
                <button class="btn-success" onclick="exportAllToCSV()"><i class="fa-solid fa-file-csv"></i> Download CSV</button>
                <button class="btn-primary" onclick="openTapModal()" style="background-color: #8b5cf6;"><i class="fa-solid fa-wifi"></i> Tap to Register</button>
                <button class="btn-primary" onclick="openModal()"><i class="fa-solid fa-plus"></i> Add Student</button>
            </div>
        </div>

        <div class="grades-grid">
            <?php foreach ($students_by_grade as $grade => $students): $grade_id = str_replace(' ', '-', $grade); $total = count($students); ?>
            <div class="table-container">
                <div class="table-header">
                    <h2><i class="fa-solid fa-graduation-cap" style="color:#3b82f6;"></i> <?php echo $grade; ?></h2>
                    <input type="text" class="control-input" placeholder="Search name..." onkeyup="filterTable(this, 'tbody-<?php echo $grade_id; ?>')">
                </div>
                
                <div style="flex-grow: 1; min-height: 180px;">
                    <table class="data-table" data-grade="<?php echo $grade; ?>" id="table-<?php echo $grade_id; ?>">
                        <thead>
                            <tr>
                                <th style="width: 120px;">RFID No.</th>
                                <th>Student Name</th>
                                <th style="width: 100px; text-align: center;">Status</th>
                                <th style="width: 100px; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-<?php echo $grade_id; ?>">
                            <?php if ($total > 0): foreach ($students as $index => $row): 
                                    $badge_class = ($row['status'] == 'Active') ? 'badge-active' : 'badge-inactive';
                            ?>
                                <tr>
                                    <td style="font-family: monospace; color: #64748b;"><?php echo htmlspecialchars($row['rfid']); ?></td>
                                    <td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><strong style="color: #1e293b;"><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                    <td style="text-align: center;"><span class="<?php echo $badge_class; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                    <td style="text-align: center;">
                                        <button class="btn-edit" onclick="openEditStudentModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)" title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <a href="records.php?delete_id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Delete this student?')">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="4" style="text-align:center; padding: 30px; color: #94a3b8;">No students registered in this grade.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total > $limit_per_page): // Show pagination controls ?>
                    <div class="pagination-controls" id="pagination-<?php echo $grade_id; ?>">
                        <div class="page-info">Page 1 of <?php echo ceil($total / $limit_per_page); ?></div>
                        <div class="page-nav">
                            <button class="prev-btn" disabled>Previous</button>
                            <button class="next-btn">Next</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal-overlay" id="addStudentModal">
        <div class="modal-box">
            <h2 style="margin-bottom: 25px; color: #1e293b;">Add New Student</h2>
            <form action="records.php" method="POST">
                <input type="hidden" name="add_student" value="1"> 
                <div class="form-group">
                    <label>RFID Number</label>
                    <input type="text" name="rfid" placeholder="Scan or type RFID..." required autofocus>
                </div>
                <div class="form-group">
                    <label>Guardian Email</label>
                    <input type="email" name="guardian_email" placeholder="parent@email.com">
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="Juan Dela Cruz" required>
                </div>
                <div class="form-group">
                    <label>Grade Level</label>
                    <select name="grade" required>
                        <?php if ($admin_role === 'Teacher' && !empty($assigned_grade)): ?>
                            <option value="<?php echo htmlspecialchars($assigned_grade); ?>"><?php echo htmlspecialchars($assigned_grade); ?></option>
                        <?php else: ?>
                            <option value="Grade 1">Grade 1</option>
                            <option value="Grade 2">Grade 2</option>
                            <option value="Grade 3">Grade 3</option>
                            <option value="Grade 4">Grade 4</option>
                            <option value="Grade 5">Grade 5</option>
                            <option value="Grade 6">Grade 6</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top: 30px;">
                    <button type="button" onclick="closeModal()" style="padding:12px 20px; border:none; background:#f1f5f9; cursor:pointer; border-radius:10px; font-weight: 600; color: #475569;">Cancel</button>
                    <button type="submit" class="btn-primary" style="padding:12px 25px;">Save Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bag-ong Modal para sa Tap to Register -->
    <div class="modal-overlay" id="tapRegisterModal">
        <div class="modal-box">
            <h2 style="margin-bottom: 25px; color: #1e293b;">Tap to Register</h2>
            
            <div id="tapStatus" style="text-align: center; margin-bottom: 20px;">
                <i class="fa-solid fa-wifi fa-fade" style="font-size: 40px; color: #8b5cf6; margin-bottom: 15px;"></i>
                <p style="color: #64748b; font-weight: bold; font-size: 16px;">Waiting for RFID Tap...</p>
                <p style="color: #94a3b8; font-size: 13px;">Please tap the ID card on the scanner.</p>
            </div>

            <form action="records.php" method="POST" id="tapForm" style="display: none;">
                <input type="hidden" name="add_student" value="1"> 
                <div class="form-group">
                    <label>RFID Number (Auto-detected)</label>
                    <input type="text" name="rfid" id="tapRfidInput" readonly style="background: #e2e8f0; font-family: monospace; font-weight: bold; color: #3b82f6;">
                </div>
                <div class="form-group">
                    <label>Guardian Email</label>
                    <input type="email" name="guardian_email" id="tapStudentGuardianEmail" placeholder="parent@email.com">
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="Juan Dela Cruz" required autofocus>
                </div>
                <div class="form-group">
                    <label>Grade Level</label>
                    <select name="grade" required>
                        <?php if ($admin_role === 'Teacher' && !empty($assigned_grade)): ?>
                            <option value="<?php echo htmlspecialchars($assigned_grade); ?>"><?php echo htmlspecialchars($assigned_grade); ?></option>
                        <?php else: ?>
                            <option value="Grade 1">Grade 1</option>
                            <option value="Grade 2">Grade 2</option>
                            <option value="Grade 3">Grade 3</option>
                            <option value="Grade 4">Grade 4</option>
                            <option value="Grade 5">Grade 5</option>
                            <option value="Grade 6">Grade 6</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top: 30px;">
                    <button type="button" onclick="closeTapModal()" style="padding:12px 20px; border:none; background:#f1f5f9; cursor:pointer; border-radius:10px; font-weight: 600; color: #475569;">Cancel</button>
                    <button type="submit" class="btn-primary" style="padding:12px 25px; background-color: #8b5cf6;">Save Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bag-ong Modal para sa Edit Student -->
    <div class="modal-overlay" id="editStudentModal">
        <div class="modal-box">
            <h2 style="margin-bottom: 25px; color: #1e293b;">Edit Student Info</h2>
            <form action="records.php" method="POST">
                <input type="hidden" name="edit_student" value="1"> 
                <input type="hidden" name="student_id" id="editStudentId"> 
                <div class="form-group">
                    <label>RFID Number (Fixed)</label>
                    <input type="text" id="editStudentRfid" readonly style="background: #e2e8f0; font-family: monospace; font-weight: bold; color: #64748b;">
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="editStudentName" required>
                </div>
                <div class="form-group">
                    <label>Guardian Email</label>
                    <input type="email" name="guardian_email" id="editStudentGuardianEmail" placeholder="parent@email.com">
                </div>
                <div class="form-group">
                    <label>Grade Level</label>
                    <select name="grade" id="editStudentGrade" required>
                        <?php if ($admin_role === 'Teacher' && !empty($assigned_grade)): ?>
                            <option value="<?php echo htmlspecialchars($assigned_grade); ?>"><?php echo htmlspecialchars($assigned_grade); ?></option>
                        <?php else: ?>
                            <option value="Grade 1">Grade 1</option>
                            <option value="Grade 2">Grade 2</option>
                            <option value="Grade 3">Grade 3</option>
                            <option value="Grade 4">Grade 4</option>
                            <option value="Grade 5">Grade 5</option>
                            <option value="Grade 6">Grade 6</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="editStudentStatus" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top: 30px;">
                    <button type="button" onclick="closeEditStudentModal()" style="padding:12px 20px; border:none; background:#f1f5f9; cursor:pointer; border-radius:10px; font-weight: 600; color: #475569;">Cancel</button>
                    <button type="submit" class="btn-primary" style="padding:12px 25px;">Update Info</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() { document.getElementById('addStudentModal').style.display = 'flex'; }
        function closeModal() { document.getElementById('addStudentModal').style.display = 'none'; }

        function openEditStudentModal(student) {
            document.getElementById('editStudentModal').style.display = 'flex';
            document.getElementById('editStudentId').value = student.id;
            document.getElementById('editStudentRfid').value = student.rfid;
            document.getElementById('editStudentName').value = student.name;
            document.getElementById('editStudentGuardianEmail').value = student.guardian_email || '';
            document.getElementById('editStudentGrade').value = student.grade;
            if (student.status) {
                document.getElementById('editStudentStatus').value = student.status;
            } else {
                document.getElementById('editStudentStatus').value = 'Active';
            }
        }
        function closeEditStudentModal() { 
            document.getElementById('editStudentModal').style.display = 'none'; 
        }

        // Close modals when clicking outside the modal box
        window.onclick = function(event) {
            let addModal = document.getElementById('addStudentModal');
            let tapModal = document.getElementById('tapRegisterModal');
            let editModal = document.getElementById('editStudentModal');
            if (event.target == addModal) {
                closeModal();
            } else if (event.target == tapModal) {
                closeTapModal();
            } else if (event.target == editModal) {
                closeEditStudentModal();
            }
        }

        // Tap to Register Logic
        let tapPolling;
        let lastTapTimestamp = null;
        let isFirstTapLoad = true;

        function openTapModal() { 
            document.getElementById('tapRegisterModal').style.display = 'flex'; 
            document.getElementById('tapStatus').style.display = 'block';
            document.getElementById('tapForm').style.display = 'none';
            document.getElementById('tapRfidInput').value = '';
            
            isFirstTapLoad = true;
            
            // I-fetch daan ang pinaka-latest nga timestamp pag-abli sa modal aron dili ma-ignore ang first tap
            fetch('check_live_scan.php?t=' + Date.now())
            .then(r => r.json())
            .then(data => {
                lastTapTimestamp = data.timestamp;
                isFirstTapLoad = false;
            });

            // Gipaspasan nato ang polling gikan sa 1500ms ngadto sa 800ms
            tapPolling = setInterval(checkLiveScanForRegistration, 800);
        }

        function closeTapModal() { 
            document.getElementById('tapRegisterModal').style.display = 'none'; 
            clearInterval(tapPolling);
        }

        function checkLiveScanForRegistration() {
            if (isFirstTapLoad) return; // Maghulat sa initial fetch
            
            fetch('check_live_scan.php?t=' + Date.now())
            .then(r => r.json())
            .then(data => {
                if(data.timestamp && data.timestamp !== lastTapTimestamp) {
                    
                    lastTapTimestamp = data.timestamp;
                    
                    // I-check kung rehistrado na ba daan ang card
                    if (data.action !== 'REGISTER') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Card Already Assigned',
                            text: `This RFID card is currently registered to ${data.name}. Please tap a new, unregistered card.`,
                            confirmButtonColor: '#8b5cf6',
                            confirmButtonText: 'Got it'
                        });
                        return; // Maghulat og bag-ong tap
                    }

                    // Kung REGISTER ang action sa json (pasabot Unregistered Card)
                    document.getElementById('tapStatus').style.display = 'none';
                    document.getElementById('tapForm').style.display = 'block';
                    document.getElementById('tapRfidInput').value = data.rfid;
                    
                    // Hunongon ang polling kay naa na tay RFID
                    clearInterval(tapPolling);
                }
            })
            .catch(err => console.log(err));
        }

        // --- PAGINATION & SEARCH LOGIC ---
        const paginators = {}; // Holds all pagination controller objects

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.data-table').forEach(table => {
                const gradeId = table.id.replace('table-', '');
                const paginationControls = document.getElementById('pagination-' + gradeId);
                if (!paginationControls) return; // Skip if no pagination needed

                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const rowsPerPage = <?php echo $limit_per_page; ?>;
                const numPages = Math.ceil(rows.length / rowsPerPage);

                const paginator = {
                    rows: rows,
                    currentPage: 1,
                    rowsPerPage: rowsPerPage,
                    numPages: numPages,
                    prevBtn: paginationControls.querySelector('.prev-btn'),
                    nextBtn: paginationControls.querySelector('.next-btn'),
                    pageInfo: paginationControls.querySelector('.page-info'),
                    
                    displayPage: function(page) {
                        this.currentPage = page;
                        const start = (page - 1) * this.rowsPerPage;
                        const end = start + this.rowsPerPage;

                        this.rows.forEach((row, index) => {
                            row.style.display = (index >= start && index < end) ? 'table-row' : 'none';
                        });

                        this.prevBtn.disabled = (page === 1);
                        this.nextBtn.disabled = (page === this.numPages);
                        this.pageInfo.textContent = `Page ${this.currentPage} of ${this.numPages}`;
                    },
                    reset: function() { this.displayPage(1); }
                };

                paginator.prevBtn.addEventListener('click', () => paginator.displayPage(paginator.currentPage - 1));
                paginator.nextBtn.addEventListener('click', () => paginator.displayPage(paginator.currentPage + 1));
                
                paginators[gradeId] = paginator;
                paginator.displayPage(1); // Initial display
            });
        });

        function filterTable(input, tbodyId) {
            let filter = input.value.toLowerCase();
            let gradeId = tbodyId.replace('tbody-', '');
            let paginationControls = document.getElementById('pagination-' + gradeId);
            const paginator = paginators[gradeId];

            if (filter !== "") {
                if (paginationControls) paginationControls.style.display = 'none';
                const allRows = paginator ? paginator.rows : document.getElementById(tbodyId).querySelectorAll('tr');
                
                allRows.forEach(row => {
                    let text = row.innerText.toLowerCase();
                    row.style.display = text.includes(filter) ? 'table-row' : 'none';
                });
            } else {
                if (paginationControls) {
                    paginationControls.style.display = 'flex';
                    if (paginator) paginator.reset();
                } else { // For tables without pagination
                    document.getElementById(tbodyId).querySelectorAll('tr').forEach(row => {
                        row.style.display = 'table-row';
                    });
                }
            }
        }

        // --- CSV Export Logic ---
        function exportAllToCSV() {
            let csv = "Grade Level,RFID Number,Student Name,Status\n";
            document.querySelectorAll('.data-table').forEach(table => {
                let grade = table.getAttribute('data-grade');
                table.querySelectorAll('tbody tr').forEach(row => {
                    if (row.cells.length >= 4) {
                        csv += `"${grade}","${row.cells[0].innerText}","${row.cells[1].innerText}","${row.cells[2].innerText.trim()}"\n`;
                    }
                });
            });
            let link = document.createElement("a");
            link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
            link.download = "Students_Records.csv"; link.click();
        }
    </script>

    <?php include 'chat_widget.php'; ?>
</body>
</html>