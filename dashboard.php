<?php
/**
 * Citizen Dashboard
 * Manages user profile, application submission, status tracking, and complaints.
 */
require_once 'includes/db_connect.php';

// Check if user is logged in and is a citizen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'citizen') {
    header('Location: index.php?page=login');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_full_name = $_SESSION['full_name'];
$user_info = [];
$message = '';
$error = '';

// Fetch User Profile Info
try {
    $stmt = $pdo->prepare("SELECT full_name, aadhaar_number, phone_number, email FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_info = $stmt->fetch();
} catch (Exception $e) {
    $error = "Could not fetch user profile: " . $e->getMessage();
}

// --- Application Handling ---
$page = $_GET['action'] ?? 'home';

if ($page == 'apply' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply_submit'])) {
    $cert_type = trim($_POST['certificate_type']);
    $details = trim($_POST['details']);
    
    // File upload handling
    if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
        $upload_dir = 'uploads/';
        $file_ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];

        if (in_array($file_ext, $allowed_ext)) {
            $file_name = $user_id . '_' . time() . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['document']['tmp_name'], $file_path)) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO applications (user_id, certificate_type, application_details, document_path)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$user_id, $cert_type, $details, $file_path]);
                    $message = "Application for $cert_type submitted successfully! Application ID: " . $pdo->lastInsertId();
                } catch (Exception $e) {
                    $error = "Database error during application submission. " . $e->getMessage();
                }
            } else {
                $error = "Error uploading file. Check upload directory permissions.";
            }
        } else {
            $error = "Invalid file type. Only PDF, JPG, and PNG are allowed.";
        }
    } else {
        $error = "Please upload a required document.";
    }
}

// --- Complaint Handling ---
if ($page == 'complain' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complain_submit'])) {
    $subject = trim($_POST['subject']);
    $description = trim($_POST['description']);
    
    if (empty($subject) || empty($description)) {
        $error = "Subject and description are required for the complaint.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO complaints (user_id, subject, description)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user_id, $subject, $description]);
            $message = "Complaint submitted successfully! It will be reviewed shortly.";
        } catch (Exception $e) {
            $error = "Database error during complaint submission. " . $e->getMessage();
        }
    }
}

// Fetch Applications for Status View
$applications = [];
if ($page == 'status' || $page == 'home') {
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE user_id = ? ORDER BY applied_at DESC");
    $stmt->execute([$user_id]);
    $applications = $stmt->fetchAll();
}

// Fetch Complaints for Tracking View
$complaints = [];
if ($page == 'complain' || $page == 'home') {
    $stmt = $pdo->prepare("SELECT * FROM complaints WHERE user_id = ? ORDER BY submitted_at DESC");
    $stmt->execute([$user_id]);
    $complaints = $stmt->fetchAll();
}

// --- HTML Structure (Header) ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Dashboard | e-Grampanchayat</title>
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
                <span class="text-3xl">🏛️</span>
                <span class="text-xl font-bold">Citizen Dashboard</span>
            </div>
            <div class="text-sm font-medium">
                <span>Welcome, <?= htmlspecialchars($user_info['full_name'] ?? $user_full_name) ?></span>
                <a href="logout.php" class="ml-4 bg-red-600 px-3 py-1 rounded-full hover:bg-red-700 transition">Logout</a>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <!-- Sidebar Navigation -->
            <nav class="lg:col-span-1 bg-white p-6 rounded-xl card-shadow h-min">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Navigation</h3>
                <ul class="space-y-2">
                    <li><a href="?action=home" class="block p-3 rounded-lg text-gray-700 hover:bg-blue-50 transition duration-150 <?= $page == 'home' ? 'bg-blue-100 font-bold text-gov-blue' : '' ?>">Dashboard Home</a></li>
                    <li><a href="?action=apply" class="block p-3 rounded-lg text-gray-700 hover:bg-blue-50 transition duration-150 <?= $page == 'apply' ? 'bg-blue-100 font-bold text-gov-blue' : '' ?>">Apply for Certificate</a></li>
                    <li><a href="?action=status" class="block p-3 rounded-lg text-gray-700 hover:bg-blue-50 transition duration-150 <?= $page == 'status' ? 'bg-blue-100 font-bold text-gov-blue' : '' ?>">Track Applications</a></li>
                    <li><a href="?action=complain" class="block p-3 rounded-lg text-gray-700 hover:bg-blue-50 transition duration-150 <?= $page == 'complain' ? 'bg-blue-100 font-bold text-gov-blue' : '' ?>">Submit Complaint</a></li>
                </ul>

                <h3 class="text-lg font-semibold text-gray-800 mt-6 mb-4 border-b pb-2">Profile</h3>
                <div class="text-sm text-gray-600 space-y-1">
                    <p><strong>Aadhaar:</strong> <?= htmlspecialchars($user_info['aadhaar_number'] ?? 'N/A') ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user_info['email'] ?? 'N/A') ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($user_info['phone_number'] ?? 'N/A') ?></p>
                </div>
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
                // Start buffering the application list content for reuse in 'home' and 'status' cases
                ob_start();
                ?>
                <div class="space-y-4">
                    <?php if (empty($applications)): ?>
                        <p class="text-gray-500 p-4 bg-white rounded-lg card-shadow">No applications submitted yet. <a href="?action=apply" class="text-blue-600 hover:underline">Apply now.</a></p>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <?php
                                $status_color = match($app['status']) {
                                    'Approved' => 'bg-green-500',
                                    'Rejected' => 'bg-red-500',
                                    'In Review' => 'bg-yellow-500',
                                    default => 'bg-blue-500',
                                };
                            ?>
                            <div class="p-4 bg-white rounded-xl card-shadow border-l-4 <?= str_replace('bg-', 'border-', $status_color) ?>">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-semibold text-lg text-gray-900"><?= htmlspecialchars($app['certificate_type']) ?> (ID: <?= $app['application_id'] ?>)</h3>
                                    <span class="px-3 py-1 text-xs font-medium text-white rounded-full <?= $status_color ?>"><?= htmlspecialchars($app['status']) ?></span>
                                </div>
                                <p class="text-sm text-gray-600 mt-2">Applied on: <?= date('M d, Y', strtotime($app['applied_at'])) ?></p>
                                <p class="text-sm text-gray-600">Details: <?= substr(htmlspecialchars($app['application_details']), 0, 50) ?>...</p>

                                <?php if ($app['status'] == 'Approved' && !empty($app['certificate_download_path'])): ?>
                                    <a href="<?= htmlspecialchars($app['certificate_download_path']) ?>" download class="mt-3 inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
                                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H5a2 2 0 01-2-2v-5a2 2 0 012-2h14a2 2 0 012 2v5a2 2 0 01-2 2z"></path></svg>
                                        Download Certificate
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (!empty($app['remark'])): ?>
                                    <div class="mt-3 p-2 text-xs bg-gray-100 rounded">
                                        **Officer Remark:** <?= htmlspecialchars($app['remark']) ?>
                                    </div>
                                <?php endif; ?>

                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php 
                $application_list_content = ob_get_clean(); // Get the content and clear the buffer
                
                switch ($page) {
                    case 'home':
                        // --- Dashboard Home: Summaries ---
                        ?>
                        <h1 class="text-3xl font-bold text-gray-900 mb-6">Welcome Back, <?= htmlspecialchars($user_info['full_name'] ?? $user_full_name) ?>!</h1>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                            <div class="bg-white p-6 rounded-xl card-shadow border-l-4 border-blue-500">
                                <p class="text-gray-500">Total Applications</p>
                                <p class="text-3xl font-bold text-gray-900"><?= count($applications) ?></p>
                            </div>
                            <div class="bg-white p-6 rounded-xl card-shadow border-l-4 border-green-500">
                                <p class="text-gray-500">Approved Certificates</p>
                                <p class="text-3xl font-bold text-gray-900"><?= count(array_filter($applications, fn($app) => $app['status'] == 'Approved')) ?></p>
                            </div>
                            <div class="bg-white p-6 rounded-xl card-shadow border-l-4 border-yellow-500">
                                <p class="text-gray-500">Pending Complaints</p>
                                <p class="text-3xl font-bold text-gray-900"><?= count(array_filter($complaints, fn($comp) => $comp['status'] != 'Resolved' && $comp['status'] != 'Closed')) ?></p>
                            </div>
                        </div>

                        <h2 class="text-2xl font-semibold text-gray-800 mb-4 border-b pb-2">Recent Application Status</h2>
                        <?= $application_list_content ?>

                        <?php
                        break;

                    case 'apply':
                        // --- Apply for Certificate Form ---
                        ?>
                        <div class="bg-white p-8 rounded-xl card-shadow">
                            <h2 class="text-2xl font-bold text-gray-900 mb-6">Apply for New Certificate</h2>
                            <form method="POST" action="?action=apply" enctype="multipart/form-data" class="space-y-4">
                                <input type="hidden" name="apply_submit" value="1">
                                
                                <div>
                                    <label for="certificate_type" class="block text-sm font-medium text-gray-700">Certificate Type</label>
                                    <select name="certificate_type" id="certificate_type" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">-- Select Type --</option>
                                        <option value="Birth Certificate">Birth Certificate</option>
                                        <option value="Death Certificate">Death Certificate</option>
                                        <option value="Residence Certificate">Residence Certificate</option>
                                        <option value="Income Certificate">Income Certificate</option>
                                        <option value="BPL Certificate">BPL/EWS Certificate</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="details" class="block text-sm font-medium text-gray-700">Application Details (Reason, Specifics)</label>
                                    <textarea name="details" id="details" rows="4" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Provide necessary details for this application..."></textarea>
                                </div>
                                
                                <div>
                                    <label for="document" class="block text-sm font-medium text-gray-700">Upload Required Supporting Document (PDF/JPG/PNG)</label>
                                    <input type="file" name="document" id="document" required class="mt-1 w-full border border-gray-300 rounded-lg p-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>

                               <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-black font-semibold py-2.5 px-6 rounded-lg transition duration-300">Submit Application</button>

                            </form>
                        </div>
                        <?php
                        break;

                    case 'status':
                        // --- Application Status Tracking ---
                        ?>
                        <h2 class="text-2xl font-semibold text-gray-800 mb-4 border-b pb-2">Track Application Status</h2>
                        <?= $application_list_content ?>
                        <?php
                        break;

                    case 'complain':
                        // --- Submit/Track Complaint ---
                        ?>
                        <div class="bg-white p-8 rounded-xl card-shadow mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 mb-6">Submit New Grievance/Complaint</h2>
                            <form method="POST" action="?action=complain" class="space-y-4">
                                <input type="hidden" name="complain_submit" value="1">
                                
                                <div>
                                    <label for="subject" class="block text-sm font-medium text-gray-700">Subject/Title</label>
                                    <input type="text" name="subject" id="subject" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                
                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700">Detailed Description of Complaint</label>
                                    <textarea name="description" id="description" rows="5" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Describe the issue, location, and date if applicable..."></textarea>
                                </div>

                                <button type="submit" class="bg-yellow-600 text-white font-semibold py-2.5 px-6 rounded-lg transition duration-300 hover:bg-yellow-700">Submit Complaint</button>
                            </form>
                        </div>
                        
                        <h2 class="text-2xl font-semibold text-gray-800 mb-4 border-b pb-2">Your Submitted Complaints</h2>
                        <div class="space-y-4">
                            <?php if (empty($complaints)): ?>
                                <p class="text-gray-500 p-4 bg-white rounded-lg card-shadow">No complaints submitted yet.</p>
                            <?php else: ?>
                                <?php foreach ($complaints as $complaint): ?>
                                    <?php
                                        $status_class = match($complaint['status']) {
                                            'Resolved' => 'bg-green-100 text-green-700 border-green-500',
                                            'Under Investigation' => 'bg-yellow-100 text-yellow-700 border-yellow-500',
                                            default => 'bg-blue-100 text-blue-700 border-blue-500',
                                        };
                                    ?>
                                    <div class="p-4 bg-white rounded-xl card-shadow border-l-4 <?= $status_class ?>">
                                        <div class="flex justify-between items-center">
                                            <h3 class="font-semibold text-lg text-gray-900"><?= htmlspecialchars($complaint['subject']) ?> (ID: <?= $complaint['complaint_id'] ?>)</h3>
                                            <span class="px-3 py-1 text-xs font-medium rounded-full <?= $status_class ?>"><?= htmlspecialchars($complaint['status']) ?></span>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-2"><?= nl2br(htmlspecialchars($complaint['description'])) ?></p>
                                        <p class="text-xs text-gray-400 mt-2">Submitted: <?= date('M d, Y', strtotime($complaint['submitted_at'])) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <?php
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