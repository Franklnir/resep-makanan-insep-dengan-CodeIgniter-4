<?php namespace App\Controllers;

use CodeIgniter\Controller;
use App\Libraries\FirebaseService;


class Riwayat extends Controller
{
    protected $firebaseService;

    public function __construct()
    {
        $this->firebaseService = new FirebaseService();
        helper(['session', 'form']); // Add 'form' helper for form validation
       $this->session = \Config\Services::session();}


    /**
     * Halaman riwayat resep user.
     * Dapat menampilkan riwayat berdasarkan username dari URL,
     * atau riwayat pengguna yang sedang login jika tidak ada username di URL.
     *
     * @param string|null $username Nama pengguna yang resepnya ingin dilihat.
     */
    public function index($username = null)
    {
        $loggedInUsername = session()->get('username') ?? 'Anonim';

        // Jika tidak ada username di URL, dan pengguna belum login, arahkan ke login
        if ($username === null && !session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }

        // Tentukan username yang akan digunakan untuk mencari resep
        $targetUsername = $username ?? $loggedInUsername;

        // Ambil data resep dari Firebase Realtime Database
        $recipesRef = $this->firebaseService->getRecipesRef();
        $recipes = $recipesRef->getValue();

        $userRecipes = [];
        if (!empty($recipes)) {
            foreach ($recipes as $id => $recipe) {
                // Filter resep berdasarkan author (username) yang ditargetkan
                if (isset($recipe['author']) && $recipe['author'] === $targetUsername) {
                    $recipe['id'] = $id; // Tambahkan ID resep dari Firebase
                    $userRecipes[] = $recipe;
                }
            }
        }

        // Ambil detail user yang ditargetkan (untuk menampilkan profil)
        $usersRef = $this->firebaseService->getUsersRef();
        $allUsersData = $usersRef->getValue();
        $currentUserData = null;

        if (!empty($allUsersData)) {
            foreach ($allUsersData as $userId => $userData) {
                if (isset($userData['username']) && $userData['username'] === $targetUsername) {
                    $currentUserData = $userData;
                    break;
                }
            }
        }

        $data = [
            'recipes' => $userRecipes,
            'username' => $targetUsername, // Username yang sedang ditampilkan riwayatnya
            'userProfile' => $currentUserData, // Detail profil user untuk tampilan
            'session_username' => $loggedInUsername, // Username pengguna yang login untuk navbar
            'session_avatar' => session()->get('user_avatar', 'https://via.placeholder.com/40') // Avatar pengguna yang login untuk navbar
        ];

        return view('riwayat/index', $data);
    }

    /**
     * Menampilkan formulir edit resep atau memproses pengiriman formulir edit.
     *
     * @param string $recipeId ID resep yang akan diedit.
     */
  public function edit_recipe(string $recipeId)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        $loggedInUsername = session()->get('username');
        $recipesRef = $this->firebaseService->getRecipesRef()->getChild($recipeId);
        $snapshot = $recipesRef->getSnapshot();
        $recipe = $snapshot->getValue();

        if (!$recipe || ($recipe['author'] ?? '') !== $loggedInUsername) {
            return redirect()->to('/riwayat/' . $loggedInUsername)
                             ->with('error', 'Resep tidak ditemukan atau bukan milik Anda.');
        }

        if ($this->request->getMethod() === 'post') {
            $validationRules = [
                'name'        => 'required|min_length[3]|max_length[255]',
                'description' => 'required|min_length[10]',
                'ingredients' => 'required',
                'instructions'=> 'required',
                'category'    => 'required',
                'image'       => 'permit_empty|is_image[image]|max_size[image,2048]|ext_in[image,jpg,jpeg,png,gif]',
                'imageUrl'    => 'permit_empty|valid_url'
            ];

            if (!$this->validate($validationRules)) {
                return redirect()->back()
                                 ->withInput()
                                 ->with('errors', $this->validator->getErrors());
            }

            $updatedData = [
                'name'        => $this->request->getPost('name'),
                'description' => $this->request->getPost('description'),
                'ingredients' => array_values(array_filter(preg_split('/\r?\n/', $this->request->getPost('ingredients')))),
                'steps'       => array_values(array_filter(preg_split('/\r?\n/', $this->request->getPost('instructions')))),
                'category'    => $this->request->getPost('category'),
                'timestamp'   => time(),
            ];

            // Gambar baru
            $imageFile = $this->request->getFile('image');
            if ($imageFile && $imageFile->isValid() && !$imageFile->hasMoved()) {
                $uploadPath = WRITEPATH . 'uploads/';
                if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);

                $filename = $imageFile->getRandomName();
                $tempPath = $uploadPath . $filename;
                $imageFile->move($uploadPath, $filename);

                $compressed = $this->compressImage($tempPath);
                $uploadedUrl = $this->uploadToSupabase($compressed, $filename);

                // Hapus file sementara
                if (file_exists($tempPath)) unlink($tempPath);
                if ($compressed !== $tempPath && file_exists($compressed)) unlink($compressed);

                if ($uploadedUrl) $updatedData['imageUrl'] = $uploadedUrl;
            } else {
                // Tidak ada upload: ambil dari input URL atau tetap gambar lama
                $inputImg = $this->request->getPost('imageUrl');
                $updatedData['imageUrl'] = $inputImg
                    ?: ($recipe['imageUrl'] ?? base_url('assets/img/default_recipe.png'));
            }

            try {
                $recipesRef->update($updatedData);
                log_message('info', "Resep {$recipeId} diperbarui oleh {$loggedInUsername}");
                return redirect()->to('/riwayat/' . $loggedInUsername)
                                 ->with('success', 'Resep berhasil diperbarui.');
            } catch (\Exception $e) {
                log_message('error', 'Update gagal: ' . $e->getMessage());
                return redirect()->back()
                                 ->withInput()
                                 ->with('error', 'Gagal memperbarui resep: ' . $e->getMessage());
            }
        }

        return view('/edit_recipe', [
            'recipe'   => $recipe,
            'recipeId' => $recipeId
        ]);
    }


    public function delete($recipeId)
    {
        // Pastikan pengguna sudah login
        if (!session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'))->with('error', 'Anda harus login untuk menghapus resep.');
        }

        $recipesRef = $this->firebaseService->getRecipesRef();
        $recipe = $recipesRef->getChild($recipeId)->getValue();

        // Periksa apakah resep ada
        if (!$recipe) {
            return redirect()->to(base_url('riwayat/' . session()->get('username')))->with('error', 'Resep tidak ditemukan.');
        }

        // Periksa apakah pengguna yang login adalah pemilik resep
        if (!isset($recipe['author']) || session()->get('username') !== $recipe['author']) {
            return redirect()->to(base_url('riwayat/' . session()->get('username')))->with('error', 'Anda tidak memiliki izin untuk menghapus resep ini.');
        }

        try {
            $recipesRef->getChild($recipeId)->remove();
            return redirect()->to(base_url('riwayat/' . session()->get('username')))->with('success', 'Resep berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->to(base_url('riwayat/' . session()->get('username')))->with('error', 'Gagal menghapus resep: ' . $e->getMessage());
        }
    }






    private function compressImage(string $filePath, int $quality = 85): string
{
    log_message('debug', 'Memulai kompresi untuk file: ' . $filePath);

    if (!file_exists($filePath)) return $filePath;

    try {
        $imageService = \Config\Services::image();
        $imageInstance = $imageService->withFile($filePath);

        $compressedPath = $filePath . '_compressed.jpg';

        $imageInstance
            ->convert(IMAGETYPE_JPEG)
            ->quality($quality)
            ->save($compressedPath);

        log_message('info', 'Gambar berhasil dikompresi ke: ' . $compressedPath);
        return $compressedPath;

    } catch (\Exception $e) {
        log_message('error', 'Gagal mengkompresi gambar: ' . $e->getMessage());
        return $filePath;
    }
}

private function uploadToSupabase(string $filePath, string $filename): ?string
{
    $firebaseConfig = config('Firebase');
    $supabaseStorageUrl = $firebaseConfig->supabaseStorageUrl;
    $supabaseBucket     = $firebaseConfig->supabaseBucket;
    $supabaseApiKey     = $firebaseConfig->supabaseApiKey;

    $headers = [
        "apikey: {$supabaseApiKey}",
        "Authorization: Bearer {$supabaseApiKey}",
        "Content-Type: application/octet-stream"
    ];

    $uploadUrl = "{$supabaseStorageUrl}/{$supabaseBucket}/{$filename}";
    $ch = curl_init($uploadUrl);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => "PUT",
        CURLOPT_POSTFIELDS     => file_get_contents($filePath),
        CURLOPT_HTTPHEADER     => $headers,
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode === 200 || $httpcode === 201) {
        return "{$supabaseStorageUrl}/public/{$supabaseBucket}/{$filename}";
    }

    log_message('error', 'Gagal unggah ke Supabase. HTTP Code: ' . $httpcode . ' - Response: ' . $response);
    return null;
}

}
