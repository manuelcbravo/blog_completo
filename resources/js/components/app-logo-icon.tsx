import type { ImgHTMLAttributes } from 'react';

/**
 * Isotipo de la marca. Es un PNG con fondo transparente, así que sirve igual
 * en tema claro y oscuro sin necesitar dos archivos.
 */
export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            src="/assets/img/logos/logo_isotipo.png"
            alt=""
            width={32}
            height={32}
            {...props}
        />
    );
}
