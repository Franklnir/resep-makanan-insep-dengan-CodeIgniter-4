<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('/login', 'Auth::login');
$routes->get('/register', 'Auth::register');
$routes->post('/auth/setSession', 'Auth::setSession');
// Jika Anda ingin halaman utama TIDAK bisa diakses tanpa login, pindahkan route ini ke dalam group 'auth' di bawah.
// Untuk kasus 'insep - Resep Masakan' yang menampilkan resep populer, biasanya halaman utama bisa diakses tanpa login.


// Recipes routes
$routes->get('/recipe/(:segment)', 'Recipe::detail/$1');
$routes->get('/recipe_detail/(:segment)', 'RecipeController::detail/$1'); // Detail resep berdasarkan username atau ID resep
$routes->get('/api/recipes', 'Recipe::apiRecipes');



// Upload recipe routes
$routes->get('/upload', 'Recipe::uploadForm');
$routes->post('/upload', 'Recipe::upload');

// Delete recipe route
$routes->post('/delete_recipe/(:segment)', 'Recipe::deleteRecipe/$1');
// Route untuk daftar user
$routes->get('/user', 'User::listUsers');
// User routes
$routes->get('riwayat/(:segment)', 'Riwayat::index/$1');
$routes->get('riwayat/(:segment)', 'User::riwayat/$1');






$routes->get('riwayat', 'Riwayat::index');

$routes->post('recipe/update/(:segment)', 'Recipe::update/$1');



// Add this route for the POST request to your upload submission
$routes->post('upload/submit', 'Upload::submit');
$routes->get('upload', 'Upload::index'); // If not already there

$routes->get('upload', 'Recipe::uploadForm');
$routes->post('upload', 'Recipe::upload');

$routes->get('/upload', 'Upload::index');
$routes->post('/upload/submit', 'Upload::submit', ['as' => 'upload::submit']);

// In app/Config/Routes.php

$routes->get('edit_recipe/(:segment)', 'Riwayat::edit_recipe/$1');
$routes->post('edit_recipe/(:segment)', 'Riwayat::edit_recipe/$1');
$routes->post('delete_recipe/(:segment)', 'Riwayat::delete_recipe/$1');

// Rute untuk Riwayat
$routes->get('/riwayat/(:segment)', 'Riwayat::index/$1'); // Untuk riwayat user tertentu (e.g., /riwayat/john_doe)
$routes->get('/riwayat', 'Riwayat::index'); // Untuk riwayat user yang login (e.g., /riwayat)

// Rute untuk Edit Resep
$routes->get('/riwayat/edit/(:segment)', 'Riwayat::edit_recipe/$1');
$routes->post('/riwayat/edit/(:segment)', 'Riwayat::edit_recipe/$1');
$routes->get('recipe/edit/(:segment)', 'Recipe::editForm/$1');
$routes->post('recipe/update/(:segment)', 'Recipe::update/$1');
$routes->post('recipe/delete/(:segment)', 'Recipe::delete/$1');
$routes->get('recipe/detail/(:segment)', 'Recipe::detail/$1');
$routes->post('recipe/delete/(:segment)', 'Recipe::delete/$1');
$routes->post('recipe/delete/(:segment)', 'Recipe::deleteRecipe/$1');
$routes->get('recipe/delete/(:segment)', 'Recipe::delete/$1');
$routes->post('riwayat/delete/(:segment)', 'Riwayat::delete/$1');
$routes->get('edit_recipe/(:segment)', 'Riwayat::edit_recipe/$1');
$routes->post('edit_recipe/(:segment)', 'Riwayat::update_recipe/$1');

// Rute untuk Hapus Resep
$routes->post('/riwayat/delete/(:segment)', 'Riwayat::delete_recipe/$1');
// Atau jika Anda ingin menggunakan GET (tidak disarankan untuk DELETE, tapi untuk testing):
// $routes->get('/riwayat/delete/(:segment)', 'Riwayat::delete_recipe/$1');
$routes->delete('recipe/delete/(:segment)', 'Recipe::delete/$1');

$routes->get('logout', 'Auth::logout');
// app/Config/Routes.php
$routes->get('/user/profile', 'User::showProfile'); // To display the profile
$routes->post('/user/profile', 'User::updateProfile'); // To update the profile
$routes->get('recipe/edit/(:segment)', 'Recipe::editForm/$1');
$routes->post('recipe/update/(:segment)', 'Recipe::update/$1');
$routes->post('recipe/delete/(:segment)', 'Recipe::deleteRecipe/$1'); // Pastikan ini juga ada

$routes->get('/riwayat/(:segment)', 'User::riwayat/$1');
  $routes->get('riwayat/(:segment)', 'Riwayat::index/$1');
$routes->get('/user', 'User::listUsers');
$routes->match(['get', 'post'], 'riwayat/edit_recipe/(:segment)', 'Riwayat::edit_recipe/$1');


// Jika Anda memiliki routes.recipes.py dari Flask, Anda perlu mengonversinya
// menjadi rute di sini atau membuat sebuah 'Group' di CI4 jika kompleks.
// Contoh Blueprint di Flask bisa jadi group di CI4:
// $routes->group('recipes', function($routes){
//     $routes->get('/', 'RecipesController::index');
//     $routes->get('latest', 'RecipesController::latest');
// });
// Karena Anda tidak menyertakan konten routes.recipes.py, saya hanya mengasumsikan
// rute utama ada di app.py Anda. Jika ada blueprint lain, konversikan secara serupa.