<?php
// ============================================================
// includes/auth.php  — Session Helpers
// ============================================================

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function getCurrentUser(): ?array {
    startSession();
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool {
    return getCurrentUser() !== null;
}

function isAdmin(): bool {
    $user = getCurrentUser();
    return $user !== null && $user['role'] === 'admin';
}

/**
 * Terminate with a JSON error if not logged in.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'message' => 'Unauthorized. Please log in.']);
        exit;
    }
}

/**
 * Terminate with a JSON error if not admin.
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'Forbidden. Admin access required.']);
        exit;
    }
}

/**
 * Emit JSON and exit.
 */
function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
