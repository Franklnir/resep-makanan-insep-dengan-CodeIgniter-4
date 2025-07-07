<?php namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Services;
use Config\Firebase as FirebaseConfig;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;

class Upload extends Controller
{
    protected $session;
    protected Database $firebaseDatabase;
    protected FirebaseConfig $firebaseConfig;

    protected string $supabaseStorageUrl;
    protected string $supabaseBucket;
    protected string $supabaseApiKey;

    public function __construct()
    {
        $this->session = Services::session();
        $this->firebaseConfig = config('Firebase');

        $this->supabaseStorageUrl = $this->firebaseConfig->supabaseStorageUrl;
        $this->supabaseBucket = $this->firebaseConfig->supabaseBucket;
        $this->supabaseApiKey = $this->firebaseConfig->supabaseApiKey;

        try {
            $factory = (new Factory)
                ->withServiceAccount($this->firebaseConfig->serviceAccountPath)
                ->withDatabaseUri($this->firebaseConfig->databaseUrl);

            $this->firebaseDatabase = $factory->createDatabase();

        } catch (\Exception $e) {
            log_message('error', 'Gagal menginisialisasi Firebase di Upload: ' . $e->getMessage());
            if (! $this->request->isAJAX()) {
                // Jangan langsung setFlashdata error jika ini fatal, agar tidak menumpuk
                // Ini akan ditangani oleh redirect yang dilakukan jika firebaseDatabase tidak terinisialisasi
            }
        }
    }

   // app/Controllers/Upload.php
public function index()
{
    // Cek ini dengan sangat hati-hati
    if (!$this->session->get('isLoggedIn')) {
        // Ini seharusnya tidak terpanggil jika Anda sudah login dan sesi bekerja di halaman lain.
        // Jika ini terpanggil, berarti sesi 'isLoggedIn' hilang/false di sini.
        log_message('error', 'Upload::index - Pengguna TIDAK LOGIN atau sesi isLoggedIn hilang.');
        return redirect()->to('/login')->with('error', 'Anda harus login untuk mengunggah resep.');
    }

    // --- Tambahkan logging spesifik ini ---
    log_message('debug', 'Upload::index - Sesi saat memuat halaman Upload: ' . json_encode($this->session->get()));
    // --- Akhir logging ---

    $session_avatar = $this->session->get('user_avatar') ?? base_url('assets/img/default_avatar.png');
    $session_username = $this->session->get('username') ?? 'Guest'; // Fokus di sini

    log_message('debug', 'Upload::index - session_username yang diambil: ' . $session_username);
    log_message('debug', 'Upload::index - session_avatar yang diambil: ' . $session_avatar);

    return view('upload_form', [
        'session_avatar'   => $session_avatar,
        'session_username' => $session_username,
        'errors'           => session()->getFlashdata('errors'),
        'success'          => session()->getFlashdata('success'),
        'error'            => session()->getFlashdata('error'),
        'old'              => session()->getOldInput(),
    ]);
}

    public function submit()
    {
        helper(['form', 'url']);

        if (!isset($this->firebaseDatabase)) {
            session()->setFlashdata('error', 'Koneksi Firebase tidak terjalin. Tidak dapat mengunggah resep.');
            return redirect()->to('/upload');
        }
        if (!$this->session->get('isLoggedIn')) {
            return redirect()->to('/login')->with('error', 'Anda harus login untuk mengunggah resep.');
        }

        $validationRules = [
            'name'        => 'required|min_length[3]|max_length[255]',
            'description' => 'required|min_length[10]',
            'category'    => 'required',
            'ingredients' => 'required',
            'steps'       => 'required',
            'videoUrl'    => 'permit_empty|valid_url',
            'image'       => 'permit_empty|is_image[image]|max_size[image,2048]|ext_in[image,jpg,jpeg,png,gif]'
        ];

        if (!$this->validate($validationRules)) {
            session()->setFlashdata('errors', $this->validator->getErrors());
            return redirect()->back()->withInput();
        }

        $imageFile = $this->request->getFile('image');
        $uploadedImageUrl = null;

        if ($imageFile && $imageFile->isValid() && !$imageFile->hasMoved()) {
            $uploadPath = WRITEPATH . 'uploads/';
            if (!is_dir($uploadPath)) {
                if (!mkdir($uploadPath, 0755, true)) {
                    log_message('error', 'Gagal membuat direktori unggahan: ' . $uploadPath);
                    session()->setFlashdata('error', 'Gagal menyiapkan direktori unggahan.');
                    return redirect()->back()->withInput();
                }
            }

            $filename = $imageFile->getRandomName();
            $tempPath = $uploadPath . $filename;

            try {
                if ($imageFile->move($uploadPath, $filename)) {
                    log_message('info', 'File gambar berhasil dipindahkan sementara ke: ' . $tempPath);

                    if (file_exists($tempPath)) {
                        log_message('debug', 'Memulai kompresi untuk file: ' . $tempPath);
                        $compressedPath = $this->compressImage($tempPath);
                        log_message('debug', 'Kompresi selesai, jalur terkompresi: ' . $compressedPath);

                        if ($compressedPath === $tempPath) {
                            log_message('warning', 'Kompresi gambar gagal, mengunggah file asli: ' . $tempPath);
                        }

                        $uploadedImageUrl = $this->uploadToSupabase($compressedPath, $filename);
                        log_message('debug', 'Unggah Supabase selesai, URL: ' . ($uploadedImageUrl ?? 'NULL'));

                        if (file_exists($tempPath)) {
                            unlink($tempPath);
                            log_message('info', 'File asli dihapus: ' . $tempPath);
                        }
                        if ($compressedPath !== $tempPath && file_exists($compressedPath)) {
                            unlink($compressedPath);
                            log_message('info', 'File terkompresi dihapus: ' . $compressedPath);
                        }
                    } else {
                        log_message('error', 'File sementara tidak ditemukan setelah dipindahkan: ' . $tempPath);
                        session()->setFlashdata('error', 'Gagal memproses gambar: File sementara tidak ditemukan.');
                    }
                } else {
                    $error = $imageFile->getErrorString() . ' (' . $imageFile->getError() . ')';
                    log_message('error', 'Gagal memindahkan file gambar: ' . $error);
                    session()->setFlashdata('error', 'Gagal menyimpan gambar sementara. Pesan: ' . $error);
                }
            } catch (\Exception $e) {
                log_message('error', 'Kesalahan umum saat pemrosesan gambar: ' . $e->getMessage());
                session()->setFlashdata('error', 'Terjadi kesalahan saat memproses gambar.');
            }
        }

        if (!$uploadedImageUrl) {
            // Gunakan placeholder default jika tidak ada gambar diunggah atau gagal
            $uploadedImageUrl = base_url('assets/img/default_recipe.png');
            log_message('info', 'Menggunakan gambar resep default: ' . $uploadedImageUrl);
        }

        // --- Integrasi Firebase Realtime Database ---
        try {
            $recipeId = uniqid('recipe_');
            $username = $this->session->get('username'); // Ambil langsung dari sesi
            $userId = $this->session->get('user_id');   // Ambil langsung dari sesi

            // Pastikan username dan userId ada. Ini akan selalu ada jika isLoggedIn true
            if ($username === null || $userId === null) {
                log_message('error', 'Username atau User ID sesi tidak ditemukan saat mencoba menyimpan resep.');
                session()->setFlashdata('error', 'Kesalahan sesi. Silakan coba login ulang.');
                return redirect()->to('/login');
            }

            $data = [
                'id'            => $recipeId,
                'name'          => $this->request->getPost('name'),
                'description'   => $this->request->getPost('description'),
                'category'      => $this->request->getPost('category'),
                'ingredients'   => array_values(array_filter(explode("\n", $this->request->getPost('ingredients')))),
                'steps'         => array_values(array_filter(explode("\n", $this->request->getPost('steps')))),
                'videoUrl'      => $this->request->getPost('videoUrl'),
                'imageUrl'      => $uploadedImageUrl,
                'author'        => $username, // Diambil dari sesi
                'author_id'     => $userId,   // Diambil dari sesi
                'timestamp'     => time()
            ];

            $this->firebaseDatabase->getReference('recipes/' . $recipeId)->set($data);

            session()->setFlashdata('success', 'Resep berhasil diunggah ke Firebase!');
            log_message('info', 'Resep berhasil diunggah ke Firebase dengan ID: ' . $recipeId);

        } catch (\Exception $e) {
            log_message('error', 'Gagal menyimpan resep ke Firebase: ' . $e->getMessage());
            session()->setFlashdata('error', 'Gagal menyimpan resep ke Firebase.');
        }

        return redirect()->to('/upload');
    }

    private function uploadToSupabase(string $filePath, string $filename): ?string
    {
        $headers = [
            "apikey: {$this->supabaseApiKey}",
            "Authorization: Bearer {$this->supabaseApiKey}",
            "Content-Type: application/octet-stream"
        ];

        $uploadUrl = "{$this->supabaseStorageUrl}/{$this->supabaseBucket}/{$filename}";
        log_message('debug', 'Mencoba mengunggah ke Supabase URL: ' . $uploadUrl);

        $ch = curl_init($uploadUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");

        if (!file_exists($filePath) || !is_readable($filePath)) {
            log_message('error', 'File tidak ditemukan atau tidak dapat dibaca untuk diunggah ke Supabase: ' . $filePath);
            return null;
        }
        $fileContents = file_get_contents($filePath);
        if ($fileContents === false) {
             log_message('error', 'Gagal membaca konten file untuk diunggah ke Supabase: ' . $filePath);
             return null;
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContents);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            log_message('error', 'Kesalahan cURL saat unggah ke Supabase: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($httpcode === 200 || $httpcode === 201) {
            log_message('info', 'Unggahan ke Supabase berhasil: ' . $filename);
            return "{$this->supabaseStorageUrl}/public/{$this->supabaseBucket}/{$filename}";
        }

        log_message('error', "Unggah ke Supabase gagal, kode HTTP: $httpcode, respons: " . ($response ?: 'Tidak ada respons') . ", File: " . $filename);
        return null;
    }

    private function compressImage(string $filePath, int $quality = 85): string
    {
        log_message('debug', 'Memulai kompresi untuk file: ' . $filePath);

        if (!file_exists($filePath)) {
            log_message('error', 'File sumber untuk kompresi TIDAK DITEMUKAN: ' . $filePath);
            return $filePath;
        }
        if (!is_readable($filePath)) {
            log_message('error', 'File sumber untuk kompresi TIDAK DAPAT DIBACA: ' . $filePath);
            return $filePath;
        }

        try {
            $imageService = Services::image();

            if ($imageService === null) {
                log_message('error', 'Layanan gambar CodeIgniter mengembalikan NULL. Periksa konfigurasi PHP GD/Imagick.');
                return $filePath;
            }

            $imageInstance = $imageService->withFile($filePath);

            if ($imageInstance === false) {
                 log_message('error', 'withFile() gagal memuat gambar, kemungkinan file tidak valid atau korup: ' . $filePath);
                 return $filePath;
            }

            log_message('debug', 'Objek $imageInstance setelah withFile(): ' . get_class($imageInstance));

            $compressedPath = $filePath . '_compressed.jpg';
            log_message('debug', 'Jalur output kompresi: ' . $compressedPath);

            if ($imageInstance instanceof \CodeIgniter\Images\Handlers\BaseHandler) {

                $convertedInstance = $imageInstance->convert(IMAGETYPE_JPEG);
                if ($convertedInstance === null) {
                    log_message('error', 'convert(IMAGETYPE_JPEG) mengembalikan NULL. Masalah dengan GD/Imagick atau format gambar.');
                    return $filePath;
                }
                log_message('debug', 'Setelah convert(): ' . get_class($convertedInstance));

                $qualityInstance = $convertedInstance->quality($quality);
                if ($qualityInstance === null) {
                    log_message('error', 'quality() mengembalikan NULL. Masalah dengan GD/Imagick.');
                    return $filePath;
                }
                log_message('debug', 'Setelah quality(): ' . get_class($qualityInstance));

                $qualityInstance->save($compressedPath);

                log_message('info', 'Gambar berhasil dikompresi ke: ' . $compressedPath);
                return $compressedPath;
            } else {
                log_message('error', 'Objek imageInstance bukan handler gambar yang valid setelah withFile(). Tipe: ' . gettype($imageInstance));
                return $filePath;
            }

        } catch (\Exception $e) {
            log_message('error', 'Gagal mengkompresi gambar (Exception): ' . $e->getMessage() . ' pada file: ' . $filePath);
            return $filePath;
        }
    }
}