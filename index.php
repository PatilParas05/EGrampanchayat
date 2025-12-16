<?php
/**
 * e-Grampanchayat Main Index and Public Router
 * Handles public pages, login/register logic, and redirects.
 */
require_once 'includes/db_connect.php';

// --- Functions and Logic ---

function authenticate_user($pdo, $email, $password) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

function register_user($pdo, $data) {
    // Basic validation
    if (empty($data['email']) || empty($data['password']) || empty($data['aadhaar']) || empty($data['name'])) {
        return "All fields are required.";
    }

    $password_hash = password_hash($data['password'], PASSWORD_BCRYPT);
    $role = 'citizen';

    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (full_name, aadhaar_number, phone_number, email, password_hash, role)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'],
            $data['aadhaar'],
            $data['phone'],
            $data['email'],
            $password_hash,
            $role
        ]);
        return true;
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            return "Email or Aadhaar number already registered.";
        }
        // General error
        return "An error occurred during registration: " . $e->getMessage();
    }
}

// --- Request Handling ---

$page = $_GET['page'] ?? 'home';
$error = '';
$message = '';

if (isset($_SESSION['user_id'])) {
    // Redirect authenticated users to their respective dashboards
    if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff') {
        header('Location: admin_dashboard.php');
        exit;
    } else {
        header('Location: dashboard.php');
        exit;
    }
}

// Handle Login Form Submission
if ($page == 'login' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_submit'])) {
    if (authenticate_user($pdo, $_POST['email'], $_POST['password'])) {
        // Authentication successful, role-based redirect handled above
        // This is a redundant check but ensures immediate redirection after login
        if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff') {
            header('Location: admin_dashboard.php');
            exit;
        } else {
            header('Location: dashboard.php');
            exit;
        }
    } else {
        $error = "Invalid email or password.";
    }
}

// Handle Registration Form Submission
if ($page == 'register' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_submit'])) {
    $result = register_user($pdo, $_POST);
    if ($result === true) {
        $message = "Registration successful! Please login.";
        $page = 'login'; // Switch to login page after success
    } else {
        $error = $result;
    }
}

// --- Public Data Fetching ---
$announcements = [];
if ($page == 'home') {
    $stmt = $pdo->query("SELECT * FROM notifications WHERE is_active = 1 ORDER BY published_at DESC LIMIT 5");
    $announcements = $stmt->fetchAll();
}
$schemes = [];
if ($page == 'schemes') {
    $stmt = $pdo->query("SELECT * FROM schemes ORDER BY published_at DESC");
    $schemes = $stmt->fetchAll();
}

// --- HTML Structure (Header) ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>e-Grampanchayat | Official Portal</title>
    <!-- Tailwind CSS CDN for modern, responsive styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; min-height: 100vh; display: flex; flex-direction: column; } /* Added flex for sticky footer */
        .gov-blue { background-color: #0d47a1; } /* Darker, official blue */
        .gov-light-blue { color: #1e88e5; }
        .gov-link:hover { text-decoration: underline; color: #1565c0; }
        .card-shadow { box-shadow: 0 6px 15px -3px rgba(0, 0, 0, 0.1), 0 3px 6px -4px rgba(0, 0, 0, 0.08); }
        .banner-animation { animation: slidein 1s ease-out; }
        
        /* Keyframes for simple banner animation */
        @keyframes slidein { 
            from { opacity: 0; transform: translateY(-10px); } 
            to { opacity: 1; transform: translateY(0); } 
        }

        /* --- Hover/Focus Effects for Buttons (Requested Feature) --- */

        /* Target the submit button when any child input within its parent form is focused/filled */
        .form-container:focus-within .submit-button,
        .form-container:hover .submit-button {
            transform: scale(1.02);
            /* Updated shadow for high-contrast button */
            box-shadow: 0 10px 15px -3px rgba(251, 191, 36, 0.5), 0 4px 6px -4px rgba(251, 191, 36, 0.3);
            transition: all 0.3s ease;
        }

        /* Initial state of the button (less prominent) */
        .submit-button {
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

    <!-- Header & Navigation -->
    <header class="gov-blue text-white shadow-xl sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <!-- SVG Emblem Placeholder (Official Look) -->
                <svg class="w-8 h-8 text-yellow-300" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-1 16H8v-2h3v2zm0-4H8v-2h3v2zm-3-4h3V9H8v2zm7 4h-3v-2h3v2zm0-4h-3v-2h3v2z"/>
                </svg>
                <a href="?page=home" class="text-2xl font-extrabold tracking-wide">e-Grampanchayat Portal</a>
            </div>
            <nav class="hidden md:flex space-x-6 text-sm font-medium">
                <a href="?page=home" class="gov-link px-3 py-2 rounded-lg transition duration-150 hover:bg-white hover:text-gov-blue">Home</a>
                <a href="?page=schemes" class="gov-link px-3 py-2 rounded-lg transition duration-150 hover:bg-white hover:text-gov-blue">Schemes</a>
                <a href="?page=profile" class="gov-link px-3 py-2 rounded-lg transition duration-150 hover:bg-white hover:text-gov-blue">Panchayat Profile</a>
            </nav>
            <div class="flex items-center space-x-2">
                <!-- LOGIN BUTTON -->
                <a href="?page=login" class="bg-blue-600 text-white px-4 py-2 rounded-full font-semibold transition duration-300 hover:bg-blue-700 hover:shadow-md">Login</a>
                
                <!-- REGISTER BUTTON: High-contrast Saffron/Yellow -->
                <a href="?page=register" class="bg-yellow-400 text-gray-900 px-4 py-2 rounded-full font-semibold transition duration-300 hover:bg-yellow-500 hidden sm:inline-block hover:shadow-lg">Register</a>
            </div>
        </div>
    </header>

    <!-- Main Content Area (uses flex-grow to push footer down) -->
    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6 font-medium" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6 font-medium" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <?php
        // --- Page Content Routing ---

        switch ($page) {
            case 'home':
                // --- Home Page Content ---
                ?>
                <div class="text-center mb-12 banner-animation bg-white p-8 rounded-xl card-shadow border-t-4 border-gov-blue">
                    <h1 class="text-4xl font-extrabold text-gray-900 mb-2">Digital Governance for Our Village</h1>
                    <p class="text-xl text-gray-600">Welcome to the Official e-Grampanchayat Portal</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Announcement/News Section -->
                    <div class="md:col-span-2 bg-white p-6 rounded-xl card-shadow border-t-4 border-blue-500">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2 flex items-center gov-light-blue">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882l-7 3.5v5.364l7 3.5 7-3.5v-5.364l-7-3.5zM12 21V12.5M17.5 7.5L12 10.5L6.5 7.5M12 3.5V6.5"></path></svg>
                            Latest Announcements & Public Notices
                        </h2>
                        <?php if (empty($announcements)): ?>
                            <p class="text-gray-500">No recent announcements available.</p>
                        <?php else: ?>
                            <ul class="space-y-4">
                                <?php foreach ($announcements as $announcement): ?>
                                    <li class="p-4 border-l-4 <?= $announcement['type'] == 'News' ? 'border-blue-500' : 'border-red-500' ?> bg-gray-50 rounded-md transition duration-300 hover:bg-gray-100">
                                        <div class="font-semibold text-lg text-gray-900"><?= htmlspecialchars($announcement['title']) ?></div>
                                        <p class="text-gray-600 text-sm mt-1"><?= substr(htmlspecialchars($announcement['content']), 0, 100) ?>...</p>
                                        <span class="text-xs text-gray-400 block mt-1">Published: <?= date('M d, Y', strtotime($announcement['published_at'])) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Links/Services Section -->
                    <div class="bg-white p-6 rounded-xl card-shadow border-t-4 border-green-500">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2 flex items-center text-green-600">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Citizen Services
                        </h2>
                        <ul class="space-y-3">
                            <li><a href="?page=login" class="block bg-blue-50 hover:bg-blue-200 text-blue-800 font-medium py-3 px-4 rounded-lg transition duration-150 shadow-sm">Apply for Certificate</a></li>
                            <li><a href="?page=login" class="block bg-blue-50 hover:bg-blue-200 text-blue-800 font-medium py-3 px-4 rounded-lg transition duration-150 shadow-sm">Track Application Status</a></li>
                            <li><a href="?page=login" class="block bg-blue-50 hover:bg-blue-200 text-blue-800 font-medium py-3 px-4 rounded-lg transition duration-150 shadow-sm">Submit Grievance</a></li>
                            <li><a href="?page=schemes" class="block bg-blue-50 hover:bg-blue-200 text-blue-800 font-medium py-3 px-4 rounded-lg transition duration-150 shadow-sm">View Government Schemes</a></li>
                            <li><a href="?page=profile" class="block bg-blue-50 hover:bg-blue-200 text-blue-800 font-medium py-3 px-4 rounded-lg transition duration-150 shadow-sm">Panchayat Profile & Contacts</a></li>
                        </ul>
                    </div>
                </div>

                <?php
                break;

            case 'login':
                // --- Login Page Content ---
                ?>
                <div class="max-w-md mx-auto bg-white p-8 rounded-xl card-shadow form-container">
                    <h2 class="text-3xl font-extrabold text-center text-gray-900 mb-6 border-b pb-3">Secure Citizen Login</h2>
                    <form method="POST" action="?page=login">
                        <input type="hidden" name="login_submit" value="1">
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <input type="email" name="email" id="email" required class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:ring-blue-600 focus:ring-1" placeholder="your.email@example.com">
                        </div>
                        <div class="mb-6">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <input type="password" name="password" id="password" required class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:ring-blue-600 focus:ring-1" placeholder="••••••••">
                        </div>
                        <!-- BUTTON: Saffron/Yellow -->
                        <button type="submit" class="w-full bg-yellow-400 text-gray-900 font-bold py-3 rounded-lg transition duration-300 hover:bg-yellow-500 submit-button">Sign In to Your Account</button>
                    </form>
                    <p class="mt-6 text-center text-sm text-gray-600">
                        Don't have an account? <a href="?page=register" class="text-blue-600 font-semibold hover:underline">Register Here</a>
                    </p>
                </div>
                <?php
                break;

            case 'register':
                // --- Registration Page Content ---
                ?>
                <div class="max-w-lg mx-auto bg-white p-8 rounded-xl card-shadow form-container">
                    <h2 class="text-3xl font-extrabold text-center text-gray-900 mb-6 border-b pb-3">New Citizen Registration</h2>
                    <form method="POST" action="?page=register" class="space-y-4">
                        <input type="hidden" name="register_submit" value="1">

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                            <!-- Added Placeholder -->
                            <input type="text" name="name" id="name" required class="mt-1 w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:ring-blue-600 focus:ring-1" placeholder="e.g., Jane D'Souza">
                        </div>
                        <div>
                            <label for="aadhaar" class="block text-sm font-medium text-gray-700">Aadhaar Number (12 Digits)</label>
                            <!-- Added Placeholder -->
                            <input type="text" name="aadhaar" id="aadhaar" required maxlength="12" pattern="\d{12}" title="Aadhaar must be 12 digits" class="mt-1 w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:ring-blue-600 focus:ring-1" placeholder="XXXX XXXX XXXX">
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                            <!-- Added Placeholder -->
                            <input type="tel" name="phone" id="phone" required class="mt-1 w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:ring-blue-600 focus:ring-1" placeholder="e.g., 9876543210">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                            <input type="email" name="email" id="email" required class="mt-1 w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:ring-blue-600 focus:ring-1" placeholder="citizen@example.com">
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" name="password" id="password" required class="mt-1 w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:ring-blue-600 focus:ring-1" placeholder="••••••••">
                        </div>
                        <!-- BUTTON: Changed to Saffron/Yellow -->
                        <button type="submit" class="w-full bg-yellow-400 text-gray-900 font-bold py-3 rounded-lg transition duration-300 hover:bg-yellow-500 submit-button">Register Account</button>
                    </form>
                    <p class="mt-6 text-center text-sm text-gray-600">
                        Already registered? <a href="?page=login" class="text-blue-600 font-semibold hover:underline">Login Here</a>
                    </p>
                </div>
                <?php
                break;

            case 'schemes':
                // --- Government Schemes Page Content ---
                ?>
                <h1 class="text-3xl font-extrabold text-gray-900 mb-6 border-b-4 border-gov-blue pb-3">Government Schemes & Programs</h1>

                <div class="space-y-6">
                    <?php if (empty($schemes)): ?>
                        <div class="bg-yellow-50 p-6 rounded-xl border border-yellow-200 shadow-sm">
                            <p class="text-yellow-700 font-medium">No schemes have been published yet by the Admin.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($schemes as $scheme): ?>
                            <div class="bg-white p-6 rounded-xl card-shadow border-l-8 border-gov-blue hover:shadow-2xl transition duration-300">
                                <h2 class="text-2xl font-bold text-gov-blue mb-2"><?= htmlspecialchars($scheme['name']) ?></h2>
                                <p class="text-gray-600 mb-4"><?= nl2br(htmlspecialchars($scheme['description'])) ?></p>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mt-4">
                                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <h3 class="font-bold text-gray-800 mb-2 border-b pb-1">Eligibility Criteria</h3>
                                        <ul class="list-disc list-inside text-gray-700 space-y-1">
                                            <?php foreach (explode("\n", htmlspecialchars($scheme['eligibility'])) as $point): ?>
                                                <li><?= trim($point) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <h3 class="font-bold text-gray-800 mb-2 border-b pb-1">How to Apply</h3>
                                        <p class="text-gray-700"><?= nl2br(htmlspecialchars($scheme['how_to_apply'])) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php
                break;

            case 'profile':
                // --- Panchayat Profile Page Content ---
                ?>
                <h1 class="text-3xl font-extrabold text-gray-900 mb-6 border-b-4 border-gov-blue pb-3">Panchayat Profile & Directory</h1>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Village Overview -->
                    <div class="lg:col-span-2 bg-white p-6 rounded-xl card-shadow border-t-4 border-gov-blue">
                        <h2 class="text-2xl font-bold text-gov-blue mb-4">Village Overview: [Village Name Placeholder]</h2>
                        <p class="text-gray-700 mb-4">
                            The **[Village Name] Grampanchayat** is committed to providing transparent and efficient governance.
                            Established in **[Year]**, it serves as the grassroots administrative body, managing local development, public utilities, and civil documentation for its residents.
                            Our mission is to achieve sustainable development and improve the quality of life for all citizens.
                        </p>
                        <h3 class="text-xl font-semibold mt-6 mb-3 border-t pt-3">Elected Members & Staff</h3>
                        <ul class="space-y-3">
                            <li class="p-3 bg-blue-50 rounded-lg border-l-4 border-blue-500"><strong>Sarpanch (Head):</strong> Smt. Kavita Patil (Contact: 9876543210)</li>
                            <li class="p-3 bg-blue-50 rounded-lg border-l-4 border-blue-500"><strong>Up-Sarpanch:</strong> Shri. Rajesh Verma</li>
                            <li class="p-3 bg-green-50 rounded-lg border-l-4 border-green-500"><strong>Gram Sevak (Staff In-Charge):</strong> Shri. Sunil Kadam (Email: staff@gp.com)</li>
                        </ul>
                    </div>

                    <!-- Contact Details -->
                    <div class="bg-white p-6 rounded-xl card-shadow border-t-4 border-red-500">
                        <h2 class="text-2xl font-bold text-gov-blue mb-4">Official Contact Information</h2>
                        <address class="text-gray-700 space-y-4">
                            <p><strong>Address:</strong><br>
                            Grampanchayat Office, Main Road,<br>
                            [Village Name], [District], [State] - [Pincode]</p>

                            <p><strong>Official Email:</strong><br>
                            <a href="mailto:info@grampanchayat.gov.in" class="text-blue-600 hover:underline">info@grampanchayat.gov.in</a></p>

                            <p><strong>Phone (Office):</strong><br>
                            020-XXXXXXX</p>

                            <p class="mt-4 border-t pt-3 text-sm text-gray-500">
                                Office Hours: Monday - Friday, 10:00 AM to 5:00 PM
                            </p>
                        </address>
                    </div>

                    <!-- Asset Directory Mock -->
                    <div class="lg:col-span-3 bg-white p-6 rounded-xl card-shadow border-t-4 border-yellow-500">
                        <h3 class="text-xl font-semibold mb-3 border-b pb-2">Village Asset Directory (Mock)</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                            <div class="p-4 bg-gray-50 rounded-lg border hover:bg-yellow-50 transition duration-150">Primary School A</div>
                            <div class="p-4 bg-gray-50 rounded-lg border hover:bg-yellow-50 transition duration-150">Community Water Tank</div>
                            <div class="p-4 bg-gray-50 rounded-lg border hover:bg-yellow-50 transition duration-150">Public Health Center</div>
                            <div class="p-4 bg-gray-50 rounded-lg border hover:bg-yellow-50 transition duration-150">Village Library</div>
                        </div>
                    </div>
                </div>
                <?php
                break;

            default:
                // Fallback to Home
                header('Location: ?page=home');
                exit;
        }
        ?>

    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-12 w-full">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-sm">
            &copy; <?= date('Y') ?> e-Grampanchayat Portal. All rights reserved. | <a href="#" class="hover:underline text-blue-300">Terms of Use</a>
        </div>
    </footer>

</body>
</html>