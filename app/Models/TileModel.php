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
        // Sichtbarkeit:
        // - eigene Kachel
        // - global
        // - explizit dem Benutzer zugeordnet (tile_users)
        // - einer Gruppe zugeordnet, in der der Benutzer Mitglied ist (tile_groups x user_groups)
        $db = \Config\Database::connect();
        $hasTU = $db->tableExists('tile_users');
        $hasTG = $db->tableExists('tile_groups');
        $hasUG = $db->tableExists('user_groups');

        $builder = $this->select('tiles.*')->distinct();

        // User-spezifische Präferenzen (verstecken/Reihenfolge)
        $db = \Config\Database::connect();
        $hasPrefs = $db->tableExists('user_tile_prefs');
        if ($hasPrefs) {
            $utp = $db->protectIdentifiers('user_tile_prefs');
            $builder->join("{$utp} utp", 'utp.tile_id = tiles.id AND utp.user_id = ' . (int)$userId, 'left');
        }

        if ($hasTU) {
            $tuTable = $db->protectIdentifiers('tile_users');
            $builder->join("{$tuTable} tu", 'tu.tile_id = tiles.id AND tu.user_id = ' . (int)$userId, 'left');
        }
        if ($hasUG && $hasTG) {
            $ugTable = $db->protectIdentifiers('user_groups');
            $tgTable = $db->protectIdentifiers('tile_groups');
            $builder->join("{$ugTable} ug", 'ug.user_id = ' . (int)$userId, 'left')
                    ->join("{$tgTable} tg", 'tg.tile_id = tiles.id AND tg.group_id = ug.group_id', 'left');
        }

        $builder->groupStart()
            ->where('tiles.user_id', $userId)
            ->orWhere('tiles.is_global', 1);
        if ($hasTU) {
            $builder->orWhere('tu.user_id IS NOT NULL', null, false);
        }
        if ($hasUG && $hasTG) {
            $builder->orWhere('tg.group_id IS NOT NULL', null, false);
        }
        $builder->groupEnd();

        // Versteckte Kacheln des Nutzers ausblenden
        if ($hasPrefs) {
            $builder->groupStart()
                ->where('utp.hidden', 0)
                ->orWhere('utp.hidden IS NULL', null, false)
            ->groupEnd();
        }

        // Sortierung: nach Kategorie, dann per Nutzer-Position (falls vorhanden), sonst tiles.position
        if ($hasPrefs) {
            $builder->orderBy('tiles.category', 'ASC')
                    ->orderBy('COALESCE(utp.position, tiles.position)', 'ASC', false)
                    ->orderBy('tiles.id', 'ASC');
        } else {
            $builder->orderBy('tiles.category', 'ASC')
                    ->orderBy('tiles.position', 'ASC')
                    ->orderBy('tiles.id', 'ASC');
        }

        return $builder;
    }
}
