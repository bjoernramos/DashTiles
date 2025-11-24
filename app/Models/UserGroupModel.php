<?php

namespace App\Models;

use CodeIgniter\Model;

class UserGroupModel extends Model
{
    protected $table = 'user_groups';
    protected $primaryKey = null; // composite
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $allowedFields = ['user_id', 'group_id'];
    protected $useTimestamps = false;
}
