<?php

namespace App\Models;

use CodeIgniter\Model;

class UserSettingModel extends Model
{
    protected $table = 'user_settings';
    protected $primaryKey = 'user_id';

    protected $useTimestamps = true;
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'user_id', 'columns',
    ];

    protected $returnType = 'array';

    protected $validationRules = [
        'user_id' => 'required|is_natural_no_zero',
        'columns' => 'required|is_natural_no_zero|greater_than_equal_to[1]|less_than_equal_to[6]',
    ];

    public function getOrCreate(int $userId): array
    {
        $row = $this->find($userId);
        if ($row) {
            return $row;
        }
        $this->insert(['user_id' => $userId, 'columns' => 3]);
        return $this->find($userId) ?: ['user_id' => $userId, 'columns' => 3];
    }
}
