<?php namespace App\Controllers;

use App\Libraries\FirebaseService;

class Home extends BaseController
{
    protected $firebaseService;

    public function __construct()
    {
        $this->firebaseService = new FirebaseService();
    }

    public function index()
    {
        $recipesRef = $this->firebaseService->getRecipesRef();
        $recipes = $recipesRef->getValue(); // Mendapatkan data sebagai array PHP

        // Mengubah objek menjadi array jika ada data
        $data['recipes'] = $recipes ? array_values($recipes) : [];

        return view('index', $data);
    }
}