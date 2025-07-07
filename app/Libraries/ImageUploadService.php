<?php namespace App\Libraries; // <-- This line MUST be exactly like this

use CodeIgniter\Images\Image;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Services;
use Config\Firebase as FirebaseConfig; // Assuming you have this config file for Supabase keys

class ImageUploadService
{
    protected string $supabaseStorageUrl;
    protected string $supabaseBucket;
    protected string $supabaseApiKey;

    public function __construct()
    {
        // Load the Firebase config (which should contain Supabase credentials)
        $firebaseConfig = config('Firebase');

        // Check if the necessary Supabase properties exist in your Firebase config
        if (property_exists($firebaseConfig, 'supabaseStorageUrl') &&
            property_exists($firebaseConfig, 'supabaseBucket') &&
            property_exists($firebaseConfig, 'supabaseApiKey')) {
            $this->supabaseStorageUrl = $firebaseConfig->supabaseStorageUrl;
            $this->supabaseBucket = $firebaseConfig->supabaseBucket;
            $this->supabaseApiKey = $firebaseConfig->supabaseApiKey;
        } else {
            // Handle error: Supabase configuration missing
            // Log an error and/or throw an exception
            log_message('error', 'Supabase configuration missing in app/Config/Firebase.php');
            // Depending on how critical this is, you might throw an exception:
            // throw new \Exception('Supabase configuration is incomplete.');
        }
    }

    /**
     * Compresses an image and uploads it to Supabase storage.
     * Returns the URL of the uploaded image or null on failure.
     */
    public function compressAndUpload(UploadedFile $imageFile): ?string
    {
        if (!$imageFile->isValid() || $imageFile->hasMoved()) {
            return null;
        }

        // Generate a unique filename
        $filename = $imageFile->getRandomName();
        $tempPath = WRITEPATH . 'uploads/' . $filename; // Use writable path for temporary storage

        // Move the uploaded file to a temporary location
        if (!$imageFile->move(WRITEPATH . 'uploads', $filename)) {
            log_message('error', 'Failed to move uploaded image to temp directory.');
            return null;
        }

        // Compress the image
        $compressedPath = $this->compressImage($tempPath);

        if (!$compressedPath) {
            log_message('error', 'Failed to compress image.');
            unlink($tempPath); // Clean up temp file
            return null;
        }

        // Upload to Supabase
        $imageUrl = $this->uploadToSupabase($compressedPath, $filename);

        // Clean up temporary files
        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
        if (file_exists($compressedPath)) {
            unlink($compressedPath);
        }

        return $imageUrl;
    }

    /**
     * Compresses an image using CodeIgniter's Image library.
     * Returns the path to the compressed image.
     */
    private function compressImage(string $filePath, int $quality = 85): string
    {
        try {
            $image = Services::image('gd'); // Use 'gd' or 'imagick'
            $image->withFile($filePath)
                  ->save($filePath, $quality); // Overwrite original with compressed
            return $filePath;
        } catch (\Exception $e) {
            log_message('error', 'Image compression failed: ' . $e->getMessage());
            return ''; // Return empty string on failure
        }
    }

    /**
     * Uploads a file to Supabase Storage.
     * Returns the public URL of the uploaded file or null on failure.
     */
    private function uploadToSupabase(string $filePath, string $filename): ?string
    {
        if (empty($this->supabaseBucket) || empty($this->supabaseApiKey) || empty($this->supabaseStorageUrl)) {
            log_message('error', 'Supabase API keys or bucket not configured.');
            return null;
        }

        try {
            $client = Services::curlrequest([
                'base_uri' => rtrim($this->supabaseStorageUrl, '/') . '/',
                'headers' => [
                    'apikey' => $this->supabaseApiKey,
                    'Authorization' => 'Bearer ' . $this->supabaseApiKey,
                ],
            ]);

            $response = $client->post("object/public/{$this->supabaseBucket}/" . $filename, [
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => $filename,
                    ],
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                // Construct the public URL
                return rtrim($this->supabaseStorageUrl, '/') . "/object/public/{$this->supabaseBucket}/" . $filename;
            } else {
                log_message('error', 'Supabase upload failed: ' . $response->getStatusCode() . ' - ' . $response->getBody());
                return null;
            }
        } catch (\Throwable $e) {
            log_message('error', 'Supabase upload exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Deletes an image from Supabase Storage.
     * Returns true on success, false on failure.
     */
    public function deleteFromSupabase(string $imageUrl): bool
    {
        if (empty($this->supabaseBucket) || empty($this->supabaseApiKey) || empty($this->supabaseStorageUrl)) {
            log_message('error', 'Supabase API keys or bucket not configured for deletion.');
            return false;
        }

        // Extract filename from the URL
        // Example URL: https://[project_ref].supabase.co/storage/v1/object/public/[bucket_name]/[filename]
        $pathParts = explode("/object/public/{$this->supabaseBucket}/", $imageUrl);
        if (count($pathParts) < 2) {
            log_message('warning', 'Could not extract filename from URL for Supabase deletion: ' . $imageUrl);
            return false;
        }
        $filenameToDelete = $pathParts[1];

        try {
            $client = Services::curlrequest([
                'base_uri' => rtrim($this->supabaseStorageUrl, '/') . '/',
                'headers' => [
                    'apikey' => $this->supabaseApiKey,
                    'Authorization' => 'Bearer ' . $this->supabaseApiKey,
                    'Content-Type' => 'application/json', // Needed for JSON body
                ],
            ]);

            $response = $client->delete("object/public/{$this->supabaseBucket}", [
                'json' => ['filenames' => [$filenameToDelete]], // Supabase expects an array of filenames
            ]);

            if ($response->getStatusCode() === 200) {
                return true;
            } else {
                log_message('error', 'Supabase delete failed: ' . $response->getStatusCode() . ' - ' . $response->getBody());
                return false;
            }
        } catch (\Throwable $e) {
            log_message('error', 'Supabase delete exception: ' . $e->getMessage());
            return false;
        }
    }
}