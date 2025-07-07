<?php

namespace App\Cells;

use CodeIgniter\View\Cells\Cell;
use App\Models\PostModel;

class PostCell extends Cell
{
    public $category;

    public function render()
    {
        $model = new PostModel();

        // Ambil post dengan kategori tertentu
        $data['posts'] = $model->where('category', $this->category)
                               ->orderBy('created_at', 'DESC')
                               ->findAll(5);

        return view('components/recent_posts', $data);
    }
}
