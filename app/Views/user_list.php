<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pengguna - insep</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('style.css');?>">
</head>
<body>
    <header class="navbar">
        <div class="logo">ins<span>ep</span></div>
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search" placeholder="Cari pengguna...">
            <button class="clear-button" onclick="clearSearch()">×</button>
        </div>
        <nav class="nav-links">
            <a href="<?= base_url('/') ?>"><i class="fas fa-home"></i> Home</a>
            <a href="<?= base_url('/upload') ?>"><i class="fas fa-upload"></i> Upload</a>
            <a href="<?= base_url('/user') ?>" class="active"><i class="fas fa-users"></i> Pengguna</a>
            <a href="#" onclick="goToRiwayat()"><i class="fas fa-history"></i> Riwayat</a>
        </nav>
        <div class="user-profile">
            <img src="<?= esc($session_avatar) ?>"
                 alt="Profile" class="user-avatar">
            <span class="username"><?= esc($session_username) ?></span>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">Daftar Pengguna</h1>
        <p class="page-subtitle">Pengguna yang telah mengunggah resep</p>
        
        <?php if (empty($authors)): ?>
            <div class="no-results">
                <i class="fas fa-user-slash"></i>
                <p>Belum ada pengguna yang mengunggah resep.</p>
            </div>
        <?php else: ?>
            <div class="users-container">
                <?php foreach ($authors as $author): // $author sekarang adalah array lengkap user ?>
                    <div class="user-card">
                        <div class="user-avatar-container">
                            <?php 
                                // Gunakan profileImage dari Firebase jika ada, jika tidak, gunakan UI Avatars
                                $avatarSrc = !empty($author['profileImage']) 
                                    ? esc($author['profileImage']) 
                                    : 'https://ui-avatars.com/api/?name=' . urlencode($author['username']) . '&background=ff6b6b&color=fff';
                            ?>
                            <img src="<?= $avatarSrc ?>" 
                                 alt="<?= esc($author['username']) ?>" class="user-card-avatar">
                        </div>
                        <div class="user-info">
                            <h3><?= esc($author['username']) ?></h3>
                            <p class="user-email"><?= esc($author['email']) ?></p>
                            <?php if (!empty($author['nomorHp'])): ?>
                                <p class="user-phone">Telp: <?= esc($author['nomorHp']) ?></p>
                            <?php endif; ?>
                            <a href="<?= base_url('riwayat/' . urlencode($author['username'])) ?>" class="view-btn">
                                <i class="fas fa-eye"></i> Lihat Resep
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="back-container">
            <a href="<?= base_url('/') ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar Resep
            </a>
        </div>
    </div>

    <script src="<?= base_url('static/js/script.js') ?>"></script>
</body>
</html>