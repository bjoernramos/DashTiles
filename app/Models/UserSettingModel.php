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
        'user_id', 'columns', 'ping_enabled', 'background_enabled',
        'search_tile_enabled', 'search_engine', 'search_autofocus',
        'session_duration',
    ];

    protected $returnType = 'array';

    protected $validationRules = [
        'user_id' => 'required|is_natural_no_zero',
        'columns' => 'required|is_natural_no_zero|greater_than_equal_to[1]|less_than_equal_to[6]',
        'ping_enabled' => 'permit_empty|in_list[0,1]',
        'background_enabled' => 'permit_empty|in_list[0,1]',
        'search_tile_enabled' => 'permit_empty|in_list[0,1]',
        'search_autofocus' => 'permit_empty|in_list[0,1]',
        'search_engine' => 'permit_empty|in_list[google,duckduckgo,bing,startpage,ecosia]',
        // Allow any integer value; 0 means "until browser is closed"
        'session_duration' => 'permit_empty|integer'
    ];

    public function getOrCreate(int $userId): array
    {
        $row = $this->find($userId);
        if ($row) {
            return $row;
        }
        $defaultPing = getenv('PING_DEFAULT_ENABLED');
        $defaultPing = ($defaultPing === false || $defaultPing === '' || $defaultPing === null) ? 1 : ((string)$defaultPing === '0' ? 0 : 1);
        $defaultBg = getenv('BACKGROUND_DEFAULT_ENABLED');
        $defaultBg = ($defaultBg === false || $defaultBg === '' || $defaultBg === null) ? 0 : ((string)$defaultBg === '1' ? 1 : 0);
        $defaults = [
            'user_id' => $userId,
            'columns' => 3,
            'ping_enabled' => $defaultPing,
            'background_enabled' => $defaultBg,
            'search_tile_enabled' => 1,
            'search_engine' => 'google',
            'search_autofocus' => 0,
            'session_duration' => 7200,
        ];
        $this->insert($defaults);
        return $this->find($userId) ?: $defaults;
    }
}
