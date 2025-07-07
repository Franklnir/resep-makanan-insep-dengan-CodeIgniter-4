<?php namespace App\Libraries;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;
use Kreait\Firebase\Auth;
use Config\Firebase as FirebaseConfig; // Menggunakan alias untuk menghindari konflik nama

class FirebaseService
{
    protected Database $database;
    protected Auth $auth; // Properti untuk Firebase Authentication Admin SDK
    protected FirebaseConfig $config; // Menggunakan alias di sini

    public function __construct()
    {
        $this->config = config('Firebase'); // Muat konfigurasi CodeIgniter secara standar

        try {
            $factory = (new Factory())
                ->withServiceAccount($this->config->serviceAccountPath)
                ->withDatabaseUri($this->config->databaseUrl);

            $this->database = $factory->createDatabase();
            $this->auth = $factory->createAuth(); // Inisialisasi Firebase Auth Admin SDK
        } catch (\Exception $e) {
            log_message('error', 'Firebase initialization error: ' . $e->getMessage());
            throw new \RuntimeException("Gagal menginisialisasi Firebase Service: " . $e->getMessage());
        }
    }

    /**
     * Mengembalikan instance Firebase Realtime Database.
     * @return Database
     */
    public function getDatabase(): Database
    {
        return $this->database;
    }

    /**
     * Mengembalikan instance Firebase Authentication (Admin SDK).
     * @return Auth
     */
    public function getAuth(): Auth
    {
        return $this->auth;
    }

    /**
     * Mengembalikan referensi ke node 'recipes'.
     * @return \Kreait\Firebase\Database\Reference
     */
    public function getRecipesRef(): \Kreait\Firebase\Database\Reference
    {
        return $this->database->getReference('recipes');
    }

    /**
     * Mengembalikan referensi ke node 'users'.
     * @return \Kreait\Firebase\Database\Reference
     */
    public function getUsersRef(): \Kreait\Firebase\Database\Reference
    {
        return $this->database->getReference('users');
    }

    /**
     * Mengambil resep berdasarkan ID.
     * Mengisi ID resep ke dalam data yang dikembalikan.
     * @param string $recipeId
     * @return array|null Data resep atau null jika tidak ditemukan.
     */
    public function getRecipeById(string $recipeId): ?array
    {
        $recipeData = $this->database->getReference('recipes/' . $recipeId)->getValue();
        if (!empty($recipeData)) {
            $recipeData['id'] = $recipeId; // Inject ID ke dalam array data resep
            return $recipeData;
        }
        return null;
    }

    /**
     * Menghapus resep berdasarkan ID.
     * @param string $recipeId
     * @return void
     */
    public function deleteRecipe(string $recipeId): void
    {
        $this->database->getReference('recipes/' . $recipeId)->remove();
    }

    /**
     * Menambahkan resep baru ke database.
     * Menggunakan push() untuk ID otomatis, lalu menyertakan ID dalam data.
     * @param array $data Data resep (name, description, ingredients, etc.)
     * @return array Data resep yang telah disimpan, termasuk ID yang dibuat.
     */
    public function addRecipe(array $data): array
    {
        $newRecipeRef = $this->database->getReference('recipes')->push();
        $recipeId = $newRecipeRef->getKey();
        $data['id'] = $recipeId; // Tambahkan ID ke dalam data yang akan disimpan

        $newRecipeRef->set($data); // Simpan data resep
        return $data; // Kembalikan data yang sudah disimpan, termasuk ID
    }

    /**
     * Memperbarui data resep yang sudah ada.
     * @param string $recipeId ID resep yang akan diperbarui.
     * @param array $data Data yang akan diperbarui (hanya field yang ingin diubah).
     * @return void
     */
    public function updateRecipe(string $recipeId, array $data): void
    {
        $this->database->getReference('recipes/' . $recipeId)->update($data);
    }

    /**
     * Mengambil data pengguna berdasarkan username.
     * Berguna untuk mencari profil pengguna dan menyertakan userId.
     * @param string $username
     * @return array|null Data pengguna atau null jika tidak ditemukan.
     */
    public function getUserByUsername(string $username): ?array
    {
        // Untuk efisiensi, pertimbangkan untuk menggunakan query Firebase jika jumlah user sangat besar
        // Namun, iterasi lokal cocok untuk jumlah user yang lebih kecil.
        $users = $this->getUsersRef()->getValue();
        if (empty($users)) {
            return null;
        }

        foreach ($users as $userId => $userData) {
            if (isset($userData['username']) && $userData['username'] === $username) {
                $userData['userId'] = $userId; // Tambahkan userId ke data
                return $userData;
            }
        }
        return null;
    }

    /**
     * Mengambil konfigurasi Supabase Storage.
     * @return array
     */
    public function getSupabaseConfig(): array
    {
        return [
            'url' => $this->config->supabaseStorageUrl ?? null,
            'bucket' => $this->config->supabaseBucket ?? null,
            'key' => $this->config->supabaseApiKey ?? null,
        ];
    }

    /**
     * Mengunggah file ke Supabase Storage menggunakan CodeIgniter's CurlRequest.
     * Catatan: Ini adalah versi yang disarankan untuk digunakan di helper atau langsung di controller.
     * @param string $filePath Path file lokal yang akan diupload.
     * @param string $fileName Nama file di Supabase.
     * @return string|null URL file yang diupload atau null jika gagal.
     */
    public function uploadFileToSupabase(string $filePath, string $fileName): ?string
    {
        $supabaseConfig = $this->getSupabaseConfig();

        if (empty($supabaseConfig['url']) || empty($supabaseConfig['key'])) {
            log_message('error', 'Supabase configuration is missing for file upload in FirebaseService.');
            return null;
        }

        $supabaseStorageUrlBase = rtrim($supabaseConfig['url'], '/'); // Base URL hingga /v1/object
        $supabaseBucket = $supabaseConfig['bucket'];
        $supabaseApiKey = $supabaseConfig['key'];

        // URL target untuk PUT operation di Supabase Storage
        // Format: [STORAGE_URL_BASE]/[BUCKET_NAME]/[FILE_NAME]
        $targetUrl = "{$supabaseStorageUrlBase}/{$supabaseBucket}/{$fileName}";

        try {
            $client = Services::curlrequest(); // Gunakan CodeIgniter's CurlRequest Service

            if (!file_exists($filePath) || !is_readable($filePath)) {
                log_message('error', 'File untuk upload Supabase tidak ditemukan atau tidak dapat dibaca: ' . $filePath);
                return null;
            }

            $response = $client->setHeaders([
                'apikey' => $supabaseApiKey,
                'Authorization' => "Bearer {$supabaseApiKey}",
                'Content-Type' => mime_content_type($filePath) ?: 'application/octet-stream', // Fallback jika mime_content_type gagal
                'x-upsert' => 'true' // Untuk menimpa jika nama file sudah ada
            ])->put($targetUrl, [
                'body' => fopen($filePath, 'r'), // Mengirim konten file sebagai stream
                'timeout' => 30 // Tambahkan timeout
            ]);

            if ($response->getStatusCode() === 200) {
                // URL publik Supabase untuk akses file
                return "{$supabaseStorageUrlBase}/public/{$supabaseBucket}/{$fileName}";
            } else {
                $errorBody = $response->getBody();
                log_message('error', 'Supabase upload failed (status ' . $response->getStatusCode() . '): ' . $errorBody);
                return null;
            }
        } catch (\CodeIgniter\HTTP\ClientException $e) {
            log_message('error', 'Supabase network error (CurlRequest): ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            log_message('error', 'Supabase upload exception (general): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Menghapus file dari Supabase Storage.
     * @param string $fileName Nama file di Supabase.
     * @return bool True jika berhasil, false jika gagal.
     */
    public function deleteFileFromSupabase(string $fileName): bool
    {
        $supabaseConfig = $this->getSupabaseConfig();

        if (empty($supabaseConfig['url']) || empty($supabaseConfig['key'])) {
            log_message('error', 'Supabase configuration is missing for file deletion.');
            return false;
        }

        $supabaseStorageUrlBase = rtrim($supabaseConfig['url'], '/');
        $supabaseBucket = $supabaseConfig['bucket'];
        $supabaseApiKey = $supabaseConfig['key'];

        // URL target untuk DELETE operation di Supabase Storage
        $targetUrl = "{$supabaseStorageUrlBase}/{$supabaseBucket}/{$fileName}";

        try {
            $client = Services::curlrequest();

            $response = $client->setHeaders([
                'apikey' => $supabaseApiKey,
                'Authorization' => "Bearer {$supabaseApiKey}",
            ])->delete($targetUrl, [
                'timeout' => 30 // Tambahkan timeout
            ]);

            if ($response->getStatusCode() === 204) { // 204 No Content adalah status sukses untuk DELETE
                log_message('info', 'Gambar berhasil dihapus dari Supabase: ' . $fileName);
                return true;
            } else {
                $errorBody = $response->getBody();
                log_message('error', 'Supabase delete failed (status ' . $response->getStatusCode() . '): ' . $errorBody);
                return false;
            }
        } catch (\CodeIgniter\HTTP\ClientException $e) {
            log_message('error', 'Supabase delete network error: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            log_message('error', 'Supabase delete exception (general): ' . $e->getMessage());
            return false;
        }
    }
}