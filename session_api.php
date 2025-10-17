<?php
// Lightweight session-based authentication and user management API
// Returns JSON responses for frontend and automated tests

declare(strict_types=1);

// Ensure errors are not leaked to clients
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Start PHP session
if (session_status() === PHP_SESSION_NONE) {
    // Session cookie params: secure defaults; path "/" so it works for both / and /subnets base
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Basic JSON output helper
function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

// Read JSON body if provided
function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// DB connection via existing project bootstrap (same pattern as api.php)
try {
    require_once __DIR__ . '/db_init.php';
    // Instantiate database using environment variables, silent mode
    $database = new SubnetDatabase(null, true);
    $pdo = $database->getConnection();
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('Database connection not available');
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => 'DB init failed: ' . $e->getMessage()], 500);
    exit;
}

// Auth helpers
function is_authenticated(): bool {
    return !empty($_SESSION['username']);
}

function current_user(): ?string {
    return $_SESSION['username'] ?? null;
}

function is_admin_user(): bool {
    // Minimal role model: username === 'admin' is admin
    return !empty($_SESSION['is_admin']);
}

function require_auth(): void {
    if (!is_authenticated()) {
        json_response(['success' => false, 'message' => 'Authentication required'], 401);
        exit;
    }
}

function require_admin(): void {
    if (!is_authenticated() || !is_admin_user()) {
        json_response(['success' => false, 'message' => 'Admin privileges required'], 403);
        exit;
    }
}

// DB helpers
function fetch_user(PDO $pdo, string $username): ?array {
    $stmt = $pdo->prepare('SELECT username, password_hash, created_at FROM users WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function create_user(PDO $pdo, string $username, string $password): void {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, created_at) VALUES (:u, :h, NOW())');
    $stmt->execute([':u' => $username, ':h' => $hash]);
}

function delete_user(PDO $pdo, string $username): int {
    $stmt = $pdo->prepare('DELETE FROM users WHERE username = :u');
    $stmt->execute([':u' => $username]);
    return $stmt->rowCount();
}

function update_user_password(PDO $pdo, string $username, string $newPassword): void {
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password_hash = :h WHERE username = :u');
    $stmt->execute([':h' => $hash, ':u' => $username]);
}

// Actions
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'me': {
            if (!is_authenticated()) {
                json_response(['success' => false, 'message' => 'Not authenticated']);
            } else {
                json_response([
                    'success' => true,
                    'username' => current_user(),
                    'is_admin' => is_admin_user(),
                ]);
            }
            break;
        }

        case 'login': {
            $body = read_json_body();
            $username = trim((string)($body['username'] ?? ''));
            $password = (string)($body['password'] ?? '');
            if ($username === '' || $password === '') {
                json_response(['success' => false, 'message' => 'Username and password required'], 400);
                break;
            }

            $user = fetch_user($pdo, $username);
            if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
                json_response(['success' => false, 'message' => 'Invalid credentials'], 401);
                break;
            }

            // Auth OK — initialize session
            $_SESSION['username'] = $username;
            $_SESSION['is_admin'] = ($username === 'admin');
            json_response([
                'success' => true,
                'username' => $username,
                'is_admin' => $_SESSION['is_admin'] ? true : false,
            ]);
            break;
        }

        case 'logout': {
            // Clear session
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            json_response(['success' => true]);
            break;
        }

        case 'list_users': {
            require_admin();
            $stmt = $pdo->query('SELECT username, created_at FROM users ORDER BY username ASC');
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            json_response(['success' => true, 'users' => $users]);
            break;
        }

        case 'add_user': {
            require_admin();
            $body = read_json_body();
            $username = trim((string)($body['username'] ?? ''));
            $password = (string)($body['password'] ?? '');
            if ($username === '' || $password === '') {
                json_response(['success' => false, 'message' => 'Username and password required'], 400);
                break;
            }
            if (fetch_user($pdo, $username)) {
                json_response(['success' => false, 'message' => 'User already exists']);
                break;
            }
            create_user($pdo, $username, $password);
            json_response(['success' => true]);
            break;
        }

        case 'delete_user': {
            require_admin();
            $body = read_json_body();
            $username = trim((string)($body['username'] ?? ''));
            if ($username === '') {
                json_response(['success' => false, 'message' => 'Username required'], 400);
                break;
            }
            if ($username === 'admin') {
                json_response(['success' => false, 'message' => 'Cannot delete admin user'], 400);
                break;
            }
            delete_user($pdo, $username);
            json_response(['success' => true]);
            break;
        }

        case 'change_password': {
            require_auth();
            $body = read_json_body();
            $old = (string)($body['old_password'] ?? '');
            $new = (string)($body['new_password'] ?? '');
            if ($old === '' || $new === '') {
                json_response(['success' => false, 'message' => 'Old and new password required'], 400);
                break;
            }
            $username = current_user();
            $user = fetch_user($pdo, $username);
            if (!$user || !password_verify($old, $user['password_hash'])) {
                json_response(['success' => false, 'message' => 'Old password incorrect'], 400);
                break;
            }
            update_user_password($pdo, $username, $new);
            json_response(['success' => true]);
            break;
        }

        default: {
            json_response(['success' => false, 'message' => 'Unknown action'], 400);
        }
    }
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => 'Server error', 'detail' => $e->getMessage()], 500);
}
?>
