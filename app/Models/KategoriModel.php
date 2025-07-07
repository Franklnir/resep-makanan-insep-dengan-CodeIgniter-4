<?php

namespace App\Models;

use CodeIgniter\Model;

class KategoriModel extends Model
{
    protected $table      = 'kategori';  // Nama tabel
    protected $primaryKey = 'id_kategori';  // Primary key
    protected $allowedFields = ['nama_kategori'];  // Kolom yang diperbolehkan untuk diinputkan

    // Tidak diperlukan jika tabel memiliki created_at dan updated_at otomatis
    protected $useTimestamps = true;
}
