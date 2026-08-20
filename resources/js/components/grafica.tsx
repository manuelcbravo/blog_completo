import Highcharts from 'highcharts';
import { useEffect, useMemo, useRef } from 'react';
import { useAppearance } from '@/hooks/use-appearance';

/**
 * Paleta categórica validada para ambos modos: los tres primeros slots de la
 * guía de visualización (azul, naranja, aqua). Cambiarlos exige volver a correr
 * el validador de contraste y separación para daltonismo.
 */
export const SERIES_CLARO = ['#2a78d6', '#eb6834', '#1baf7a'] as const;
export const SERIES_OSCURO = ['#3987e5', '#d95926', '#199e70'] as const;

type GraficaProps = {
    options: Highcharts.Options;
    alto?: number;
    etiqueta: string;
};

function tema(oscuro: boolean, alto: number): Highcharts.Options {
    const texto = oscuro ? '#a1a1a1' : '#737373';
    const rejilla = oscuro ? '#262626' : '#e5e5e5';
    const superficie = oscuro ? '#0a0a0a' : '#ffffff';

    return {
        colors: [...(oscuro ? SERIES_OSCURO : SERIES_CLARO)],
        chart: {
            height: alto,
            backgroundColor: 'transparent',
            spacing: [8, 4, 4, 4],
            style: { fontFamily: 'inherit' },
        },
        title: { text: undefined },
        credits: { enabled: false },
        accessibility: { enabled: false },
        legend: {
            itemStyle: { color: texto, fontWeight: '500' },
            itemHoverStyle: { color: oscuro ? '#fafafa' : '#171717' },
            symbolRadius: 2,
        },
        xAxis: {
            lineColor: rejilla,
            tickColor: rejilla,
            gridLineColor: rejilla,
            labels: { style: { color: texto, fontSize: '11px' } },
        },
        yAxis: {
            gridLineColor: rejilla,
            title: { text: undefined },
            labels: { style: { color: texto, fontSize: '11px' } },
        },
        tooltip: {
            backgroundColor: superficie,
            borderColor: rejilla,
            borderRadius: 8,
            shadow: false,
            style: { color: oscuro ? '#fafafa' : '#171717', fontSize: '12px' },
        },
        plotOptions: {
            series: {
                animation: { duration: 250 },
                borderWidth: 0,
                lineWidth: 2,
                marker: { radius: 4, symbol: 'circle' },
            },
        },
    };
}

/**
 * Se monta Highcharts a mano en lugar de usar highcharts-react-official: ese
 * paquete se publica en CommonJS y con Vite la importación por defecto llega
 * como objeto de módulo, no como componente, y React revienta al renderizarlo.
 */
export function Grafica({ options, alto = 260, etiqueta }: GraficaProps) {
    const contenedor = useRef<HTMLDivElement>(null);
    const grafica = useRef<Highcharts.Chart | null>(null);
    const { resolvedAppearance } = useAppearance();
    const oscuro = resolvedAppearance === 'dark';

    const opciones = useMemo(
        () => Highcharts.merge(tema(oscuro, alto), options),
        [oscuro, alto, options],
    );

    useEffect(() => {
        if (contenedor.current === null) {
            return;
        }

        grafica.current = Highcharts.chart(contenedor.current, opciones);

        return () => {
            grafica.current?.destroy();
            grafica.current = null;
        };
    }, [opciones]);

    return <div ref={contenedor} role="img" aria-label={etiqueta} />;
}
