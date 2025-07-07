<?php namespace App\Controllers;

use App\Libraries\FirebaseService;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Config\Services;

class Recipe extends BaseController
{
    use ResponseTrait;

    protected $firebaseService;
    protected $helpers = ['form', 'url', 'image'];

    public function __construct()
    {
        $this->firebaseService = new FirebaseService();
        helper($this->helpers); // Pastikan helper otomatis diload
    }

    /**
     * Halaman utama menampilkan daftar resep
     */
    public function index()
    {
        $recipesRef = $this->firebaseService->getRecipesRef();
        $recipes = $recipesRef->getValue();

        $formattedRecipes = [];
        if ($recipes) {
            foreach ($recipes as $id => $recipeData) {
                $recipeData['id'] = $id;
                $formattedRecipes[] = $recipeData;
            }
        }

        $session = session();
        if (!$session->has('username')) {
            $session->set('username', 'Anonim');
            $session->set('user_avatar', 'https://via.placeholder.com/40');
        }

        $data['recipes'] = $formattedRecipes;
        return view('recipes_index', $data);
    }

    /**
     * Detail resep berdasarkan ID
     */
    public function detail(string $recipe_id)
    {
        $recipesRef = $this->firebaseService->getRecipesRef();
        $recipe = $recipesRef->getChild($recipe_id)->getValue();

        if (empty($recipe)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Resep tidak ditemukan');
        }

        $data['recipe'] = $recipe;
        return view('recipe_detail', $data);
    }

    /**
     * API endpoint JSON semua resep
     */
    public function apiRecipes()
    {
        $recipesRef = $this->firebaseService->getRecipesRef();
        $recipes = $recipesRef->getValue();

        $formattedRecipes = [];
        if ($recipes) {
            foreach ($recipes as $id => $recipeData) {
                $recipeData['id'] = $id;
                $formattedRecipes[] = $recipeData;
            }
        }
        return $this->respond($formattedRecipes);
    }

    /**
     * Form upload resep (GET)
     */
    public function uploadForm()
    {
        $data['validation'] = Services::validation();
        return view('upload_form', $data);
    }

    /**
     * Proses upload resep (POST)
     */
    public function upload()
    {
        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('/upload'));
        }

        $rules = [
            'name'          => 'required|min_length[3]|max_length[255]',
            'description'   => 'required|min_length[10]',
            'category'      => 'required|min_length[2]|max_length[100]',
            'ingredients'   => 'required',
            'steps'         => 'required',
            'image'         => 'if_exist|uploaded[image]|is_image[image]|mime_in[image,image/jpg,image/jpeg,image/png,image/webp]|max_size[image,2048]',
        ];

        if (! $this->validate($rules)) {
            session()->setFlashdata('errors', $this->validator->getErrors());
            return redirect()->back()->withInput();
        }

        $data = $this->request->getPost();
        $imageFile = $this->request->getFile('image');
        $imageUrl = '';

        if ($imageFile && $imageFile->isValid() && !$imageFile->hasMoved()) {
            $filename = $imageFile->getRandomName();
            $uploadPath = WRITEPATH . 'uploads/';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            $imageFile->move($uploadPath, $filename);
            $tempPath = $uploadPath . $filename;

            $compressedImagePath = $tempPath;
            if (function_exists('compress_image')) {
                $compressedImagePath = compress_image($tempPath);
            }

            if (function_exists('upload_image_to_supabase')) {
                $imageUrl = upload_image_to_supabase($compressedImagePath, $filename);
            } else {
                log_message('error', 'Fungsi upload_image_to_supabase tidak ditemukan.');
            }

            // Hapus file lokal
            if (file_exists($tempPath)) unlink($tempPath);
            if (file_exists($compressedImagePath) && $compressedImagePath !== $tempPath) unlink($compressedImagePath);
        }

        $recipe_id = uniqid();
        $username = session()->get('username', 'Anonim');
        $timestamp = time();

        try {
            $this->firebaseService->getRecipesRef()->getChild($recipe_id)->set([
                'id'          => $recipe_id,
                'name'        => $data['name'],
                'description' => $data['description'],
                'category'    => $data['category'],
                'ingredients' => explode("\n", $data['ingredients']),
                'steps'       => explode("\n", $data['steps']),
                'videoUrl'    => $data['videoUrl'] ?? null,
                'imageUrl'    => $imageUrl,
                'author'      => $username,
                'timestamp'   => $timestamp,
            ]);
            session()->setFlashdata('success', 'Resep berhasil diunggah!');
            return redirect()->to(base_url('/'));
        } catch (\Exception $e) {
            log_message('error', 'Gagal menyimpan resep ke Firebase: ' . $e->getMessage());
            session()->setFlashdata('error', 'Gagal menyimpan resep: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Form edit resep (GET)
     */
    public function editForm(string $id)
    {
        $recipesRef = $this->firebaseService->getRecipesRef();
        $recipe = $recipesRef->getChild($id)->getValue();

        if (empty($recipe)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Resep tidak ditemukan');
        }

        $loggedInUsername = session()->get('user_username');
        if ($recipe['author'] !== $loggedInUsername) {
            session()->setFlashdata('error', 'Anda tidak memiliki izin untuk mengedit resep ini.');
            return redirect()->to(base_url('/recipe/detail/' . $id));
        }

        $data['recipe'] = $recipe;
        $data['validation'] = Services::validation();
        return view('edit_form', $data);
    }

    /**
     * Update resep (POST)
     */
    public function update(string $id)
    {
        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('/recipe/edit/' . $id));
        }

        $rules = [
            'name'          => 'required|min_length[3]|max_length[255]',
            'description'   => 'required|min_length[10]',
            'category'      => 'required|min_length[2]|max_length[100]',
            'ingredients'   => 'required',
            'steps'         => 'required',
            'image'         => 'if_exist|uploaded[image]|is_image[image]|mime_in[image,image/jpg,image/jpeg,image/png,image/webp]|max_size[image,2048]',
        ];

        if (! $this->validate($rules)) {
            session()->setFlashdata('errors', $this->validator->getErrors());
            return redirect()->back()->withInput();
        }

        $recipesRef = $this->firebaseService->getRecipesRef();
        $originalRecipe = $recipesRef->getChild($id)->getValue();

        if (empty($originalRecipe)) {
            session()->setFlashdata('error', 'Resep tidak ditemukan.');
            return redirect()->to(base_url('/'));
        }

        $loggedInUsername = session()->get('username');
        if ($originalRecipe['author'] !== $loggedInUsername) {
            session()->setFlashdata('error', 'Anda tidak memiliki izin untuk mengedit resep ini.');
            return redirect()->to(base_url('/recipe/detail/' . $id));
        }

        $data = $this->request->getPost();
        $imageFile = $this->request->getFile('image');
        $imageUrl = $originalRecipe['imageUrl'] ?? '';

        if ($imageFile && $imageFile->isValid() && !$imageFile->hasMoved()) {
            $filename = $imageFile->getRandomName();
            $uploadPath = WRITEPATH . 'uploads/';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            $imageFile->move($uploadPath, $filename);
            $tempPath = $uploadPath . $filename;

            $compressedImagePath = $tempPath;
            if (function_exists('compress_image')) {
                $compressedImagePath = compress_image($tempPath);
            }

            if (function_exists('upload_image_to_supabase')) {
                $imageUrl = upload_image_to_supabase($compressedImagePath, $filename);
            } else {
                log_message('error', 'Fungsi upload_image_to_supabase tidak ditemukan saat update.');
            }

            if (file_exists($tempPath)) unlink($tempPath);
            if (file_exists($compressedImagePath) && $compressedImagePath !== $tempPath) unlink($compressedImagePath);
        }

        $updateData = [
            'name'        => $data['name'],
            'description' => $data['description'],
            'category'    => $data['category'],
            'ingredients' => explode("\n", $data['ingredients']),
            'steps'       => explode("\n", $data['steps']),
            'videoUrl'    => $data['videoUrl'] ?? null,
            'imageUrl'    => $imageUrl,
        ];

        try {
            $recipesRef->getChild($id)->update($updateData);
            session()->setFlashdata('success', 'Resep berhasil diperbarui!');
            return redirect()->to(base_url('/recipe/detail/' . $id));
        } catch (\Exception $e) {
            log_message('error', 'Gagal memperbarui resep di Firebase: ' . $e->getMessage());
            session()->setFlashdata('error', 'Gagal memperbarui resep: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Hapus resep (DELETE)
     */
    public function delete(string $id)
    {
        $recipesRef = $this->firebaseService->getRecipesRef();
        $recipe = $recipesRef->getChild($id)->getValue();

        if (empty($recipe)) {
            session()->setFlashdata('error', 'Resep tidak ditemukan.');
            return redirect()->to(base_url('/'));
        }

        $loggedInUsername = session()->get('username'); // sesuai keinginan kamu

        if ($recipe['author'] !== $loggedInUsername) {
            session()->setFlashdata('error', 'Anda tidak memiliki izin untuk menghapus resep ini.');
            return redirect()->to(base_url('/recipe/detail/' . $id));
        }

        try {
            // Hapus gambar dari supabase jika ada
            if (!empty($recipe['imageUrl']) && function_exists('delete_image_from_supabase')) {
                delete_image_from_supabase($recipe['imageUrl']);
            }

            $recipesRef->getChild($id)->remove();
            session()->setFlashdata('success', 'Resep berhasil dihapus.');
            return redirect()->to(base_url('/'));
        } catch (\Exception $e) {
            log_message('error', 'Gagal menghapus resep di Firebase: ' . $e->getMessage());
            session()->setFlashdata('error', 'Gagal menghapus resep: ' . $e->getMessage());
            return redirect()->to(base_url('/recipe/detail/' . $id));
        }
    }

    /**
     * Daftar unik penulis dari resep
     */
    public function listAuthors()
    {
        $recipesRef = $this->firebaseService->getRecipesRef();
        $recipes = $recipesRef->getValue();

        $authors = [];
        if ($recipes) {
            foreach ($recipes as $recipe) {
                if (!empty($recipe['author'])) {
                    $authors[] = $recipe['author'];
                }
            }
        }

        $uniqueAuthors = array_unique($authors);
        sort($uniqueAuthors);

        return $this->respond($uniqueAuthors);
    }
}
