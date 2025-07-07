<?php namespace Config;

use CodeIgniter\Config\BaseConfig;

class Image extends BaseConfig
{
    // ... properti lain
    public string $defaultHandler = 'gd'; // Pastikan 'gd' atau 'imagick' (jika diinstal)
    // ... properti lain
}