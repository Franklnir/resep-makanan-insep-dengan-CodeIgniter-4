<?php namespace App\Controllers;

use CodeIgniter\Controller;
use Config\Firebase; // Import kelas konfigurasi Firebase
use CodeIgniter\API\ResponseTrait; // Tambahkan ini untuk memudahkan respons JSON

class Auth extends Controller
{
    use ResponseTrait; // Gunakan trait ini

    protected $firebaseConfig;

    public function __construct()
    {
        // Memuat konfigurasi Firebase
        $firebase = config('Firebase');
        // Pastikan konfigurasi 'config' di Firebase.php ada dan berisi data yang valid.
        // Jika 'config' adalah array, json_encode akan mengubahnya menjadi string JSON.
        // Jika Anda hanya ingin melewatkan properti langsung, bisa seperti ini:
        // $this->firebaseConfig = json_encode([
        //     'apiKey'            => $firebase->apiKey,
        //     'authDomain'        => $firebase->authDomain,
        //     'projectId'         => $firebase->projectId,
        //     'storageBucket'     => $firebase->storageBucket,
        //     'messagingSenderId' => $firebase->messagingSenderId,
        //     'appId'             => $firebase->appId,
        //     'measurementId'     => $firebase->measurementId
        // ]);
        // Namun, jika Anda punya array 'config' di Firebase.php:
        $this->firebaseConfig = json_encode($firebase->config); // Encode ke JSON untuk JavaScript
    }

    /**
     * Menampilkan halaman login.
     */
    public function login()
    {
        // Periksa dengan kunci sesi yang konsisten: 'isLoggedIn'
        if (session()->get('isLoggedIn')) {
            return redirect()->to(base_url('/'));
        }
        $data = [
            'title' => 'Login ke Insep',
            'firebaseConfig' => $this->firebaseConfig // Lewatkan konfigurasi ke view
        ];
        return view('auth/login', $data); // Memuat view: app/Views/auth/login.php
    }

    /**
     * Menampilkan halaman register.
     */
    public function register()
    {
        // Periksa dengan kunci sesi yang konsisten: 'isLoggedIn'
        if (session()->get('isLoggedIn')) {
            return redirect()->to(base_url('/'));
        }
        $data = [
            'title' => 'Daftar Akun Baru',
            'firebaseConfig' => $this->firebaseConfig // Lewatkan konfigurasi ke view
        ];
        return view('auth/register', $data); // Memuat view: app/Views/auth/register.php
    }

    /**
     * Endpoint untuk menangani proses post-login dari client (setelah Firebase berhasil login).
     * Menerima data dari JavaScript setelah login Firebase sukses, lalu mengatur sesi.
     */
    public function setSession()
    {
        $input = $this->request->getJSON(true); // Ambil data JSON dari request

        // Validasi data yang diterima
        if (empty($input['uid']) || empty($input['email'])) {
            return $this->failValidationError('Data tidak lengkap (UID atau Email kosong).');
        }

        // Dapatkan displayName dari Firebase (jika ada)
        $usernameFromFirebase = $input['displayName'] ?? '';
        $emailPrefix = explode('@', $input['email'])[0];

        // Tentukan username prioritas: displayName > bagian email > "Pengguna Baru"
        $finalUsername = !empty($usernameFromFirebase) ? $usernameFromFirebase : $emailPrefix;
        if (empty($finalUsername)) { // Jika masih kosong (misal email aneh atau tidak ada)
            $finalUsername = 'Pengguna Baru'; // Default yang lebih baik
        }

        // Ambil photoURL dari Firebase Auth
        $profileImageUrl = $input['photoURL'] ?? 'https://via.placeholder.com/40';

        // Simpan data pengguna ke sesi CodeIgniter dengan kunci yang konsisten
        session()->set([
            'isLoggedIn'  => true,
            'user_id'     => $input['uid'],
            'email'       => $input['email'],
            'username'    => $finalUsername,
            'user_avatar' => $profileImageUrl // <--- PERBAIKAN DI SINI
        ]);

        log_message('info', 'Sesi diatur untuk UID: ' . $input['uid'] . ' Username: ' . $finalUsername);


        // Opsional: Simpan informasi tambahan tentang pengguna di Firebase Realtime Database
        // Ini memastikan data profil pengguna (termasuk username dan avatar) tersimpan secara permanen
        // Ini perlu Library FirebaseService, jadi kita panggil dia di sini.
        try {
            // Pastikan FirebaseService menginisialisasi Firebase Admin SDK dengan benar
            $firebaseService = new \App\Libraries\FirebaseService();
            $usersRef = $firebaseService->getUsersRef();

            // Cek apakah user sudah ada di database 'users'
            $existingUser = $usersRef->getChild($input['uid'])->getValue();

            // Tangkap device info
            $userAgent = $this->request->getUserAgent();
            $deviceInfo = [
                'Platform' => $userAgent->getPlatform(),
                'Browser'  => $userAgent->getBrowser(),
                'Version'  => $userAgent->getVersion(),
                'Mobile'   => $userAgent->isMobile() ? $userAgent->getMobile() : 'N/A',
                'Robot'    => $userAgent->isRobot() ? $userAgent->getRobot() : 'N/A',
            ];
            // Jika Anda ingin mengambil info Android yang lebih spesifik dari aplikasi mobile,
            // Anda perlu mengirimkannya dari aplikasi mobile ke endpoint ini.
            // Contoh: $input['device_info'] jika dikirim dari aplikasi mobile

            $userDataToSave = [
                'email'        => $input['email'],
                'username'     => $finalUsername,
                'profileImage' => $profileImageUrl, // Gunakan URL yang sama dengan yang disimpan di sesi
                'lastLogin'    => date('Y-m-d H:i:s'),
                'device_info'  => $deviceInfo // Simpan info device yang lebih detail
            ];

            if ($existingUser) {
                // Jika user sudah ada, update data yang relevan
                // Pastikan tidak menimpa data yang mungkin hanya ada di Realtime DB (misal data tambahan dari admin)
                // Gabungkan data yang ada dengan data yang akan diupdate
                $mergedData = array_merge($existingUser, $userDataToSave);
                $usersRef->getChild($input['uid'])->update($mergedData);
                log_message('info', 'Data user Firebase diperbarui untuk UID: ' . $input['uid']);
            } else {
                // Jika user baru, set data baru
                $usersRef->getChild($input['uid'])->set($userDataToSave);
                log_message('info', 'User baru ditambahkan ke Firebase: ' . $input['uid'] . ' Username: ' . $finalUsername);
            }
        } catch (\Exception $e) {
            log_message('error', 'Gagal menyimpan/memperbarui data user di Firebase Realtime Database (Auth::setSession): ' . $e->getMessage());
            // Lanjutkan proses meskipun ada error Firebase karena sesi CodeIgniter sudah diatur
        }
        // --- Akhir Integrasi Opsional ---


        // Ambil URL yang ingin di-redirect setelah login, jika ada
        $redirectUrl = session()->getFlashdata('redirect_url') ?? base_url('/');

        return $this->respond([
            'status'      => 'success',
            'message'     => 'Sesi berhasil diatur.',
            'redirect_to' => $redirectUrl // Kirim URL redirect ke frontend
        ]);
    }


    /**
     * Fungsi untuk logout.
     * Menghancurkan sesi CodeIgniter dan redirect ke halaman login.
     */
    public function logout()
    {
        $username = session()->get('username');
        session()->destroy(); // Hapus semua sesi
        log_message('info', 'Pengguna ' . ($username ?? 'Anonim') . ' telah logout.');
        return redirect()->to('/login')->with('success', 'Anda telah berhasil logout.'); // Arahkan ke halaman login
    }
}