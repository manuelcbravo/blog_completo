<?php

namespace App\Models;

use App\Enums\TipoPublicacion;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $post_id
 * @property TipoPublicacion $tipo
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $referer
 * @property string|null $session_id
 * @property CarbonInterface|null $created_at
 */
class Vista extends Model
{
    protected $table = 'post_views';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'post_id',
        'tipo',
        'ip_address',
        'user_agent',
        'referer',
        'session_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoPublicacion::class,
        ];
    }
}
