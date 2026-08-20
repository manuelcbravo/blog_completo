<?php

namespace App\Support\Publico;

/**
 * Parte el HTML de una publicación en dos para poder meter un anuncio dentro
 * del texto y no sólo al final.
 *
 * El corte se hace después de un párrafo de nivel superior, nunca dentro de un
 * bloque de código, una tabla o una lista: cortar ahí rompería el marcado. Y si
 * el artículo es corto, no se parte — un anuncio a la mitad de tres párrafos
 * estorba más de lo que rinde.
 */
class CuerpoConAnuncio
{
    /**
     * Mínimo de párrafos que debe tener el artículo para partirse.
     */
    private const MINIMO_PARRAFOS = 6;

    /**
     * Después de cuántos párrafos cae el anuncio. Suficiente para que quien
     * llegó ya esté leyendo, y no tan abajo que nadie lo alcance.
     */
    private const CORTE_TRAS = 3;

    /**
     * @return array{0: string, 1: string} El texto de antes y el de después.
     *                                     El segundo va vacío si no se parte.
     */
    public static function partir(?string $html): array
    {
        $html = (string) $html;

        if ($html === '') {
            return ['', ''];
        }

        $posiciones = self::finalesDeParrafo($html);

        if (count($posiciones) < self::MINIMO_PARRAFOS) {
            return [$html, ''];
        }

        $corte = $posiciones[self::CORTE_TRAS - 1];

        return [substr($html, 0, $corte), substr($html, $corte)];
    }

    /**
     * Posiciones donde termina cada `</p>` que está en el nivel superior del
     * documento. Se descartan los que van dentro de otro bloque —una cita, una
     * celda, un elemento de lista— contando la anidación con las etiquetas de
     * apertura y cierre que aparecen antes.
     *
     * @return list<int>
     */
    private static function finalesDeParrafo(string $html): array
    {
        $contenedores = 'blockquote|table|ul|ol|li|pre|figure|div|section';
        $patron = '/<\/p>|<(?<apertura>'.$contenedores.')[\s>]|<\/(?<cierre>'.$contenedores.')>/i';

        preg_match_all($patron, $html, $coincidencias, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        $profundidad = 0;
        $posiciones = [];

        foreach ($coincidencias as $coincidencia) {
            [$texto, $offset] = $coincidencia[0];

            if (($coincidencia['apertura'][0] ?? '') !== '') {
                $profundidad++;

                continue;
            }

            if (($coincidencia['cierre'][0] ?? '') !== '') {
                $profundidad = max(0, $profundidad - 1);

                continue;
            }

            if ($profundidad === 0) {
                $posiciones[] = $offset + strlen($texto);
            }
        }

        return $posiciones;
    }
}
