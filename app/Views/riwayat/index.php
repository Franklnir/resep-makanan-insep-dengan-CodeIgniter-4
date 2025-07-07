<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Resep <?= esc($username) ?> - insep</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <link rel="stylesheet" href="<?= base_url('/style.css');?>">
<body>
    <header class="navbar">
        <div class="logo">ins<span>ep</span></div>
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search" placeholder="Cari resep...">
            <button class="clear-button" onclick="clearSearch()">×</button>
        </div>
        <nav class="nav-links">
            <a href="<?= base_url('/') ?>"><i class="fas fa-home"></i> Beranda</a>
            <a href="<?= base_url('/upload') ?>"><i class="fas fa-upload"></i> Upload</a>
            <a href="<?= base_url('/user') ?>"><i class="fas fa-users"></i> Pengguna</a>
            <a href="<?= base_url('riwayat/' . urlencode(session()->get('username', 'Anonim'))) ?>" class="active"><i class="fas fa-history"></i> Riwayat</a>
        </nav>
        <div class="user-profile">
            <img src="<?= esc(session()->get('user_avatar', 'https://via.placeholder.com/40')) ?>"
                 alt="Profil" class="user-avatar">
            <span class="username"><?= esc(session()->get('username', 'Anonim')) ?></span>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">Resep dari <?= esc($username) ?></h1>

        <?php if (isset($userProfile)): ?>
            <div class="profile-header">
                <?php if (!empty($userProfile['profileImage'])): ?>
                    <img src="<?= esc($userProfile['profileImage']) ?>" alt="<?= esc($username) ?>" class="profile-avatar-large">
                <?php else: ?>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($username) ?>&background=ff6b6b&color=fff"
                         alt="<?= esc($username) ?>" class="profile-avatar-large">
                <?php endif; ?>
                <p class="profile-email"><?= esc($userProfile['email'] ?? 'Email tidak tersedia') ?></p>
            </div>
        <?php endif; ?>

        <a href="<?= base_url('/user') ?>" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Daftar Pengguna</a>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success">
                <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger">
                <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <?php if (empty($recipes)): ?>
            <div class="no-results">
                <i class="fas fa-utensils"></i>
                <p>Pengguna ini belum mengunggah resep.</p>
            </div>
        <?php else: ?>
            <div class="recipes-grid">
                <?php foreach ($recipes as $recipe): ?>
                    <div class="recipe-card-grid">
                        <?php
                            $imageUrl = !empty($recipe['imageUrl']) ? esc($recipe['imageUrl']) : base_url('static/default-recipe.jpg');
                        ?>
                        <img src="<?= $imageUrl ?>" alt="<?= esc($recipe['name'] ?? 'Resep Tanpa Nama') ?>" class="recipe-thumbnail">
                        <div class="recipe-content">
                            <h3 class="recipe-title"><?= esc($recipe['name'] ?? 'Nama Resep Tidak Ada') ?></h3>
                            <p class="recipe-meta">
                                <span class="category"><i class="fas fa-tag"></i> <?= esc($recipe['category'] ?? 'Tidak Dikategorikan') ?></span>
                                <span class="author"><i class="fas fa-user"></i> <?= esc($recipe['author'] ?? 'Anonim') ?></span>
                            </p>
                            <p class="recipe-description"><?= substr(esc($recipe['description'] ?? 'Tidak ada deskripsi.'), 0, 100) . (strlen(esc($recipe['description'] ?? '')) > 100 ? '...' : '') ?></p>
                            
                            <div class="recipe-actions">
                                <a href="<?= base_url('recipe/' . ($recipe['id'] ?? '')) ?>" class="btn-primary"><i class="fas fa-info-circle"></i> Detail</a>
                                <?php if (session()->get('username') === $username): ?>
                                    <a href="<?= base_url('edit_recipe/' . ($recipe['id'] ?? '')) ?>" class="btn-info"><i class="fas fa-edit"></i> Edit</a>
                     <form action="<?= base_url('riwayat/delete/' . $recipe['id']) ?>" method="post" onsubmit="return confirm('Yakin ingin menghapus resep ini?');">
    <?= csrf_field() ?>
    <button type="submit" class="btn-danger"><i class="fas fa-trash-alt"></i> Hapus</button>
</form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function clearSearch() {
            document.querySelector('.search').value = '';
        }
    </script>
</body>
</html>