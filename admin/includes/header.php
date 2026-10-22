<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: /idvision/admin/auth/login.php");
    exit;
}

$adminName =$_SESSION['admin_name'] ?? 'Administrator';
$adminInitial = strtoupper(substr($adminName, 0, 1));
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IDVISION Admin Panel</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --font-family-sans: 'Inter', system-ui, -apple-system, sans-serif;
            --amber-500: #f59e0b;
            --amber-600: #d97706;
            --amber-400: #fbbf24;
            --filament-bg-light: #f8fafc;
            --filament-card-light: #ffffff;
            --filament-border-light: #e2e8f0;
            --filament-text-light: #334155;
            --filament-bg-dark: #030712;
            --filament-card-dark: #090d16;
            --filament-border-dark: #1f2937;
            --filament-text-dark: #f3f4f6;
        }

        body {
            font-family: var(--font-family-sans);
            font-size: 0.875rem;
            background-color: var(--filament-bg-light);
            color: var(--filament-text-light);
            letter-spacing: -0.01em;
        }

        [data-bs-theme="dark"] body {
            background-color: var(--filament-bg-dark);
            color: var(--filament-text-dark);
        }

        .topbar-header {
            background-color: #090d16 !important;
            border-bottom: 1px solid #1f2937 !important;
        }

        .filament-sidebar {
            background-color: var(--filament-card-light);
            border-right: 1px solid var(--filament-border-light);
            min-height: calc(100vh - 57px);
            transition: all 0.2s ease;
        }

        [data-bs-theme="dark"] .filament-sidebar {
            background-color: var(--filament-card-dark);
            border-right-color: var(--filament-border-dark);
        }

        .filament-nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 0.85rem;
            border-radius: 0.5rem;
            color: #64748b;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
        }

        .filament-nav-link:hover {
            background-color: #f1f5f9;
            color: var(--amber-600);
        }

        [data-bs-theme="dark"] .filament-nav-link:hover {
            background-color: #111827;
            color: var(--amber-400);
        }

        .filament-nav-link.active {
            background-color: #fffbeb;
            color: var(--amber-600);
            font-weight: 600;
        }

        [data-bs-theme="dark"] .filament-nav-link.active {
            background-color: #1a1508;
            color: var(--amber-400);
            border-left: 3px solid var(--amber-400);
        }

        .card {
            border-radius: 0.75rem;
            border: 1px solid var(--filament-border-light);
            background-color: var(--filament-card-light);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }

        [data-bs-theme="dark"] .card {
            border-color: var(--filament-border-dark);
            background-color: var(--filament-card-dark);
            box-shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.5);
        }

        .filament-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--amber-400) 0%, var(--amber-600) 100%);
            color: #000000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.35);
            transition: transform 0.15s ease;
            cursor: pointer;
        }

        .filament-avatar:hover {
            transform: scale(1.05);
        }

        .btn-success, .btn-primary {
            background-color: var(--amber-500) !important;
            border-color: var(--amber-500) !important;
            color: #000000 !important;
            font-weight: 600;
        }

        .btn-success:hover, .btn-primary:hover {
            background-color: var(--amber-600) !important;
            border-color: var(--amber-600) !important;
            color: #ffffff !important;
        }

        .table-dark {
            --bs-table-bg: #111827;
            --bs-table-border-color: var(--filament-border-dark);
        }

        .dropdown-toggle::after {
            display: none !important;
        }
    </style>

    <script>
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
    </script>
</head>
<body>

<audio id="orderNotifSound" preload="auto">
    <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
</audio>

<div class="topbar-header sticky-top py-2 px-4">
    <div class="d-flex align-items-center justify-content-between">
        <a href="/idvision/admin/index.php" class="text-decoration-none d-flex align-items-center gap-2">
            <?php 
                $logoPath =$_SERVER['DOCUMENT_ROOT'] . '/idvision/admin/assets/images/logo.png';
                if (file_exists($logoPath)): 
            ?>
                <img src="/idvision/admin/assets/images/logo.png" alt="IDVISION Logo" class="img-fluid" style="max-height: 36px;">
            <?php else: ?>
                <div class="rounded-3 p-1.5 d-flex align-items-center justify-content-center fw-bold" style="width:34px; height:34px; background-color: var(--amber-500); color: #000;">
                    <i class="bi bi-eyeglasses fs-5"></i>
                </div>
            <?php endif; ?>
            <span class="fw-bold fs-5 text-white tracking-tight m-0">IDVISION</span>
        </a>

        <div class="dropdown">
            <a href="#" class="dropdown-toggle text-decoration-none" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="filament-avatar">
                    <?= $adminInitial ?>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg p-2 mt-2" aria-labelledby="userDropdown" style="min-width: 200px; border-radius: 0.75rem;">
                <li class="px-3 py-2 border-bottom mb-1">
                    <div class="fw-semibold text-body fs-7"><?= htmlspecialchars($adminName) ?></div>
                    <small class="text-muted fs-8">admin@idvision.com</small>
                </li>
                
                <li>
                    <button class="dropdown-item rounded-2 py-2 fs-7 d-flex align-items-center gap-2" id="btnThemeToggle">
                        <i class="bi bi-moon-stars text-warning" id="themeIconMenu"></i>
                        <span>Ubah Mode Tampilan</span>
                    </button>
                </li>

                <li><hr class="dropdown-divider my-1"></li>

                <li>
                    <a class="dropdown-item rounded-2 py-2 fs-7 text-danger d-flex align-items-center gap-2" href="/idvision/admin/auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Sign Out</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    let lastKnownOrderId = localStorage.getItem("last_known_order_id") || 0;

    const btnThemeToggle = document.getElementById("btnThemeToggle");
    if (btnThemeToggle) {
        btnThemeToggle.addEventListener("click", function () {
            const currentTheme = document.documentElement.getAttribute("data-bs-theme");
            const newTheme = currentTheme === "dark" ? "light" : "dark";
            document.documentElement.setAttribute("data-bs-theme", newTheme);
            localStorage.setItem("theme", newTheme);
        });
    }

    function checkNewOrders() {
        fetch('/idvision/admin/api/check_new_orders.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.total_new !== undefined) {
                    const badge = document.getElementById('pending-orders-badge');
                    if (badge) {
                        if (data.total_new > 0) {
                            badge.innerText = data.total_new;
                            badge.classList.remove('d-none');
                        } else {
                            badge.classList.add('d-none');
                        }
                    }

                    if (lastKnownOrderId != 0 && data.last_id > lastKnownOrderId) {
                        const sound = document.getElementById('orderNotifSound');
                        if (sound) {
                            sound.play().catch(() => {});
                        }

                        if (confirm(`🔔 ADA PESANAN BARU MASUK!\n\nID Pesanan: #${data.last_id}\nApakah Anda ingin langsung mengelola pesanan ini sekarang?`)) {
                            window.location.href = `/idvision/admin/orders/detail.php?id=${data.last_id}`;
                        }
                    }

                    lastKnownOrderId = data.last_id;
                    localStorage.setItem("last_known_order_id", data.last_id);
                }
            })
            .catch(err => console.error("Gagal mengecek notifikasi order:", err));
    }

    checkNewOrders();
    setInterval(checkNewOrders, 5000);
});
</script>