<?php

namespace App\Models;

use CodeIgniter\Model;

class UserTilePrefModel extends Model
{
    protected $table = 'user_tile_prefs';
    protected $primaryKey = null; // composite
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['user_id','tile_id','hidden','position','updated_at'];
}
