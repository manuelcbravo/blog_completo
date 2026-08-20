import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import {
    ChevronRight,
    FolderTree,
    GraduationCap,
    Inbox,
    LayoutGrid,
    Mail,
    MessageSquare,
    Newspaper,
    Package,
    Settings,
    Tags,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavUser } from '@/components/nav-user';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { IsCurrentUrlFn } from '@/hooks/use-current-url';
import { usePermisos } from '@/hooks/use-permisos';
import { dashboard } from '@/routes';
import { index as blogCategorias } from '@/routes/blog/categorias';
import { index as blogComentarios } from '@/routes/blog/comentarios';
import { index as blogContactos } from '@/routes/blog/contactos';
import { index as blogEtiquetas } from '@/routes/blog/etiquetas';
import { index as blogPublicaciones } from '@/routes/blog/publicaciones';
import { index as blogSuscriptores } from '@/routes/blog/suscriptores';
import { index as configRoles } from '@/routes/config/roles';
import { index as configUsers } from '@/routes/config/users';

type Href = NonNullable<InertiaLinkProps['href']>;
type Enlace = { title: string; href: Href; icon: LucideIcon };
type Grupo = {
    title: string;
    icon: LucideIcon;
    items: { title: string; href: Href }[];
};

export function AppSidebar() {
    const { puede } = usePermisos();
    const { isCurrentUrl } = useCurrentUrl();

    const enlaces: Enlace[] = [
        { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
        ...(puede('blog.publicaciones.gestionar')
            ? [
                  {
                      title: 'Publicaciones',
                      href: blogPublicaciones('posts'),
                      icon: Newspaper,
                  },
                  {
                      title: 'Tutoriales',
                      href: blogPublicaciones('tutoriales'),
                      icon: GraduationCap,
                  },
                  {
                      title: 'Recursos',
                      href: blogPublicaciones('recursos'),
                      icon: Package,
                  },
              ]
            : []),
        ...(puede('blog.taxonomias.gestionar')
            ? [
                  {
                      title: 'Categorías',
                      href: blogCategorias(),
                      icon: FolderTree,
                  },
                  { title: 'Etiquetas', href: blogEtiquetas(), icon: Tags },
              ]
            : []),
        ...(puede('blog.comentarios.moderar')
            ? [
                  {
                      title: 'Comentarios',
                      href: blogComentarios(),
                      icon: MessageSquare,
                  },
              ]
            : []),
        ...(puede('blog.suscriptores.gestionar')
            ? [
                  {
                      title: 'Suscriptores',
                      href: blogSuscriptores(),
                      icon: Mail,
                  },
              ]
            : []),
        ...(puede('blog.contactos.gestionar')
            ? [{ title: 'Mensajes', href: blogContactos(), icon: Inbox }]
            : []),
    ];

    const configuracionItems = [
        ...(puede('usuarios.gestionar')
            ? [
                  { title: 'Usuarios', href: configUsers() },
                  { title: 'Roles y permisos', href: configRoles() },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <SidebarGroup className="px-2 py-0">
                    <SidebarMenu>
                        {enlaces.map((enlace) => (
                            <SidebarMenuItem key={enlace.title}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={isCurrentUrl(enlace.href)}
                                    tooltip={{ children: enlace.title }}
                                >
                                    <Link href={enlace.href} prefetch>
                                        <enlace.icon />
                                        <span>{enlace.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        ))}
                    </SidebarMenu>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter>
                {configuracionItems.length > 0 && (
                    <SidebarMenu>
                        <GrupoNav
                            grupo={{
                                title: 'Configuración',
                                icon: Settings,
                                items: configuracionItems,
                            }}
                            isCurrentUrl={isCurrentUrl}
                        />
                    </SidebarMenu>
                )}
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

function GrupoNav({
    grupo,
    isCurrentUrl,
}: {
    grupo: Grupo;
    isCurrentUrl: IsCurrentUrlFn;
}) {
    const abierto = grupo.items.some((item) => isCurrentUrl(item.href));

    return (
        <Collapsible defaultOpen={abierto} className="group/collapsible">
            <SidebarMenuItem>
                <CollapsibleTrigger asChild>
                    <SidebarMenuButton tooltip={{ children: grupo.title }}>
                        <grupo.icon />
                        <span>{grupo.title}</span>
                        <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                    </SidebarMenuButton>
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <SidebarMenuSub>
                        {grupo.items.map((item) => (
                            <SidebarMenuSubItem key={item.title}>
                                <SidebarMenuSubButton
                                    asChild
                                    isActive={isCurrentUrl(item.href)}
                                >
                                    <Link href={item.href} prefetch>
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        ))}
                    </SidebarMenuSub>
                </CollapsibleContent>
            </SidebarMenuItem>
        </Collapsible>
    );
}
