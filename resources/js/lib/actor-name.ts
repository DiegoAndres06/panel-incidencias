// Como la aplicación no tiene inicio de sesión, quien opera escribe su nombre en cada cambio
// (queda en el historial). Para no tener que repetirlo, se recuerda el último en el navegador.

const STORAGE_KEY = 'incidencias.actor_name';

// Misma longitud máxima que valida el servidor.
const MAX_LENGTH = 120;

// El almacenamiento del navegador puede no estar disponible o lanzar un error (modo
// privado, datos bloqueados). En ese caso la aplicación sigue funcionando, solo que sin
// recordar el nombre.

// Devuelve el último nombre guardado, o una cadena vacía si no hay ninguno.
export function readActorName(): string {
    try {
        return window.localStorage.getItem(STORAGE_KEY) ?? '';
    } catch {
        return '';
    }
}

// Guarda el nombre para la próxima vez. Ignora lo que no sea texto o esté vacío.
export function rememberActorName(name: unknown): void {
    if (typeof name !== 'string') {
        return;
    }

    const clean = name.trim().slice(0, MAX_LENGTH);

    if (clean === '') {
        return;
    }

    try {
        window.localStorage.setItem(STORAGE_KEY, clean);
    } catch {
        // Sin almacenamiento disponible: simplemente no se recuerda.
    }
}
