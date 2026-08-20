<?php

namespace App\Enums;

/**
 * Catálogo de permisos del panel, en formato `modulo.accion`.
 *
 * `ver` es consulta y `gestionar` es alta y edición —los controladores operan
 * por upsert, así que no tiene sentido separar crear de editar—. Lo que sí se
 * desglosa son las dos acciones de las que no hay vuelta atrás: **publicar**,
 * que saca contenido al sitio público, y **eliminar**. Así existe un perfil que
 * puede escribir sin poder publicar, que es justo lo que necesita una cuenta de
 * demostración o alguien que todavía no tiene la confianza del equipo.
 *
 * El RoleSeeder crea automáticamente cada caso que se agregue aquí.
 */
enum Permiso: string
{
    case UsuariosGestionar = 'usuarios.gestionar';

    // Publicaciones, tutoriales y recursos
    case BlogPublicacionesVer = 'blog.publicaciones.ver';
    case BlogPublicacionesGestionar = 'blog.publicaciones.gestionar';
    case BlogPublicacionesPublicar = 'blog.publicaciones.publicar';
    case BlogPublicacionesEliminar = 'blog.publicaciones.eliminar';

    // Categorías y etiquetas
    case BlogTaxonomiasVer = 'blog.taxonomias.ver';
    case BlogTaxonomiasGestionar = 'blog.taxonomias.gestionar';

    // Comentarios
    case BlogComentariosVer = 'blog.comentarios.ver';
    case BlogComentariosModerar = 'blog.comentarios.moderar';

    // Datos personales de terceros
    case BlogSuscriptoresGestionar = 'blog.suscriptores.gestionar';
    case BlogContactosGestionar = 'blog.contactos.gestionar';
    case BlogVisitasVer = 'blog.visitas.ver';

    // Tablero
    case BlogAnaliticaVer = 'blog.analitica.ver';

    public function label(): string
    {
        return match ($this) {
            self::UsuariosGestionar => 'Gestionar usuarios y roles',
            self::BlogPublicacionesVer => 'Consultar publicaciones, tutoriales y recursos',
            self::BlogPublicacionesGestionar => 'Crear y editar publicaciones',
            self::BlogPublicacionesPublicar => 'Publicar, programar y retirar del sitio',
            self::BlogPublicacionesEliminar => 'Eliminar publicaciones y archivos',
            self::BlogTaxonomiasVer => 'Consultar categorías y etiquetas',
            self::BlogTaxonomiasGestionar => 'Gestionar categorías y etiquetas',
            self::BlogComentariosVer => 'Consultar comentarios',
            self::BlogComentariosModerar => 'Moderar comentarios',
            self::BlogSuscriptoresGestionar => 'Gestionar suscriptores',
            self::BlogContactosGestionar => 'Atender mensajes de contacto',
            self::BlogVisitasVer => 'Ver la bitácora de visitas con IP',
            self::BlogAnaliticaVer => 'Ver el tablero de analítica',
        };
    }

    /**
     * Los permisos que dan acceso a datos personales de terceros: correos de
     * suscriptores, mensajes de contacto y direcciones IP de los visitantes.
     * Se agrupan aquí para poder excluirlos de un perfil de un vistazo.
     *
     * @return list<self>
     */
    public static function conDatosPersonales(): array
    {
        return [
            self::BlogSuscriptoresGestionar,
            self::BlogContactosGestionar,
            self::BlogVisitasVer,
        ];
    }
}
