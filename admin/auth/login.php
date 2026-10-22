<?php
session_start();
require_once '../../backend/config/database.php';

// Jika sudah login, langsung alihkan ke dashboard produk
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: ../products/index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $pdo = getDbConnection();
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :u LIMIT 1");
        $stmt->execute(['u' => $username]);
        $user = $stmt->fetch();

        if ($user) {
            $isPasswordValid = false;

            // 1. Pengecekan password_verify bawaan PHP
            if (password_verify($password, $user['password_hash'])) {
                $isPasswordValid = true;
            } 
            // 2. Bypass & auto-update hash jika input 'admin123'
            elseif ($password === 'admin123') {
                $isPasswordValid = true;
                
                $newHash = password_hash('admin123', PASSWORD_BCRYPT);
                $stmtFix = $pdo->prepare("UPDATE users SET password_hash = :ph WHERE user_id = :uid");
                $stmtFix->execute(['ph' => $newHash, 'uid' => $user['user_id']]);
            }

            if ($isPasswordValid) {
                if ($user['role'] !== 'admin') {
                    $error = "Akses ditolak. Akun ini bukan bertipe admin!";
                } elseif ((int)$user['is_active'] !== 1) {
                    $error = "Akun admin ini sedang dinonaktifkan!";
                } else {
                    // Login Sukses
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id']        = $user['user_id'];
                    $_SESSION['admin_name']      = $user['full_name'];
                    
                    header("Location: ../products/index.php");
                    exit;
                }
            } else {
                $error = "Password yang kamu masukkan salah!";
            }
        } else {
            $error = "Email admin tidak ditemukan!";
        }
    } else {
        $error = 'Harap isi Email dan Password!';
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - IDVISION Admin</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        :root {
            --font-family-sans: 'Inter', system-ui, -apple-system, sans-serif;
            --amber-500: #f59e0b;
            --amber-600: #d97706;
            --amber-400: #fbbf24;
            --bg-deep-black: #030712;
            --card-slate-dark: #090d16;
            --border-slate-dark: #1f2937;
        }

        body {
            font-family: var(--font-family-sans);
            background-color: var(--bg-deep-black);
            color: #f3f4f6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            letter-spacing: -0.01em;
        }

        .login-card {
            background-color: var(--card-slate-dark);
            border: 1px solid var(--border-slate-dark);
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
            width: 100%;
            max-width: 420px;
        }

        .form-control {
            background-color: #111827;
            border: 1px solid var(--border-slate-dark);
            color: #f3f4f6;
            border-radius: 0.5rem;
            padding: 0.65rem 0.85rem;
            font-size: 0.875rem;
        }

        .form-control:focus {
            background-color: #111827;
            border-color: var(--amber-500);
            box-shadow: 0 0 0 0.25rem rgba(245, 158, 11, 0.15);
            color: #ffffff;
        }

        .btn-filament {
            background-color: var(--amber-500);
            border-color: var(--amber-500);
            color: #000000;
            font-weight: 600;
            border-radius: 0.5rem;
            padding: 0.65rem;
            transition: all 0.15s ease-in-out;
        }

        .btn-filament:hover {
            background-color: var(--amber-600);
            border-color: var(--amber-600);
            color: #ffffff;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            background-color: var(--amber-500);
            color: #000000;
            border-radius: 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
    </style>
</head>
<body>

<div class="container p-3">
    <div class="login-card mx-auto p-4 p-sm-5">
        
        <!-- Header Logo Brand -->
        <div class="text-center mb-4">
            <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                <?php 
                    $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/idvision/admin/assets/images/logo.png';
                    if (file_exists($logoPath)): 
                ?>
                    <img src="/idvision/admin/assets/images/logo.png" alt="IDVISION Logo" class="img-fluid" style="max-height: 42px;">
                <?php else: ?>
                    <div class="brand-icon fw-bold">
                        <i class="bi bi-eyeglasses"></i>
                    </div>
                <?php endif; ?>
                <span class="fw-bold fs-4 text-white tracking-tight">IDVISION</span>
            </div>
            <p class="text-muted fs-7 m-0">Masuk ke Filament Management Panel</p>
        </div>

        <!-- Alert Error Message -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger border-0 bg-danger-subtle text-danger fs-8 py-2.5 px-3 rounded-3 mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-6"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <!-- Form Login -->
        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label fs-8 fw-semibold text-body mb-1">Email Admin</label>
                <input type="email" name="username" class="form-control" value="admin@idvision.com" placeholder="admin@idvision.com" required autofocus>
            </div>

            <div class="mb-4">
                <label class="form-label fs-8 fw-semibold text-body mb-1">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-filament w-100 fs-7 d-flex align-items-center justify-content-center gap-2">
                <span>Sign In</span>
                <i class="bi bi-arrow-right fs-6"></i>
            </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top border-secondary border-opacity-10">
            <small class="text-muted fs-8">&copy; <?= date('Y') ?> IDVISION Eyewear System</small>
        </div>

    </div>
</div>

</body>
</html>