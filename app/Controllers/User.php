<?php namespace App\Controllers;

use App\Libraries\FirebaseService;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RedirectResponse;

class User extends Controller
{
    protected $firebaseService;

    public function __construct()
    {
        $this->firebaseService = new FirebaseService();
        helper(['url', 'form', 'session']);
    }

    /**
     * Menampilkan halaman riwayat resep milik user berdasarkan username.
     */
    public function riwayat(string $username)
    {
        $recipes = $this->firebaseService->getRecipesRef()->getValue() ?? [];
        $userRecipes = [];

        foreach ($recipes as $id => $recipe) {
            if (isset($recipe['author']) && $recipe['author'] === $username) {
                $recipe['id'] = $id;
                $userRecipes[] = $recipe;
            }
        }

        $users = $this->firebaseService->getUsersRef()->getValue() ?? [];
        $userProfile = null;

        foreach ($users as $uid => $data) {
            if (!empty($data['username']) && $data['username'] === $username) {
                $userProfile = array_merge($data, ['id' => $uid]);
                break;
            }
        }

        return view('riwayat/index', [
            'recipes'          => $userRecipes,
            'username'         => $username,
            'userProfile'      => $userProfile,
            'session_username' => session()->get('username', 'Anonim'),
            'session_avatar'   => session()->get('user_avatar', 'https://via.placeholder.com/40'),
        ]);
    }

    /**
     * Menampilkan daftar semua user.
     */
    public function listUsers()
    {
        $users = $this->firebaseService->getUsersRef()->getValue() ?? [];
        $authors = [];

        foreach ($users as $userId => $user) {
            if (!empty($user['username'])) {
                $authors[] = [
                    'userId'       => $userId,
                    'username'     => $user['username'],
                    'email'        => $user['email'] ?? '',
                    'nomorHp'      => $user['nomorHp'] ?? '',
                    'profileImage' => $user['profileImage'] ?? null,
                    'device_info'  => $user['device_info'] ?? '',
                ];
            }
        }

        usort($authors, fn($a, $b) => strcmp($a['username'], $b['username']));

        return view('user_list', [
            'authors'          => $authors,
            'session_username' => session()->get('username', 'Anonim'),
            'session_avatar'   => session()->get('user_avatar', 'https://via.placeholder.com/40'),
        ]);
    }

    /**
     * Menghapus resep berdasarkan ID.
     * Hanya user yang login dan merupakan author yang dapat menghapus.
     */
    public function delete(string $recipeId): RedirectResponse
    {
        $session = session();
        $loggedIn = $session->get('isLoggedIn');
        $currentUsername = $session->get('username');

        if (!$loggedIn || !$currentUsername) {
            $session->setFlashdata('error', 'Anda harus login untuk menghapus resep.');
            return redirect()->to('/login');
        }

        try {
            $recipe = $this->firebaseService->getRecipeById($recipeId);

            if (empty($recipe)) {
                $session->setFlashdata('error', 'Resep tidak ditemukan.');
                return redirect()->back();
            }

            if ($recipe['author'] !== $currentUsername) {
                $session->setFlashdata('error', 'Anda tidak memiliki izin untuk menghapus resep ini.');
                return redirect()->to('/riwayat/' . urlencode($currentUsername));
            }

            // Hapus gambar dari Supabase jika ada
            if (!empty($recipe['imageUrl']) && function_exists('delete_image_from_supabase')) {
                $filename = basename($recipe['imageUrl']);
                if (!delete_image_from_supabase($filename)) {
                    log_message('warning', "Gagal hapus gambar Supabase: {$filename}");
                }
            }

            // Hapus data resep dari Firebase
            $this->firebaseService->deleteRecipe($recipeId);
            $session->setFlashdata('success', 'Resep berhasil dihapus.');

        } catch (\Exception $e) {
            log_message('error', 'Gagal hapus resep: ' . $e->getMessage());
            $session->setFlashdata('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }

        return redirect()->to('/riwayat/' . urlencode($currentUsername));
    }
}
