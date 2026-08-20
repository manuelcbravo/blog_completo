<?php

namespace App\Enums;

enum Rol: string
{
    case Administrador = 'administrador';
    case Editor = 'editor';
    case Demo = 'demo';

    /**
     * @return list<Permiso>
     */
    public function permisos(): array
    {
        return match ($this) {
            self::Administrador => Permiso::cases(),

            self::Editor => [
                Permiso::BlogPublicacionesVer,
                Permiso::BlogPublicacionesGestionar,
                Permiso::BlogPublicacionesPublicar,
                Permiso::BlogPublicacionesEliminar,
                Permiso::BlogTaxonomiasVer,
                Permiso::BlogTaxonomiasGestionar,
                Permiso::BlogComentariosVer,
                Permiso::BlogComentariosModerar,
                Permiso::BlogAnaliticaVer,
            ],

            /*
             * Cuenta de demostración: recorre el panel y puede capturar, pero
             * nada de lo que haga sale al sitio ni desaparece.
             *
             * No lleva `publicar` ni `eliminar` —las dos acciones sin vuelta
             * atrás— ni ninguno de los permisos con datos personales de
             * terceros, porque la credencial está pensada para publicarse y
             * cualquiera podría entrar a leer los correos de tus suscriptores.
             */
            self::Demo => [
                Permiso::BlogPublicacionesVer,
                Permiso::BlogPublicacionesGestionar,
                Permiso::BlogTaxonomiasVer,
                Permiso::BlogComentariosVer,
                Permiso::BlogAnaliticaVer,
            ],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Editor => 'Editor',
            self::Demo => 'Demostración',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::Administrador => 'Control total del panel.',
            self::Editor => 'Escribe, publica y modera. No toca usuarios ni roles.',
            self::Demo => 'Solo lectura y captura. No publica, no elimina y no ve datos personales.',
        };
    }
}
