<?php namespace App\Models;

use CodeIgniter\Model;

class ResepModel extends Model
{
    protected $table      = 'resep'; // Your table name
    protected $primaryKey = 'id_resep'; // Your primary key field

    protected $useAutoIncrement = true;

    protected $returnType     = 'array'; // Or 'object'
    protected $useSoftDeletes = false; // Set to true if you use soft deletes

    // List all fields that are allowed to be mass-assigned
    protected $allowedFields = [
        'nama_resep', 'deskripsi', 'kategori',
        'bahan_bahan', 'langkah_langkah', 'url_video',
        'gambar_resep', 'user_id', 'tanggal_unggah'
    ];

    // Dates
    protected $useTimestamps = false; // Set to true if your table has created_at/updated_at
    protected $dateFormat    = 'datetime';
    // protected $createdField  = 'created_at';
    // protected $updatedField  = 'updated_at';
    // protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;
}