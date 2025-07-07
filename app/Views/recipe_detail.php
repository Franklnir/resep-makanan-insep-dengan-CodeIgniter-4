<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>insep - Resep <?= esc($recipe['name'] ?? 'Tidak Ditemukan') ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('/style.css'); ?>">
    <style>
        /* Tambahkan atau modifikasi CSS ini di file style.css Anda */
        .recipe-detail-header img {
            width: 100%;
            /* Atur tinggi minimum agar gambar lebih besar ke bawah */
            min-height: 400px; /* Sesuaikan nilai ini sesuai keinginan Anda, misalnya 400px, 500px, 600px */
            max-height: 600px; /* Batasi tinggi maksimum jika tidak ingin terlalu besar */
            object-fit: cover; /* Memastikan gambar mengisi area tanpa terdistorsi */
            display: block;
        }

        /* Jika Anda ingin menghilangkan overlay agar gambar terlihat penuh, Anda bisa atur ini juga */
        .recipe-detail-header {
            position: relative;
            /* Hapus atau ubah overflow: hidden; jika ada, untuk memastikan gambar tidak terpotong */
            overflow: visible; /* Atau biarkan default jika tidak ada */
        }

        /* Sesuaikan posisi overlay jika gambar membesar */
        .recipe-header-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(to top, rgba(0,0,0,0.7), rgba(0,0,0,0)); /* Sesuaikan transparansi */
            color: white;
            padding: 20px;
            box-sizing: border-box;
        }

        /* Sesuaikan ukuran font atau padding jika overlay menjadi terlalu sempit */
        .recipe-detail-header h1 {
            font-size: 2.5em; /* Atau sesuaikan */
            margin-bottom: 10px;
        }
        .recipe-meta-info span {
            font-size: 0.9em; /* Atau sesuaikan */
            margin-right: 15px;
        }
    </style>
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
            <a href="<?= base_url('/') ?>"><i class="fas fa-home"></i> Home</a>
            <a href="<?= base_url('/upload') ?>"><i class="fas fa-upload"></i> Upload</a>
            <a href="<?= base_url('/user') ?>"><i class="fas fa-user"></i> Profile</a>
            <a href="#" onclick="goToRiwayat()"><i class="fas fa-history"></i> Riwayat</a>
        </nav>
        <div class="user-profile">
            <img src="<?= esc(session()->get('user_avatar', 'https://via.placeholder.com/40')) ?>"
                 alt="Profile" class="user-avatar">
            <span class="username"><?= esc(session()->get('username', 'Anonim')) ?></span>
        </div>
    </header>

    <main class="container">
        <div class="recipe-detail-header">
            <?php if (!empty($recipe['imageUrl'])): ?>
                <img src="<?= esc($recipe['imageUrl']) ?>" alt="<?= esc($recipe['name']) ?>">
            <?php else: ?>
                <img src="https://source.unsplash.com/random/1200x600/?food,cooking" alt="Default Recipe Image">
            <?php endif; ?>
            <div class="recipe-header-overlay">
                <h1><?= esc($recipe['name'] ?? 'Resep Tidak Ditemukan') ?></h1>
                <div class="recipe-meta-info">
                    <span><i class="fas fa-utensils"></i> Kategori: <?= esc($recipe['category'] ?? 'N/A') ?></span>
                    <span><i class="fas fa-user"></i> Penulis: <a href="<?= base_url('riwayat/' . urlencode($recipe['author'] ?? 'Anonim')) ?>" style="color: inherit; text-decoration: none; font-weight: 500;"><?= esc($recipe['author'] ?? 'Anonim') ?></a></span>
                    <span><i class="far fa-calendar-alt"></i> Dibuat: <?= date('d M Y', $recipe['timestamp'] ?? time()) ?></span>
                    <span><i class="far fa-clock"></i> Waktu: <?= esc($recipe['time'] ?? 'N/A') ?></span>
                    <span><i class="fas fa-signal"></i> Kesulitan: <?= esc($recipe['difficulty'] ?? 'N/A') ?></span>
                </div>
            </div>
        </div>

        <div class="recipe-detail-content">
            <div class="recipe-description-box">
                <h2>Deskripsi Resep</h2>
                <p><?= esc($recipe['description'] ?? 'Tidak ada deskripsi.') ?></p>
            </div>

            <div class="recipe-info-box">
                <h3>Detail Resep</h3>
                <p><i class="fas fa-tag"></i> <strong>Kategori:</strong> <?= esc($recipe['category'] ?? 'N/A') ?></p>
                <p><i class="fas fa-user-edit"></i> <strong>Penulis:</strong> <a href="<?= base_url('riwayat/' . urlencode($recipe['author'] ?? 'Anonim')) ?>" style="color: var(--dark); text-decoration: none;"><?= esc($recipe['author'] ?? 'Anonim') ?></a></p>
                <p><i class="far fa-calendar-alt"></i> <strong>Tanggal Dibuat:</strong> <?= date('d M Y H:i:s', $recipe['timestamp'] ?? time()) ?></p>
                <p><i class="far fa-clock"></i> <strong>Estimasi Waktu:</strong> <?= esc($recipe['time'] ?? 'N/A') ?></p>
                <p><i class="fas fa-signal"></i> <strong>Tingkat Kesulitan:</strong> <?= esc($recipe['difficulty'] ?? 'N/A') ?></p>
            </div>

            <div class="recipe-ingredients">
                <h2><i class="fas fa-list-ul"></i> Bahan-bahan:</h2>
                <?php if (!empty($recipe['ingredients'])): ?>
                    <ul>
                        <?php
                        // Memastikan ingredients adalah array, jika tidak, coba konversi dari string
                        $ingredients = $recipe['ingredients'];
                        if (!is_array($ingredients)) {
                            // Asumsikan dipisahkan oleh baris baru atau koma
                            $ingredients = array_map('trim', explode("\n", $ingredients));
                            if (count($ingredients) === 1 && strpos($ingredients[0], ',') !== false) {
                                $ingredients = array_map('trim', explode(',', $ingredients[0]));
                            }
                            // Hapus entri kosong
                            $ingredients = array_filter($ingredients);
                        }
                        ?>
                        <?php if (!empty($ingredients)): ?>
                            <?php foreach ($ingredients as $ingredient): ?>
                                <li><?= esc($ingredient) ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Tidak ada bahan-bahan yang tercatat.</p>
                        <?php endif; ?>
                    </ul>
                <?php else: ?>
                    <p>Tidak ada bahan-bahan yang tercatat.</p>
                <?php endif; ?>
            </div>

            <div class="recipe-steps">
                <h2><i class="fas fa-tasks"></i> Langkah-langkah:</h2>
                <?php if (!empty($recipe['instructions'])): // Menggunakan 'instructions' bukan 'steps' sesuai controller Anda ?>
                    <ol>
                        <?php
                        // Memastikan instructions adalah array, jika tidak, coba konversi dari string
                        $instructions = $recipe['instructions'];
                        if (!is_array($instructions)) {
                            // Asumsikan dipisahkan oleh baris baru
                            $instructions = array_map('trim', explode("\n", $instructions));
                            // Hapus entri kosong
                            $instructions = array_filter($instructions);
                        }
                        ?>
                        <?php if (!empty($instructions)): ?>
                            <?php foreach ($instructions as $step): ?>
                                <li><?= esc($step) ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Tidak ada langkah-langkah yang tercatat.</p>
                        <?php endif; ?>
                    </ol>
                <?php else: ?>
                    <p>Tidak ada langkah-langkah yang tercatat.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($recipe['videoUrl'])): ?>
            <div class="recipe-video">
                <h2><i class="fas fa-video"></i> Video Tutorial:</h2>
                <?php
                    // Fungsi untuk mengkonversi URL YouTube ke embed URL
                    function getYouTubeEmbedUrl($url) {
                        $pattern = '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com|youtu\.be)\/(?:watch\?v=|embed\/|v\/|)([a-zA-Z0-9_-]{11})(?:\S+)?/';
                        if (preg_match($pattern, $url, $matches)) {
                            return 'https://www.youtube.com/embed/' . $matches[1]; // Perbaiki ke embed URL YouTube yang standar
                        }
                        return null;
                    }
                    $embedUrl = getYouTubeEmbedUrl($recipe['videoUrl']);
                ?>
                <?php if ($embedUrl): ?>
                    <div class="video-responsive">
                        <iframe src="<?= esc($embedUrl) ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                <?php else: ?>
                    <p>URL video tidak valid atau tidak dapat di-embed.</p>
                    <a href="<?= esc($recipe['videoUrl']) ?>" target="_blank" class="view-btn">Lihat Video di Browser</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="action-buttons">
            <?php
            // Logika untuk menampilkan tombol edit dan hapus hanya jika pengguna adalah penulis resep
            $currentUsername = session()->get('username'); // Ambil username dari sesi
            if ($currentUsername && $currentUsername === ($recipe['author'] ?? '') && !empty($recipe['id'])) {
                echo '<a href="' . base_url('edit_recipe/' . esc($recipe['id'])) . '" class="view-btn" style="background-color: var(--blue); margin-right: 10px;"><i class="fas fa-edit"></i> Edit Resep</a>';
                echo '<form action="' . base_url('delete_recipe/' . esc($recipe['id'])) . '" method="POST" style="display: inline;" onsubmit="return confirm(\'Apakah Anda yakin ingin menghapus resep ini?\');">';
                echo csrf_field(); // Tambahkan CSRF token untuk keamanan
                echo '<button type="submit" class="view-btn" style="background-color: var(--gray);"><i class="fas fa-trash"></i> Hapus Resep</button>';
                echo '</form>';
            }
            ?>
            <a href="<?= base_url('/') ?>" class="view-btn"><i class="fas fa-arrow-left"></i> Kembali ke Daftar Resep</a>
        </div>
    </main>

    <script src="<?= base_url('static/js/script.js') ?>"></script>
    <script>
        // Fungsi-fungsi JS yang Anda berikan, saya biarkan di sini agar berfungsi
        function clearSearch() {
            const searchInput = document.querySelector('.search');
            if (searchInput) {
                searchInput.value = '';
            }
            const clearBtn = document.querySelector('.clear-button');
            if (clearBtn) {
                clearBtn.style.display = 'none';
            }
        }

        function goToRiwayat() {
            const usernameSpan = document.querySelector('.username');
            const username = usernameSpan ? usernameSpan.textContent.trim() : 'Anonim';
            window.location.href = `<?= base_url('riwayat/') ?>${encodeURIComponent(username)}`;
        }

        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.querySelector('.search');
            const clearButton = document.querySelector('.clear-button');

            if (searchInput && clearButton) {
                searchInput.addEventListener('input', () => {
                    if (searchInput.value.length > 0) {
                        clearButton.style.display = 'block';
                    } else {
                        clearButton.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>