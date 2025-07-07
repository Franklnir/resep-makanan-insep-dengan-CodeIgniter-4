<?php

use GuzzleHttp\Client;
use CodeIgniter\Config\Services;
use Config\Firebase; // Untuk mengambil konfigurasi Supabase

// Fungsi untuk mengunggah gambar ke Supabase
if (!function_exists('upload_image_to_supabase')) {
    function upload_image_to_supabase(string $imagePath, string $filename): ?string
    {
        $config = new Firebase(); // Ambil konfigurasi Supabase

        $client = new Client();
        $headers = [
            "apikey" => $config->supabaseApiKey,
            "Authorization" => "Bearer " . $config->supabaseApiKey,
            "Content-Type" => mime_content_type($imagePath) // Tambahkan Content-Type
        ];

        try {
            $response = $client->put(
                "{$config->supabaseStorageUrl}/{$config->supabaseBucket}/{$filename}",
                [
                    'headers' => $headers,
                    'body' => fopen($imagePath, 'r') // Menggunakan stream untuk body
                ]
            );

            if ($response->getStatusCode() === 200) {
                // Supabase biasanya mengembalikan URL lengkap setelah upload
                // URL bisa diakses publik dengan format ini:
                $imageUrl = "https://zwyjincljcwqjyagzwci.supabase.co/storage/v1/object/public/{$config->supabaseBucket}/{$filename}";
                return $imageUrl;
            } else {
                log_message('error', 'Error uploading image to Supabase: ' . $response->getBody());
                return null;
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            log_message('error', 'Supabase Client Error: ' . $e->getMessage() . ' - Response: ' . $e->getResponse()->getBody());
            return null;
        } catch (\Exception $e) {
            log_message('error', 'General Error uploading image to Supabase: ' . $e->getMessage());
            return null;
        }
    }
}

// Fungsi untuk kompresi gambar (memerlukan GD atau Imagick extension PHP)
if (!function_exists('compress_image')) {
    function compress_image(string $imagePath, int $quality = 85): string
    {
        try {
            $info = getimagesize($imagePath);
            $mime = $info['mime'];

            if ($mime == 'image/jpeg') {
                $image = imagecreatefromjpeg($imagePath);
            } elseif ($mime == 'image/gif') {
                $image = imagecreatefromgif($imagePath);
            } elseif ($mime == 'image/png') {
                $image = imagecreatefrompng($imagePath);
            } else {
                return $imagePath; // Tidak bisa dikompres, kembalikan path asli
            }

            $compressedPath = sys_get_temp_dir() . '/' . uniqid('compressed_') . '_' . basename($imagePath);

            // Simpan gambar yang dikompresi
            imagejpeg($image, $compressedPath, $quality);
            imagedestroy($image);

            return $compressedPath;
        } catch (\Exception $e) {
            log_message('error', 'Error compressing image: ' . $e->getMessage());
            return $imagePath; // Kembalikan path asli jika kompresi gagal
        }
    }
}

// Load helper ini di app/Config/Autoload.php
// public $helpers = ['url', 'form', 'session', 'image']; // Tambahkan 'image'