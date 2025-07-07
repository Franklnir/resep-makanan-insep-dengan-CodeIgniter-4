<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Resep - insep</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('style.css') ?>"> 
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
        <a href="<?= base_url('/') ?>"><i class="fas fa-home"></i> Beranda</a>
        <a href="<?= base_url('/upload') ?>"><i class="fas fa-upload"></i> Upload</a>
        <a href="<?= base_url('/user') ?>"><i class="fas fa-users"></i> Pengguna</a>
        <a href="<?= base_url('riwayat/' . urlencode(session()->get('username', 'Anonim'))) ?>" class="active"><i class="fas fa-history"></i> Riwayat</a>
    </nav>
    <div class="user-profile">
        <img src="<?= esc(session()->get('user_avatar', base_url('assets/img/default_avatar.png'))) ?>"
             alt="Profil" class="user-avatar">
        <span class="username"><?= esc(session()->get('username', 'Anonim')) ?></span>
    </div>
</header>

<div class="container upload-form-container"> <h1 class="page-title">Edit Resep</h1>
    <p class="page-subtitle">Ubah detail resep Anda di sini.</p>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert-message success-message">
            <i class="fas fa-check-circle"></i>
            <p><?= session()->getFlashdata('success') ?></p>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert-message error-message">
            <i class="fas fa-times-circle"></i>
            <p><?= session()->getFlashdata('error') ?></p>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert-message error-message">
            <i class="fas fa-exclamation-circle"></i>
            <p>Terjadi kesalahan validasi:</p>
            <ul>
                <?php foreach (session()->getFlashdata('errors') as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?= form_open_multipart('riwayat/edit_recipe/' . esc($recipeId), ['class' => 'recipe-upload-form']) ?>

        <div class="form-group">
            <label for="name">Nama Resep:</label>
            <input type="text" id="name" name="name" 
                   value="<?= old('name', esc($recipe['name'] ?? '')) ?>" required>
        </div>

        <div class="form-group">
            <label for="description">Deskripsi:</label>
            <textarea id="description" name="description" rows="5" required><?= old('description', esc($recipe['description'] ?? '')) ?></textarea>
        </div>

        <div class="form-group">
            <label for="category">Kategori:</label>
            <select id="category" name="category" required>
                <option value="">-- Pilih Kategori --</option>
                <option value="Makanan" <?= old('category', $recipe['category'] ?? '') === 'Makanan' ? 'selected' : '' ?>>Makanan</option>
                <option value="Minuman" <?= old('category', $recipe['category'] ?? '') === 'Minuman' ? 'selected' : '' ?>>Minuman</option>
                <option value="Camilan" <?= old('category', $recipe['category'] ?? '') === 'Camilan' ? 'selected' : '' ?>>Camilan</option>
                <option value="Kue" <?= old('category', $recipe['category'] ?? '') === 'Kue' ? 'selected' : '' ?>>Kue</option>
            </select>
        </div>

        <div class="form-group">
            <label for="ingredients">Bahan-bahan (pisahkan dengan baris baru):</label>
            <textarea id="ingredients" name="ingredients" rows="7" required><?= old('ingredients', esc(is_array($recipe['ingredients']) ? implode("\n", $recipe['ingredients']) : ($recipe['ingredients'] ?? ''))) ?></textarea>
            <small>Setiap bahan pada baris baru.</small>
        </div>

        <div class="form-group">
            <label for="instructions">Langkah-langkah (pisahkan dengan baris baru):</label>
            <textarea id="instructions" name="instructions" rows="10" required><?= old('instructions', esc(isset($recipe['steps']) ? (is_array($recipe['steps']) ? implode("\n", $recipe['steps']) : $recipe['steps']) : '')) ?></textarea>
            <small>Setiap langkah pada baris baru.</small>
        </div>

        <div class="form-group">
            <label for="videoUrl">URL Video (Opsional, dari YouTube):</label>
            <input type="url" id="videoUrl" name="videoUrl" 
                   value="<?= old('videoUrl', esc($recipe['videoUrl'] ?? '')) ?>"
                   placeholder="Contoh: https://www.youtube.com/watch?v=xxxxxxxxxxx">
            <small>Masukkan URL video YouTube lengkap.</small>
        </div>

        <div class="form-group file-upload-group">
            <label for="image">Gambar Resep (Biarkan kosong untuk mempertahankan gambar lama):</label>
            <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(event)">
            <div class="image-preview" id="imagePreview">
                <img id="previewImg" src="<?= esc($recipe['imageUrl'] ?? '#') ?>" 
                     alt="Pratinjau Gambar" 
                     style="<?= empty($recipe['imageUrl']) ? 'display: none;' : 'display: block;' ?> max-width: 100%; height: auto;">
                <span id="previewText" style="<?= empty($recipe['imageUrl']) ? 'display: block;' : 'display: none;' ?>">
                    Tidak ada gambar dipilih
                </span>
            </div>
            <?php if (!empty($recipe['imageUrl'])): ?>
                <small>Gambar saat ini: <a href="<?= esc($recipe['imageUrl']) ?>" target="_blank">Lihat Gambar</a></small><br>
            <?php endif; ?>
            <small>Format: JPG, PNG, GIF. Maks. 2MB. Jika Anda mengupload gambar baru, gambar lama akan diganti.</small>
        </div>

        <div class="form-group">
            <label for="imageUrl">Atau URL Gambar Eksternal (Opsional):</label>
            <input type="url" id="imageUrl" name="imageUrl" 
                   value="<?= old('imageUrl', esc($recipe['imageUrl'] ?? '')) ?>"
                   placeholder="Contoh: https://example.com/gambar-resep.jpg">
            <small>Prioritas: Gambar yang diunggah > URL Gambar Eksternal > Gambar lama.</small>
        </div>

        <button type="submit" class="submit-btn">
            <i class="fas fa-save"></i> Simpan Perubahan
        </button>
        <a href="<?= base_url('riwayat/' . session()->get('username')) ?>" class="back-btn back-to-home">
            <i class="fas fa-arrow-left"></i> Kembali ke Riwayat Resep
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
            // Jika tidak ada file dipilih, kembali ke gambar lama atau teks "tidak ada gambar"
            const currentImageUrl = "<?= esc($recipe['imageUrl'] ?? '') ?>";
            if (currentImageUrl) {
                document.getElementById("previewImg").src = currentImageUrl;
                document.getElementById("previewImg").style.display = "block";
                document.getElementById("previewText").style.display = "none";
            } else {
                document.getElementById("previewImg").style.display = "none";
                document.getElementById("previewText").style.display = "block";
                document.getElementById("previewImg").src = "#";
            }
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
</script>
</body>
</html>