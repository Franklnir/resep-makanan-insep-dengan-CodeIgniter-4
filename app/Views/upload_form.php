<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Unggah Resep Baru - insep</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <link rel="stylesheet" href="<?= base_url('style.css'); ?>" />
</head>
<body>
    <header class="navbar">
        <div class="logo">
            ins<span>ep</span>
        </div>
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search" placeholder="Cari resep..." />
            <button class="clear-button" onclick="clearSearch()">×</button>
        </div>
        <nav class="nav-links">
            <a href="<?= base_url('/') ?>"><i class="fas fa-home"></i> Home</a>
            <a href="<?= base_url('/upload') ?>" class="active"><i class="fas fa-upload"></i> Upload</a>
            <a href="<?= base_url('/user') ?>"><i class="fas fa-users"></i> Pengguna</a>
            <a href="#" onclick="goToRiwayat()"><i class="fas fa-history"></i> Riwayat</a>
        </nav>
        <div class="user-profile">
            <img
                src="<?= esc($session_avatar ?? base_url('assets/img/default_avatar.png')) ?>"
                alt="profileImage"
                class="user-avatar"
            />
            <span class="username"><?= esc($session_username ?? 'Guest') ?></span>
        </div>
    </header>

    <div class="container upload-form-container">
        <h1 class="page-title">Unggah Resep Baru</h1>
        <p class="page-subtitle">Bagikan kreasi masakanmu dengan dunia!</p>

        <?php if (!empty($errors)) : ?>
            <div class="alert-message error-message">
                <i class="fas fa-exclamation-circle"></i>
                <p>Terjadi kesalahan validasi:</p>
                <ul>
                    <?php foreach ($errors as $error) : ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)) : ?>
            <div class="alert-message success-message">
                <i class="fas fa-check-circle"></i>
                <p><?= esc($success) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)) : ?>
            <div class="alert-message error-message">
                <i class="fas fa-times-circle"></i>
                <p><?= esc($error) ?></p>
            </div>
        <?php endif; ?>

        <?= form_open_multipart('/upload/submit', ['class' => 'recipe-upload-form']) ?>

            <div class="form-group">
                <label for="name">Nama Resep:</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= esc(old('name')) ?>"
                    required
                />
            </div>

            <div class="form-group">
                <label for="description">Deskripsi:</label>
                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    required
                ><?= esc(old('description')) ?></textarea>
            </div>

            <div class="form-group">
                <label for="category">Kategori:</label>
                <select id="category" name="category" required>
                    <option value="">-- Pilih Kategori --</option>
                    <option value="Makanan" <?= old('category') === 'Makanan' ? 'selected' : '' ?>>Makanan</option>
                    <option value="Minuman" <?= old('category') === 'Minuman' ? 'selected' : '' ?>>Minuman</option>
                    <option value="Camilan" <?= old('category') === 'Camilan' ? 'selected' : '' ?>>Camilan</option>
                    <option value="Kue" <?= old('category') === 'Kue' ? 'selected' : '' ?>>Kue</option>
                </select>
            </div>

            <div class="form-group">
                <label for="ingredients">Bahan-bahan (pisahkan dengan baris baru):</label>
                <textarea
                    id="ingredients"
                    name="ingredients"
                    rows="6"
                    required
                ><?= esc(old('ingredients')) ?></textarea>
                <small>Setiap bahan pada baris baru.</small>
            </div>

            <div class="form-group">
                <label for="steps">Langkah-langkah (pisahkan dengan baris baru):</label>
                <textarea
                    id="steps"
                    name="steps"
                    rows="6"
                    required
                ><?= esc(old('steps')) ?></textarea>
                <small>Setiap langkah pada baris baru.</small>
            </div>

            <div class="form-group">
                <label for="videoUrl">URL Video (Opsional, dari YouTube):</label>
                <input
                    type="url"
                    id="videoUrl"
                    name="videoUrl"
                    value="<?= esc(old('videoUrl')) ?>"
                    placeholder="Contoh: https://www.youtube.com/watch?v=VIDEO_ID"
                />
                <small>Masukkan URL video YouTube lengkap.</small>
            </div>

            <div class="form-group file-upload-group">
                <label for="image">Gambar Resep (Opsional):</label>
                <input
                    type="file"
                    id="image"
                    name="image"
                    accept="image/*"
                    onchange="previewImage(event)"
                />
                <div class="image-preview" id="imagePreview">
                    <img
                        id="previewImg"
                        src="#"
                        alt="Pratinjau Gambar"
                        style="display: none"
                    />
                    <span id="previewText">Tidak ada gambar dipilih</span>
                </div>
                <small>Format: JPG, PNG, GIF. Maks. 2MB.</small>
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-paper-plane"></i> Unggah Resep
            </button>
            <a href="<?= base_url('/') ?>" class="back-btn back-to-home">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar Resep
            </a>
        <?= form_close() ?>
    </div>

    <script>
        // Fungsi pratinjau gambar
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function () {
                const output = document.getElementById("previewImg");
                const text = document.getElementById("previewText");
                output.src = reader.result;
                output.style.display = "block";
                text.style.display = "none";
            };
            if (event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            } else {
                document.getElementById("previewImg").style.display = "none";
                document.getElementById("previewText").style.display = "block";
                document.getElementById("previewImg").src = "#";
            }
        }

        // Fungsionalitas search dan clear button (sesuai dengan navbar Anda)
        document.addEventListener("DOMContentLoaded", function () {
            const searchInput = document.querySelector(".search");
            const clearButton = document.querySelector(".clear-button");

            if (searchInput && clearButton) {
                searchInput.addEventListener("input", function () {
                    if (this.value.length > 0) {
                        clearButton.style.display = "block";
                    } else {
                        clearButton.style.display = "none";
                    }
                });
            }
        });

        function clearSearch() {
            const searchInput = document.querySelector(".search");
            if (searchInput) {
                searchInput.value = "";
                document.querySelector(".clear-button").style.display = "none";
                // Opsional: tambahkan logika pencarian ulang atau tampilkan semua resep
            }
        }

        function goToRiwayat() {
            // Contoh redirect ke halaman riwayat resep user dengan username sebagai parameter
            const username = "<?= esc($session_username ?? 'guest') ?>";
            // Ganti '/riwayat' dengan route yang sesuai jika berbeda
            window.location.href = `<?= base_url('/riwayat') ?>?user=${encodeURIComponent(
                username
            )}`;
        }
    </script>
</body>
</html>
