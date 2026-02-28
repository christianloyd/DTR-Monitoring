<?php
// ============================================================
// api/auth.php  — Authentication Endpoints
// Actions: login | register | admin_login | logout | session
// ============================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
startSession();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // --------------------------------------------------------
    case 'login':
        $identifier = trim($_POST['email'] ?? '');
        $password   = $_POST['password'] ?? '';

        if (!$identifier || !$password) {
            jsonResponse(['ok' => false, 'message' => 'Email/username and password are required.'], 400);
        }

        $pdo  = getDB();
        // Match by email OR username
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1');
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            jsonResponse(['ok' => false, 'message' => 'Invalid email/username or password.'], 401);
        }

        // Public login is for OJT trainees only — admins use /admin/login.php
        if ($user['role'] === 'admin') {
            jsonResponse(['ok' => false, 'message' => 'Admin accounts must log in via the Admin Portal.'], 403);
        }

        $_SESSION['user'] = [
            'id'                  => $user['id'],
            'name'                => $user['name'],
            'username'            => $user['username'],
            'email'               => $user['email'],
            'role'                => $user['role'],
            'required_hours'      => $user['required_hours'],
            'training_supervisor' => $user['training_supervisor'] ?? null,
        ];

        jsonResponse(['ok' => true, 'user' => $_SESSION['user']]);
        break;

    // --------------------------------------------------------
    case 'admin_login':
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            jsonResponse(['ok' => false, 'message' => 'Email and password are required.'], 400);
        }

        $pdo  = getDB();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND role = ? LIMIT 1');
        $stmt->execute([$email, 'admin']);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            jsonResponse(['ok' => false, 'message' => 'Invalid credentials or insufficient privileges.'], 401);
        }

        $_SESSION['user'] = [
            'id'             => $user['id'],
            'name'           => $user['name'],
            'username'       => $user['username'],
            'email'          => $user['email'],
            'role'           => $user['role'],
            'required_hours' => $user['required_hours'],
        ];

        jsonResponse(['ok' => true, 'user' => $_SESSION['user']]);
        break;

    // --------------------------------------------------------
    case 'admin_register':
        $name     = trim($_POST['name']     ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';

        if (!$name || !$username || !$email || !$password) {
            jsonResponse(['ok' => false, 'message' => 'All fields are required.'], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['ok' => false, 'message' => 'Invalid email address.'], 400);
        }
        if (strlen($password) < 6) {
            jsonResponse(['ok' => false, 'message' => 'Password must be at least 6 characters.'], 400);
        }
        if (!preg_match('/^[a-zA-Z0-9_]{3,80}$/', $username)) {
            jsonResponse(['ok' => false, 'message' => 'Username must be 3-80 chars (letters, numbers, underscores).'], 400);
        }

        $pdo = getDB();

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            jsonResponse(['ok' => false, 'message' => 'Email already registered.'], 409);
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            jsonResponse(['ok' => false, 'message' => 'Username already taken.'], 409);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (name, username, email, password, role, required_hours) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $username, $email, $hash, 'admin', 486]);

        jsonResponse(['ok' => true, 'message' => 'Admin account created successfully.']);
        break;

    // --------------------------------------------------------
    case 'register':
        $name          = trim($_POST['name']           ?? '');
        $username      = trim($_POST['username']       ?? '');
        $email         = trim($_POST['email']          ?? '');
        $password      = $_POST['password']            ?? '';
        $requiredHours = (int)($_POST['required_hours'] ?? 486);

        // Public registration is always OJT — role is never accepted from input
        $role = 'ojt';

        if (!$name || !$username || !$email || !$password) {
            jsonResponse(['ok' => false, 'message' => 'All fields are required.'], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['ok' => false, 'message' => 'Invalid email address.'], 400);
        }
        if (strlen($password) < 6) {
            jsonResponse(['ok' => false, 'message' => 'Password must be at least 6 characters.'], 400);
        }
        if (!preg_match('/^[a-zA-Z0-9_]{3,80}$/', $username)) {
            jsonResponse(['ok' => false, 'message' => 'Username must be 3–80 characters (letters, numbers, underscores only).'], 400);
        }
        if ($requiredHours < 1) {
            $requiredHours = 486;
        }

        $pdo = getDB();

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            jsonResponse(['ok' => false, 'message' => 'Email already registered.'], 409);
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            jsonResponse(['ok' => false, 'message' => 'Username already taken. Please choose another.'], 409);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (name, username, email, password, role, required_hours)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $username, $email, $hash, $role, $requiredHours]);

        jsonResponse(['ok' => true, 'message' => 'Account created successfully. Please log in.']);
        break;

    // --------------------------------------------------------
    case 'update_settings':
        $currentUser = getCurrentUser();
        if (!$currentUser) {
            jsonResponse(['ok' => false, 'message' => 'Not logged in.'], 401);
        }
        $supervisor = trim($_POST['training_supervisor'] ?? '');
        if (!$supervisor) {
            jsonResponse(['ok' => false, 'message' => 'Training Supervisor name is required.'], 400);
        }
        $pdo = getDB();
        $stmt = $pdo->prepare('UPDATE users SET training_supervisor = ? WHERE id = ? LIMIT 1');
        $stmt->execute([$supervisor, $currentUser['id']]);
        // Refresh session
        $_SESSION['user']['training_supervisor'] = $supervisor;
        jsonResponse(['ok' => true, 'message' => 'Settings saved.', 'training_supervisor' => $supervisor]);
        break;

    // --------------------------------------------------------
    case 'logout':
        session_unset();
        session_destroy();
        jsonResponse(['ok' => true, 'message' => 'Logged out.']);
        break;

    // --------------------------------------------------------
    case 'session':
        $user = getCurrentUser();
        if ($user) {
            jsonResponse(['ok' => true, 'loggedIn' => true, 'user' => $user]);
        } else {
            jsonResponse(['ok' => true, 'loggedIn' => false]);
        }
        break;

    // --------------------------------------------------------
    default:
        jsonResponse(['ok' => false, 'message' => 'Unknown action.'], 400);
}
