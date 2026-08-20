<?php

namespace App\Enums;

enum Permiso: string
{
    case UsuariosGestionar = 'usuarios.gestionar';

    case BlogPublicacionesGestionar = 'blog.publicaciones.gestionar';
    case BlogTaxonomiasGestionar = 'blog.taxonomias.gestionar';
    case BlogComentariosModerar = 'blog.comentarios.moderar';
    case BlogSuscriptoresGestionar = 'blog.suscriptores.gestionar';
    case BlogContactosGestionar = 'blog.contactos.gestionar';
    case BlogAnaliticaVer = 'blog.analitica.ver';

    public function label(): string
    {
        return match ($this) {
            self::UsuariosGestionar => 'Gestionar usuarios y roles',
            self::BlogPublicacionesGestionar => 'Gestionar publicaciones, tutoriales y recursos',
            self::BlogTaxonomiasGestionar => 'Gestionar categorías y etiquetas',
            self::BlogComentariosModerar => 'Moderar comentarios',
            self::BlogSuscriptoresGestionar => 'Gestionar suscriptores',
            self::BlogContactosGestionar => 'Atender mensajes de contacto',
            self::BlogAnaliticaVer => 'Ver la analítica del blog',
        };
    }
}
