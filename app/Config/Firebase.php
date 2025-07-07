<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Firebase extends BaseConfig
{
    // Kosongkan semua untuk keamanan
    public array $config = [];

    public string $serviceAccountPath = '';
    public string $databaseUrl = '';
    public string $supabaseStorageUrl = '';
    public string $supabaseBucket = '';
    public string $supabaseApiKey = '';
}
