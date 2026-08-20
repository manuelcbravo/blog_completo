<?php

namespace App\Models;

use App\Enums\SeccionSitio;
use App\Enums\TipoPublicacion;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $post_id
 * @property string $tipo
 * @property string|null $ruta
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
        'ruta',
        'ip_address',
        'user_agent',
        'referer',
        'session_id',
    ];

    /**
     * El tipo guarda un TipoPublicacion o un SeccionSitio, así que se queda
     * como cadena: castearlo a uno de los dos reventaría al leer el otro.
     */
    public function seccion(): ?SeccionSitio
    {
        return SeccionSitio::tryFrom($this->tipo);
    }

    public function tipoPublicacion(): ?TipoPublicacion
    {
        return TipoPublicacion::tryFrom($this->tipo);
    }

    /**
     * Etiqueta corta del user agent. No pretende ser exacta —para eso hace
     * falta una librería—, sólo evitar que la tabla muestre 300 caracteres
     * ilegibles cuando lo único que quieres saber es si fue móvil o robot.
     */
    public function navegador(): string
    {
        $agente = (string) $this->user_agent;

        if ($agente === '') {
            return 'Desconocido';
        }

        $robots = ['bot', 'crawler', 'spider', 'slurp', 'facebookexternalhit', 'headless'];

        foreach ($robots as $marca) {
            if (str_contains(strtolower($agente), $marca)) {
                return 'Robot';
            }
        }

        $navegador = match (true) {
            str_contains($agente, 'Edg/') => 'Edge',
            str_contains($agente, 'OPR/') => 'Opera',
            str_contains($agente, 'Firefox/') => 'Firefox',
            str_contains($agente, 'Chrome/') => 'Chrome',
            str_contains($agente, 'Safari/') => 'Safari',
            default => 'Otro',
        };

        $sistema = match (true) {
            str_contains($agente, 'iPhone'), str_contains($agente, 'iPad') => 'iOS',
            str_contains($agente, 'Android') => 'Android',
            str_contains($agente, 'Windows') => 'Windows',
            str_contains($agente, 'Mac OS X') => 'macOS',
            str_contains($agente, 'Linux') => 'Linux',
            default => null,
        };

        return $sistema === null ? $navegador : "{$navegador} · {$sistema}";
    }

    /**
     * De dónde llegó: el dominio del referente, o "Directo" si no traía uno.
     * Es el dato que más dice de una visita, bastante más que la IP.
     */
    public function origen(): string
    {
        $referente = (string) $this->referer;

        if ($referente === '') {
            return 'Directo';
        }

        $host = parse_url($referente, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return 'Directo';
        }

        $host = preg_replace('/^www\./', '', $host) ?? $host;

        return $host === parse_url((string) config('app.url'), PHP_URL_HOST)
            ? 'Interno'
            : $host;
    }
}
