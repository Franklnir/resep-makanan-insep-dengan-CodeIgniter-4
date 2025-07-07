<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('/loginregister.css');?>">
    <style>
        body {
            /* Pastikan gambar 'login.jpg' ada di folder public/gambar/ */
            background-image: url('<?= base_url('gambar/login.jpg') ?>');
            background-size: cover; /* Untuk memastikan gambar menutupi seluruh area */
            background-position: center; /* Untuk memusatkan gambar */
            background-repeat: no-repeat; /* Mencegah pengulangan gambar */
            background-attachment: fixed; /* Membuat background tetap saat scroll */
            display: flex; /* Menggunakan flexbox untuk memusatkan konten */
            justify-content: center; /* Memusatkan secara horizontal */
            align-items: center; /* Memusatkan secara vertikal */
            min-height: 100vh; /* Memastikan body mengambil seluruh tinggi viewport */
            margin: 0; /* Menghilangkan margin default body */
            font-family: 'Poppins', sans-serif; /* Pastikan font diterapkan */
        }

        .auth-container {
            background-color: rgba(255, 255, 255, 0.85); /* Tambahkan transparansi agar gambar latar terlihat */
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        /* ... Gaya CSS Anda yang lain untuk auth-container dari loginregister.css atau jika ada di sini ... */
    </style>
</head>
<body>
    <div class="auth-container">
        <h2>Daftar Akun Baru</h2>
        <form id="registerForm">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" placeholder="Masukkan email Anda" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" placeholder="Minimal 6 karakter" required minlength="6">
            </div>
            <div class="form-group">
                <label for="confirmPassword">Konfirmasi Password</label>
                <input type="password" id="confirmPassword" placeholder="Ulangi password Anda" required>
            </div>
            <p id="errorMessage" class="error-message" style="display: none;"></p>
            <button type="submit" class="auth-button">Daftar</button>
        </form>
        <a href="<?= base_url('/login') ?>" class="auth-link">Sudah punya akun? Login di sini</a>
    </div>

    <script src="https://www.gstatic.com/firebasejs/9.6.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.0/firebase-auth-compat.js"></script>
    <script>
        // Konfigurasi Firebase Anda (diambil dari controller)
        // Pastikan $firebaseConfig di-encode dengan benar sebagai JSON di controller.
        const firebaseConfig = <?= json_encode($firebaseConfig) ?>;

        // Inisialisasi Firebase
        firebase.initializeApp(firebaseConfig);
        const auth = firebase.auth();

        const registerForm = document.getElementById('registerForm');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const errorMessage = document.getElementById('errorMessage');

        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorMessage.style.display = 'none';

            const email = emailInput.value;
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (password !== confirmPassword) {
                errorMessage.textContent = 'Password dan konfirmasi password tidak cocok.';
                errorMessage.style.display = 'block';
                return;
            }

            try {
                const userCredential = await auth.createUserWithEmailAndPassword(email, password);
                const user = userCredential.user;

                // Kirim UID, email, displayName, photoURL ke backend CodeIgniter untuk mengatur sesi
                const response = await fetch('<?= base_url('/auth/setSession') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        uid: user.uid,
                        email: user.email,
                        displayName: user.displayName,
                        photoURL: user.photoURL
                    })
                });

                const data = await response.json();
                if (data.status === 'success') {
                    alert('Registrasi berhasil! Anda sekarang login.');
                    window.location.href = '<?= base_url('/') ?>';
                } else {
                    errorMessage.textContent = data.message || 'Gagal mengatur sesi di server setelah register.';
                    errorMessage.style.display = 'block';
                }

            } catch (error) {
                console.error("Firebase Register Error:", error);
                let msg = 'Terjadi kesalahan saat pendaftaran.';
                switch (error.code) {
                    case 'auth/email-already-in-use':
                        msg = 'Email ini sudah terdaftar.';
                        break;
                    case 'auth/invalid-email':
                        msg = 'Format email tidak valid.';
                        break;
                    case 'auth/weak-password':
                        msg = 'Password terlalu lemah (minimal 6 karakter).';
                        break;
                    case 'auth/network-request-failed':
                        msg = 'Koneksi internet bermasalah. Coba lagi.';
                        break;
                    default:
                        msg = error.message;
                        break;
                }
                errorMessage.textContent = msg;
                errorMessage.style.display = 'block';
            }
        });
    </script>
</body>
</html>