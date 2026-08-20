import AppLogoIcon from '@/components/app-logo-icon';

/**
 * Marca del panel: isotipo + logotipo. El texto va en dos archivos porque
 * cambia de color con el tema, y se oculta solo cuando la barra lateral se
 * colapsa a iconos.
 */
export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 shrink-0 items-center justify-center">
                <AppLogoIcon className="size-7" />
            </div>
            <div className="ml-1 grid flex-1 text-left">
                <img
                    src="/assets/img/logos/logo_texto.png"
                    alt="laravel_conmanuel"
                    className="h-4 w-auto object-contain object-left dark:hidden"
                />
                <img
                    src="/assets/img/logos/logo_texto_dark.png"
                    alt="laravel_conmanuel"
                    className="hidden h-4 w-auto object-contain object-left dark:block"
                />
            </div>
        </>
    );
}
