<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>insep - Resep Masakan</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="<?= base_url('/style.css');?>">
</head>
<body>
    <header class="navbar">
        <div class="logo">ins<span>ep</span></div>
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search" placeholder="Cari resep...">
            <button class="clear-button" onclick="clearSearch()">×</button>
        </div>
       <nav class="nav-links">
    <a href="<?= base_url('/') ?>" class="active"><i class="fas fa-home"></i> Home</a>
    <a href="<?= base_url('/upload') ?>"><i class="fas fa-upload"></i> Upload</a>
    <a href="<?= base_url('/user') ?>"><i class="fas fa-user"></i> Pengguna</a>
   <a href="<?= base_url('riwayat/' . urlencode(session()->get('username', 'Anonim'))) ?>">
    <i class="fas fa-history"></i> Riwayat
</a>
  <a href="<?= base_url('/logout') ?>" class="logout-link">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>

</nav>

        <div class="user-profile">
            <img src="<?= esc(session()->get('user_avatar', 'https://via.placeholder.com/40')) ?>"
                 alt="Profile" class="user-avatar">
            <span class="username"><?= esc(session()->get('username', 'Anonim')) ?></span>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">Resep Populer</h1>
        
        <!-- Category Filter -->
        <div class="category-filter">
            <button class="category-btn active" data-category="all">Semua</button>
            <button class="category-btn" data-category="camilan">Camilan</button>
            <button class="category-btn" data-category="kue">Kue</button>
            <button class="category-btn" data-category="minuman">Minuman</button>
            <button class="category-btn" data-category="diet">kuliner</button>
            <button class="category-btn" data-category="vegan">masakan</button>
        </div>
        
        <div id="recipes-container" class="recipes-container">
            <?php if (empty($recipes)): ?>
                <div class="no-results">
                    <i class="fas fa-utensils"></i>
                    <p>Belum ada resep yang tersedia.</p>
                </div>
            <?php else: ?>
                <?php foreach ($recipes as $recipe): ?>
                    <div class="recipe-card" data-recipe='<?= json_encode($recipe) ?>' data-category="<?= esc(strtolower($recipe['category'])) ?>">
                        <div class="card-image">
                            <img src="<?= esc($recipe['imageUrl'] ?? 'https://source.unsplash.com/random/300x200/?food') ?>" alt="<?= esc($recipe['name']) ?>">
                            <div class="card-overlay">
                                <span class="time"><i class="far fa-clock"></i> <?= esc($recipe['time'] ?? '30m') ?></span>
                                <span class="difficulty"><i class="fas fa-signal"></i> <?= esc($recipe['difficulty'] ?? 'Medium') ?></span>
                            </div>
                        </div>
                        <div class="card-content">
                            <div class="card-header">
                                <span class="tag popular">Popular</span>
                                <span class="category-tag"><?= esc($recipe['category']) ?></span>
                            </div>
                            <h2><?= esc($recipe['name']) ?></h2>
                            <p class="description"><?= esc($recipe['description']) ?></p>
                            <div class="card-footer">
                                <div class="ratings">
                                    <i class="fas fa-star"></i>
                                    <span>4.8</span>
                                </div>
                               <a href="<?= base_url('recipe/' . $recipe['id']) ?>" class="view-btn">
    <i class="fas fa-eye"></i> Lihat
</a>

                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="<?= base_url('static/js/script.js') ?>"></script>
</body>
</html>