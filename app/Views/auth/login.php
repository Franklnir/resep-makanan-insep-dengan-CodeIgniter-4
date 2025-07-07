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
            /* Corrected image filename and path assumption */
            background-image: url('<?= base_url('gambar/login.jpg') ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Poppins', sans-serif;
        }

        .auth-container {
            background-color: rgba(255, 255, 255, 0.85);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        /* ... Gaya CSS Anda yang lain untuk auth-container ... */
    </style>
</head>
<body>
    <div class="auth-container">
        <h2>Login</h2>
        <form id="loginForm">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" placeholder="Masukkan email Anda" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" placeholder="Masukkan password Anda" required>
            </div>
            <p id="errorMessage" class="error-message" style="display: none;"></p>
            <button type="submit" class="auth-button">Login</button>
        </form>
        <a href="<?= base_url('/register') ?>" class="auth-link">Belum punya akun? Daftar di sini</a>
    </div>

    <script src="https://www.gstatic.com/firebasejs/9.6.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.0/firebase-auth-compat.js"></script>
    <script>
        // Konfigurasi Firebase Anda (diambil dari controller)
        const firebaseConfig = JSON.parse('<?= esc($firebaseConfig, 'js') ?>');
        

        // Inisialisasi Firebase
        firebase.initializeApp(firebaseConfig);
        const auth = firebase.auth();

        const loginForm = document.getElementById('loginForm');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const errorMessage = document.getElementById('errorMessage');

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorMessage.style.display = 'none';

            const email = emailInput.value;
            const password = passwordInput.value;

            try {
                const userCredential = await auth.signInWithEmailAndPassword(email, password);
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
                    alert('Login berhasil!');
                    window.location.href = '<?= base_url('/') ?>';
                } else {
                    errorMessage.textContent = data.message || 'Gagal mengatur sesi di server.';
                    errorMessage.style.display = 'block';
                }

            } catch (error) {
                console.error("Firebase Login Error:", error);
                let msg = 'Terjadi kesalahan saat login.';
                switch (error.code) {
                    case 'auth/user-not-found':
                        msg = 'Email tidak terdaftar.';
                        break;
                    case 'auth/wrong-password':
                        msg = 'Password salah.';
                        break;
                    case 'auth/invalid-email':
                        msg = 'Format email tidak valid.';
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