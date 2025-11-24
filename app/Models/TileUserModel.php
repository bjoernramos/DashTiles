<?php

namespace App\Models;

use CodeIgniter\Model;

class TileUserModel extends Model
{
    protected $table = 'tile_users';
    protected $primaryKey = null; // composite
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $allowedFields = ['tile_id', 'user_id'];
    protected $useTimestamps = false;
}
