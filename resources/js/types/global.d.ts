// Esta línea convierte el archivo en módulo; sin ella, `declare module` reemplaza
// los tipos de Inertia en vez de ampliarlos.
export {};

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
