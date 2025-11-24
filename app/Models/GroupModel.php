<?php

namespace App\Models;

use CodeIgniter\Model;

class GroupModel extends Model
{
    protected $table = 'groups';
    protected $primaryKey = 'id';

    protected $useTimestamps = true;

    protected $allowedFields = ['name'];

    protected $returnType = 'array';

    protected $validationRules = [
        'name' => 'required|min_length[2]|max_length[190]|is_unique[groups.name,id,{id}]',
    ];
}
