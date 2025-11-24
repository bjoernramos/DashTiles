<?php

namespace App\Models;

use CodeIgniter\Model;

class TileModel extends Model
{
    protected $table = 'tiles';
    protected $primaryKey = 'id';

    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'user_id', 'is_global', 'type', 'title', 'url', 'icon', 'text', 'category', 'position',
    ];

    protected $returnType = 'array';

    protected $validationRules = [
        'user_id'  => 'required|is_natural_no_zero',
        'is_global'=> 'permit_empty|in_list[0,1]',
        'type'     => 'required|in_list[link,iframe,file]',
        'title'    => 'required|min_length[1]|max_length[190]',
        'url'      => 'permit_empty|max_length[1024]',
        'icon'     => 'permit_empty|max_length[255]',
        'text'     => 'permit_empty|max_length[255]',
        'category' => 'permit_empty|max_length[190]',
        'position' => 'permit_empty|integer',
    ];

    public function forUser(int $userId)
    {
        // Zeige eigene Kacheln oder globale Kacheln (is_global = 1)
        return $this->groupStart()
                ->where('user_id', $userId)
                ->orWhere('is_global', 1)
            ->groupEnd()
            ->orderBy('category', 'ASC')
            ->orderBy('position', 'ASC')
            ->orderBy('id', 'ASC');
    }
}
