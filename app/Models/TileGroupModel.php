<?php

namespace App\Models;

use CodeIgniter\Model;

class TileGroupModel extends Model
{
    protected $table = 'tile_groups';
    protected $primaryKey = null; // composite
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $allowedFields = ['tile_id', 'group_id'];
    protected $useTimestamps = false;
}
