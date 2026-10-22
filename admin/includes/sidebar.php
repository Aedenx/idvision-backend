<div class="col-md-3 col-lg-2 d-md-block filament-sidebar p-3 position-sticky" style="top: 57px; height: calc(100vh - 57px); overflow-y: auto;">
    <!-- Menu Group Label -->
    <div class="text-uppercase text-gold fs-8 fw-bold mb-3 px-2 pt-2 opacity-75" style="letter-spacing: 0.08em;">Resource Menu</div>
    
    <nav class="d-flex flex-column gap-1 mb-auto">
        <?php $currentUri = $_SERVER['REQUEST_URI']; ?>

        <a href="/idvision/admin/products/index.php" class="filament-nav-link <?= strpos($currentUri, '/products/') !== false ? 'active' : '' ?>">
            <i class="bi bi-eyeglasses fs-6"></i>
            <span>Produk Kacamata</span>
        </a>

        <a href="/idvision/admin/categories/index.php" class="filament-nav-link <?= strpos($currentUri, '/categories/') !== false ? 'active' : '' ?>">
            <i class="bi bi-tags fs-6"></i>
            <span>Kategori Produk</span>
        </a>

        <a href="/idvision/admin/banners/index.php" class="filament-nav-link <?= strpos($currentUri, '/banners/') !== false ? 'active' : '' ?>">
            <i class="bi bi-images fs-6"></i>
            <span>Banner Hero</span>
        </a>

        <!-- KELOLA PESANAN DENGAN BADGE NOTIFIKASI -->
        <a href="/idvision/admin/orders/index.php" class="filament-nav-link d-flex justify-content-between align-items-center <?= strpos($currentUri, '/orders/') !== false ? 'active' : '' ?>">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-bag-check fs-6"></i>
                <span>Kelola Pesanan</span>
            </div>
            <span id="pending-orders-badge" class="badge bg-danger rounded-pill d-none">0</span>
        </a>

        <!-- LIVE CHAT PELANGGAN -->
        <a href="/idvision/admin/chat/index.php" class="filament-nav-link <?= strpos($currentUri, '/chat/') !== false ? 'active' : '' ?>">
            <i class="bi bi-chat-dots fs-6"></i>
            <span>Live Chat</span>
        </a>

        <a href="/idvision/admin/members/index.php" class="filament-nav-link <?= strpos($currentUri, '/members/') !== false ? 'active' : '' ?>">
            <i class="bi bi-people fs-6"></i>
            <span>Kelola Member</span>
        </a>
    </nav>

    <hr class="my-4 border-secondary opacity-25">

    <div class="px-2 pb-3">
        <a href="/idvision/admin/auth/logout.php" class="btn btn-outline-danger btn-sm w-100 fw-semibold py-1.5 rounded-2">
            <i class="bi bi-box-arrow-right me-1"></i> Sign Out
        </a>
    </div>
</div>