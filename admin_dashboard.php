<?php
/**
 * Admin/Staff Dashboard
 * Manages all applications, complaints, and public content.
 */
require_once 'includes/db_connect.php';

// Check if user is logged in and is Admin or Staff
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header('Location: index.php?page=login');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$message = '';
$error = '';

// --- Application Management Logic ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_application'])) {
    $app_id = (int)$_POST['app_id'];
    $status = trim($_POST['new_status']);
    $remark = trim($_POST['remark']);
    
    // Simple mock for PDF generation (The bonus feature is simulated by setting a path)
    $cert_path = null;
    if ($status === 'Approved') {
        // In a real application, FPDF/TCPDF would generate a certificate here
        $cert_path = "uploads/certificate_" . $app_id . "_" . time() . ".pdf";
        // Create a dummy file to make the link work (otherwise file_exists() check in a real server environment would fail)
        file_put_contents($cert_path, "--- MOCK DIGITAL CERTIFICATE (ID: $app_id) ---");
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE applications 
            SET status = ?, remark = ?, certificate_download_path = ?
            WHERE application_id = ?
        ");
        $stmt->execute([$status, $remark, $cert_path, $app_id]);
        $message = "Application ID $app_id status updated to $status.";
    } catch (Exception $e) {
        $error = "Error updating application: " . $e->getMessage();
    }
}

// --- Grievance/Complaint Management Logic ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_complaint'])) {
    $comp_id = (int)$_POST['comp_id'];
    $status = trim($_POST['new_status']);
    $assigned_to = trim($_POST['assigned_to']);
    $admin_response = trim($_POST['admin_response']);
    
    try {
        $stmt = $pdo->prepare("
            UPDATE complaints 
            SET status = ?, assigned_to = ?, admin_response = ?
            WHERE complaint_id = ?
        ");
        $stmt->execute([$status, $assigned_to, $admin_response, $comp_id]);
        $message = "Complaint ID $comp_id status updated to $status.";
    } catch (Exception $e) {
        $error = "Error updating complaint: " . $e->getMessage();
    }
}

// --- Content Management Logic (Announcements/Schemes) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['add_content']) || isset($_POST['edit_content']))) {
    $type = $_POST['content_type']; // 'notification' or 'scheme'
    $action = isset($_POST['add_content']) ? 'add' : 'edit';
    $id = (int)($_POST['content_id'] ?? 0);

    if ($type == 'notification') {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $note_type = trim($_POST['note_type']); // News, Notice, Meeting
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($action == 'add') {
            $stmt = $pdo->prepare("INSERT INTO notifications (title, content, type, is_active) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $content, $note_type, $is_active]);
            $message = "New Announcement added successfully.";
        } else {
            $stmt = $pdo->prepare("UPDATE notifications SET title = ?, content = ?, type = ?, is_active = ? WHERE notification_id = ?");
            $stmt->execute([$title, $content, $note_type, $is_active, $id]);
            $message = "Announcement ID $id updated successfully.";
        }
    } elseif ($type == 'scheme') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $eligibility = trim($_POST['eligibility']);
        $how_to_apply = trim($_POST['how_to_apply']);
        
        if ($action == 'add') {
            $stmt = $pdo->prepare("INSERT INTO schemes (name, description, eligibility, how_to_apply) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $description, $eligibility, $how_to_apply]);
            $message = "New Scheme added successfully.";
        } else {
            $stmt = $pdo->prepare("UPDATE schemes SET name = ?, description = ?, eligibility = ?, how_to_apply = ? WHERE scheme_id = ?");
            $stmt->execute([$name, $description, $eligibility, $how_to_apply, $id]);
            $message = "Scheme ID $id updated successfully.";
        }
    }
}

// --- Data Fetching ---
$page = $_GET['action'] ?? 'home';

// Get Applications
$applications = $pdo->query("
    SELECT a.*, u.full_name, u.email 
    FROM applications a
    JOIN users u ON a.user_id = u.user_id
    ORDER BY a.applied_at DESC
")->fetchAll();

// Get Complaints
$complaints = $pdo->query("
    SELECT c.*, u.full_name, u.email 
    FROM complaints c
    JOIN users u ON c.user_id = u.user_id
    ORDER BY c.submitted_at DESC
")->fetchAll();

// Get Content for Management Page
$notifications = $pdo->query("SELECT * FROM notifications ORDER BY published_at DESC")->fetchAll();
$schemes = $pdo->query("SELECT * FROM schemes ORDER BY published_at DESC")->fetchAll();

// --- HTML Structure (Header) ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | e-Grampanchayat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f9; }
        .gov-blue { background-color: #1a4f91; }
        .card-shadow { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.06); }
    </style>
</head>
<body>

    <!-- Header & Navigation -->
    <header class="gov-blue text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <span class="text-3xl">⚙️</span>
                <span class="text-xl font-bold"><?= strtoupper($user_role) ?> Dashboard</span>
            </div>
            <div class="text-sm font-medium">
                <span>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></span>
                <a href="logout.php" class="ml-4 bg-red-600 px-3 py-1 rounded-full hover:bg-red-700 transition">Logout</a>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <!-- Sidebar Navigation -->
            <nav class="lg:col-span-1 bg-white p-6 rounded-xl card-shadow h-min">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Modules</h3>
                <ul class="space-y-2">
                    <li><a href="?action=home" class="block p-3 rounded-lg text-gray-700 hover:bg-blue-50 transition duration-150 <?= $page == 'home' ? 'bg-blue-100 font-bold text-gov-blue' : '' ?>">Overview</a></li>
                    <li><a href="?action=applications" class="block p-3 rounded-lg text-gray-700 hover:bg-blue-50 transition duration-150 <?= $page == 'applications' ? 'bg-blue-100 font-bold text-gov-blue' : '' ?>">Manage Applications</a></li>
                    <li><a href="?action=grievances" class="block p-3 rounded-lg text-gray-700 hover:bg-blue-50 transition duration-150 <?= $page == 'grievances' ? 'bg-blue-100 font-bold text-gov-blue' : '' ?>">Grievance Management</a></li>
                    <li><a href="?action=content" class="block p-3 rounded-lg text-gray-700 hover:bg-blue-50 transition duration-150 <?= $page == 'content' ? 'bg-blue-100 font-bold text-gov-blue' : '' ?>">Content Management</a></li>
                </ul>
            </nav>

            <!-- Main Content -->
            <div class="lg:col-span-3">

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <?php
                switch ($page) {
                    case 'home':
                        // --- Admin Dashboard Home: Summary ---
                        $total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'citizen'")->fetchColumn();
                        $pending_apps = count(array_filter($applications, fn($app) => $app['status'] == 'Pending'));
                        $resolved_comps = count(array_filter($complaints, fn($comp) => $comp['status'] == 'Resolved'));
                        ?>
                        <h1 class="text-3xl font-bold text-gray-900 mb-6">System Overview</h1>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                            <div class="bg-white p-6 rounded-xl card-shadow border-l-4 border-blue-500">
                                <p class="text-gray-500">Total Citizens</p>
                                <p class="text-3xl font-bold text-gray-900"><?= $total_users ?></p>
                            </div>
                            <div class="bg-white p-6 rounded-xl card-shadow border-l-4 border-yellow-500">
                                <p class="text-gray-500">Pending Applications</p>
                                <p class="text-3xl font-bold text-gray-900"><?= $pending_apps ?></p>
                            </div>
                            <div class="bg-white p-6 rounded-xl card-shadow border-l-4 border-green-500">
                                <p class="text-gray-500">Resolved Complaints</p>
                                <p class="text-3xl font-bold text-gray-900"><?= $resolved_comps ?></p>
                            </div>
                        </div>

                        <h2 class="text-2xl font-semibold text-gray-800 mb-4 border-b pb-2">Recent Pending Applications (Quick Action)</h2>
                        <div class="space-y-4">
                            <?php 
                            $recent_pending = array_filter($applications, fn($app) => $app['status'] == 'Pending');
                            $recent_pending = array_slice($recent_pending, 0, 5);
                            if (empty($recent_pending)): ?>
                                <p class="text-gray-500 p-4 bg-white rounded-lg card-shadow">No pending applications right now. Excellent!</p>
                            <?php else: ?>
                                <?php foreach ($recent_pending as $app): ?>
                                    <div class="p-4 bg-white rounded-xl card-shadow border-l-4 border-yellow-500 flex justify-between items-center">
                                        <div>
                                            <h3 class="font-semibold text-lg text-gray-900"><?= htmlspecialchars($app['certificate_type']) ?> by <?= htmlspecialchars($app['full_name']) ?></h3>
                                            <p class="text-sm text-gray-600">ID: <?= $app['application_id'] ?> | Applied: <?= date('M d, Y', strtotime($app['applied_at'])) ?></p>
                                        </div>
                                        <a href="?action=applications&view_id=<?= $app['application_id'] ?>" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">Review</a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <?php
                        break;

                    case 'applications':
                        // --- Manage Applications ---
                        $view_app_id = (int)($_GET['view_id'] ?? 0);
                        if ($view_app_id) {
                            // Single Application View/Action
                            $stmt = $pdo->prepare("
                                SELECT a.*, u.full_name, u.email, u.phone_number, u.aadhaar_number 
                                FROM applications a
                                JOIN users u ON a.user_id = u.user_id
                                WHERE application_id = ?
                            ");
                            $stmt->execute([$view_app_id]);
                            $app = $stmt->fetch();

                            if ($app):
                            ?>
                                <a href="?action=applications" class="text-blue-600 hover:underline mb-4 inline-block">&larr; Back to Applications</a>
                                <div class="bg-white p-8 rounded-xl card-shadow">
                                    <h2 class="text-3xl font-bold text-gov-blue mb-4">Application Review (ID: <?= $app['application_id'] ?>)</h2>
                                    
                                    <div class="grid grid-cols-2 gap-4 text-sm mb-6 pb-4 border-b">
                                        <div><strong>Type:</strong> <?= htmlspecialchars($app['certificate_type']) ?></div>
                                        <div><strong>Status:</strong> <span class="font-bold text-red-600"><?= htmlspecialchars($app['status']) ?></span></div>
                                        <div><strong>Applicant:</strong> <?= htmlspecialchars($app['full_name']) ?> (<?= htmlspecialchars($app['email']) ?>)</div>
                                        <div><strong>Aadhaar:</strong> <?= htmlspecialchars($app['aadhaar_number']) ?></div>
                                    </div>

                                    <div class="mb-6">
                                        <h3 class="text-xl font-semibold mb-2">Application Details</h3>
                                        <p class="p-3 bg-gray-50 rounded-lg text-gray-700"><?= nl2br(htmlspecialchars($app['application_details'])) ?></p>
                                    </div>
                                    
                                    <div class="mb-8">
                                        <h3 class="text-xl font-semibold mb-2">Documents</h3>
                                        <a href="<?= htmlspecialchars($app['document_path']) ?>" target="_blank" class="text-green-600 hover:underline font-medium">
                                            <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            View Uploaded Citizen Document
                                        </a>
                                        <?php if ($app['certificate_download_path']): ?>
                                            <a href="<?= htmlspecialchars($app['certificate_download_path']) ?>" download class="ml-4 text-blue-600 hover:underline font-medium">
                                                <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H5a2 2 0 01-2-2v-5a2 2 0 012-2h14a2 2 0 012 2v5a2 2 0 01-2 2z"></path></svg>
                                                Download Generated Certificate
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h3 class="text-xl font-semibold mb-3">Update Status</h3>
                                    <form method="POST" action="?action=applications" class="space-y-4">
                                        <input type="hidden" name="update_application" value="1">
                                        <input type="hidden" name="app_id" value="<?= $app['application_id'] ?>">
                                        
                                        <div>
                                            <label for="new_status" class="block text-sm font-medium text-gray-700">New Status</label>
                                            <select name="new_status" id="new_status" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                                <option value="Pending" <?= $app['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="In Review" <?= $app['status'] == 'In Review' ? 'selected' : '' ?>>In Review</option>
                                                <option value="Approved" <?= $app['status'] == 'Approved' ? 'selected' : '' ?>>Approved (Generate Certificate)</option>
                                                <option value="Rejected" <?= $app['status'] == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label for="remark" class="block text-sm font-medium text-gray-700">Officer Remarks</label>
                                            <textarea name="remark" id="remark" rows="3" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Reason for rejection or note on approval..."><?= htmlspecialchars($app['remark'] ?? '') ?></textarea>
                                        </div>

                                        <button type="submit" class="bg-blue-600 text-white font-semibold py-2.5 px-6 rounded-lg transition duration-300 hover:bg-blue-700">Update Application</button>
                                    </form>
                                </div>

                            <?php
                            else:
                                $error = "Application not found.";
                                header('Location: ?action=applications');
                                exit;
                            endif;

                        } else {
                            // Application List View
                            ?>
                            <h2 class="text-3xl font-bold text-gray-900 mb-6 border-b pb-2">All Applications</h2>
                            
                            <div class="overflow-x-auto bg-white rounded-xl card-shadow">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php foreach ($applications as $app): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= $app['application_id'] ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($app['certificate_type']) ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($app['full_name']) ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                        <?= match($app['status']) {
                                                            'Approved' => 'bg-green-100 text-green-800',
                                                            'Rejected' => 'bg-red-100 text-red-800',
                                                            'In Review' => 'bg-yellow-100 text-yellow-800',
                                                            default => 'bg-blue-100 text-blue-800',
                                                        } ?>">
                                                        <?= htmlspecialchars($app['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('Y-m-d', strtotime($app['applied_at'])) ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <a href="?action=applications&view_id=<?= $app['application_id'] ?>" class="text-indigo-600 hover:text-indigo-900">View/Update</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php
                        }
                        break;

                    case 'content':
                        // --- Manage Announcements and Schemes ---
                        ?>
                        <h2 class="text-3xl font-bold text-gray-900 mb-6 border-b pb-2">Content Management</h2>

                        <!-- Add Announcement/Scheme Form -->
                        <div class="bg-white p-6 rounded-xl card-shadow mb-8">
                            <h3 class="text-2xl font-semibold text-gov-blue mb-4">Add New Public Content</h3>
                            <form method="POST" action="?action=content" class="space-y-4">
                                <input type="hidden" name="add_content" value="1">

                                <div>
                                    <label for="content_type" class="block text-sm font-medium text-gray-700">Content Type</label>
                                    <select name="content_type" id="content_type" required onchange="toggleContentForm(this.value)" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg">
                                        <option value="">-- Select Type --</option>
                                        <option value="notification">Announcement / News</option>
                                        <option value="scheme">Government Scheme</option>
                                    </select>
                                </div>

                                <!-- Announcement Fields -->
                                <div id="notification-fields" class="space-y-4 border p-4 rounded-lg hidden">
                                    <div>
                                        <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                                        <input type="text" name="title" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                    <div>
                                        <label for="note_type" class="block text-sm font-medium text-gray-700">Category</label>
                                        <select name="note_type" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg">
                                            <option value="Notice">Notice</option>
                                            <option value="News">News</option>
                                            <option value="Meeting">Meeting</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="content" class="block text-sm font-medium text-gray-700">Content</label>
                                        <textarea name="content" rows="3" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" name="is_active" id="is_active" checked class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                        <label for="is_active" class="ml-2 block text-sm text-gray-900">Active / Publish Now</label>
                                    </div>
                                </div>

                                <!-- Scheme Fields -->
                                <div id="scheme-fields" class="space-y-4 border p-4 rounded-lg hidden">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700">Scheme Name</label>
                                        <input type="text" name="name" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                    <div>
                                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                        <textarea name="description" rows="3" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Short description of the scheme."></textarea>
                                    </div>
                                    <div>
                                        <label for="eligibility" class="block text-sm font-medium text-gray-700">Eligibility (Use one line per point)</label>
                                        <textarea name="eligibility" rows="3" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="e.g., Annual income below X\nResident of the village for Y years"></textarea>
                                    </div>
                                    <div>
                                        <label for="how_to_apply" class="block text-sm font-medium text-gray-700">How to Apply</label>
                                        <textarea name="how_to_apply" rows="3" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Step-by-step instructions."></textarea>
                                    </div>
                                </div>

                                <button type="submit" class="bg-green-600 text-white font-semibold py-2.5 px-6 rounded-lg transition duration-300 hover:bg-green-700">Add Content</button>
                            </form>
                        </div>
                        
                        <script>
                            function toggleContentForm(type) {
                                document.getElementById('notification-fields').classList.add('hidden');
                                document.getElementById('scheme-fields').classList.add('hidden');
                                
                                if (type === 'notification') {
                                    document.getElementById('notification-fields').classList.remove('hidden');
                                } else if (type === 'scheme') {
                                    document.getElementById('scheme-fields').classList.remove('hidden');
                                }
                            }
                        </script>

                        <!-- Content Lists -->
                        <h3 class="text-2xl font-semibold text-gray-800 mb-4 mt-8">Published Announcements (<?= count($notifications) ?>)</h3>
                        <div class="overflow-x-auto bg-white rounded-xl card-shadow mb-8">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Active</th></tr></thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($notifications as $note): ?>
                                        <tr>
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= $note['notification_id'] ?></td>
                                            <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($note['title']) ?></td>
                                            <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($note['type']) ?></td>
                                            <td class="px-6 py-4 text-sm text-gray-700"><?= $note['is_active'] ? 'Yes' : 'No' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <h3 class="text-2xl font-semibold text-gray-800 mb-4 mt-8">Published Schemes (<?= count($schemes) ?>)</h3>
                        <div class="overflow-x-auto bg-white rounded-xl card-shadow">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th></tr></thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($schemes as $scheme): ?>
                                        <tr>
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= $scheme['scheme_id'] ?></td>
                                            <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($scheme['name']) ?></td>
                                            <td class="px-6 py-4 text-sm text-gray-700"><?= substr(htmlspecialchars($scheme['description']), 0, 80) ?>...</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php
                        break;
                        
                    case 'grievances':
                        // --- Grievance Management ---
                        $view_comp_id = (int)($_GET['view_id'] ?? 0);
                        if ($view_comp_id) {
                            // Single Complaint View/Action
                            $stmt = $pdo->prepare("
                                SELECT c.*, u.full_name, u.email, u.phone_number 
                                FROM complaints c
                                JOIN users u ON c.user_id = u.user_id
                                WHERE complaint_id = ?
                            ");
                            $stmt->execute([$view_comp_id]);
                            $comp = $stmt->fetch();

                            if ($comp):
                            ?>
                                <a href="?action=grievances" class="text-blue-600 hover:underline mb-4 inline-block">&larr; Back to Grievances</a>
                                <div class="bg-white p-8 rounded-xl card-shadow">
                                    <h2 class="text-3xl font-bold text-gov-blue mb-4">Grievance Review (ID: <?= $comp['complaint_id'] ?>)</h2>
                                    
                                    <div class="grid grid-cols-2 gap-4 text-sm mb-6 pb-4 border-b">
                                        <div><strong>Subject:</strong> <?= htmlspecialchars($comp['subject']) ?></div>
                                        <div><strong>Status:</strong> <span class="font-bold text-red-600"><?= htmlspecialchars($comp['status']) ?></span></div>
                                        <div><strong>Complainant:</strong> <?= htmlspecialchars($comp['full_name']) ?> (<?= htmlspecialchars($comp['email']) ?>)</div>
                                        <div><strong>Phone:</strong> <?= htmlspecialchars($comp['phone_number']) ?></div>
                                        <div><strong>Submitted:</strong> <?= date('M d, Y H:i', strtotime($comp['submitted_at'])) ?></div>
                                        <div><strong>Assigned To:</strong> <?= htmlspecialchars($comp['assigned_to'] ?? 'Not Assigned') ?></div>
                                    </div>

                                    <div class="mb-6">
                                        <h3 class="text-xl font-semibold mb-2">Complaint Description</h3>
                                        <p class="p-3 bg-gray-50 rounded-lg text-gray-700"><?= nl2br(htmlspecialchars($comp['description'])) ?></p>
                                    </div>
                                    
                                    <?php if ($comp['admin_response']): ?>
                                    <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-500 rounded">
                                        <h3 class="text-lg font-semibold mb-2 text-blue-900">Previous Admin Response</h3>
                                        <p class="text-gray-700"><?= nl2br(htmlspecialchars($comp['admin_response'])) ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <h3 class="text-xl font-semibold mb-3">Update Grievance</h3>
                                    <form method="POST" action="?action=grievances" class="space-y-4">
                                        <input type="hidden" name="update_complaint" value="1">
                                        <input type="hidden" name="comp_id" value="<?= $comp['complaint_id'] ?>">
                                        
                                        <div>
                                            <label for="new_status" class="block text-sm font-medium text-gray-700">Status</label>
                                            <select name="new_status" id="new_status" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                                <option value="Pending" <?= $comp['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="Under Investigation" <?= $comp['status'] == 'Under Investigation' ? 'selected' : '' ?>>Under Investigation</option>
                                                <option value="In Progress" <?= $comp['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                                <option value="Resolved" <?= $comp['status'] == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                                <option value="Closed" <?= $comp['status'] == 'Closed' ? 'selected' : '' ?>>Closed</option>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label for="assigned_to" class="block text-sm font-medium text-gray-700">Assign To (Staff Member/Department)</label>
                                            <input type="text" name="assigned_to" id="assigned_to" value="<?= htmlspecialchars($comp['assigned_to'] ?? '') ?>" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., Water Department, John Doe">
                                        </div>
                                        
                                        <div>
                                            <label for="admin_response" class="block text-sm font-medium text-gray-700">Admin Response / Action Taken</label>
                                            <textarea name="admin_response" id="admin_response" rows="4" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Provide update on actions taken or resolution details..."><?= htmlspecialchars($comp['admin_response'] ?? '') ?></textarea>
                                        </div>

                                        <button type="submit" class="bg-blue-600 text-white font-semibold py-2.5 px-6 rounded-lg transition duration-300 hover:bg-blue-700">Update Grievance</button>
                                    </form>
                                </div>

                            <?php
                            else:
                                $error = "Grievance not found.";
                                header('Location: ?action=grievances');
                                exit;
                            endif;

                        } else {
                            // Grievance List View
                            ?>
                            <h2 class="text-3xl font-bold text-gray-900 mb-6 border-b pb-2">Grievance Management</h2>

                            <div class="space-y-4">
                                <?php if (empty($complaints)): ?>
                                    <p class="text-gray-500 p-4 bg-white rounded-lg card-shadow">No complaints submitted by citizens.</p>
                                <?php else: ?>
                                    <?php foreach ($complaints as $comp): ?>
                                        <?php
                                            $status_class = match($comp['status']) {
                                                'Resolved' => 'border-green-500',
                                                'Under Investigation', 'In Progress' => 'border-yellow-500',
                                                default => 'border-red-500',
                                            };
                                            $badge_class = match($comp['status']) {
                                                'Resolved' => 'bg-green-100 text-green-700',
                                                'Under Investigation', 'In Progress' => 'bg-yellow-100 text-yellow-700',
                                                default => 'bg-red-100 text-red-700',
                                            };
                                        ?>
                                        <div class="p-4 bg-white rounded-xl card-shadow border-l-4 <?= $status_class ?>">
                                            <div class="flex justify-between items-start mb-2">
                                                <div class="flex-1">
                                                    <h3 class="font-semibold text-lg text-gray-900"><?= htmlspecialchars($comp['subject']) ?></h3>
                                                    <p class="text-sm text-gray-600 mt-1">
                                                        <span class="font-medium">ID:</span> <?= $comp['complaint_id'] ?> | 
                                                        <span class="font-medium">From:</span> <?= htmlspecialchars($comp['full_name']) ?> (<?= htmlspecialchars($comp['email']) ?>) |
                                                        <span class="font-medium">Submitted:</span> <?= date('M d, Y', strtotime($comp['submitted_at'])) ?>
                                                    </p>
                                                </div>
                                                <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $badge_class ?> ml-4"><?= htmlspecialchars($comp['status']) ?></span>
                                            </div>
                                            
                                            <p class="text-sm text-gray-800 mt-2 p-2 bg-gray-50 rounded line-clamp-2"><?= htmlspecialchars(substr($comp['description'], 0, 200)) ?><?= strlen($comp['description']) > 200 ? '...' : '' ?></p>
                                            
                                            <div class="mt-3 pt-3 border-t flex justify-between items-center">
                                                <div class="text-xs text-gray-600">
                                                    <span class="font-medium">Assigned To:</span> <?= htmlspecialchars($comp['assigned_to'] ?? 'Not Assigned') ?>
                                                </div>
                                                <a href="?action=grievances&view_id=<?= $comp['complaint_id'] ?>" class="bg-blue-500 text-white px-4 py-1.5 rounded-lg hover:bg-blue-600 transition text-sm font-medium">View & Update</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <?php
                        }
                        break;
                }
                ?>
            </div>
        </div>
    </div>
    
    <!-- Logout handler (simplified for a single-file concept) -->
    <a href="?action=logout" style="display:none;"></a>
    <?php if (isset($_GET['action']) && $_GET['action'] == 'logout'): ?>
        <?php 
        session_destroy();
        header('Location: index.php');
        exit;
        ?>
    <?php endif; ?>

</body>
</html>