const dateFormatter = new Intl.DateTimeFormat('es', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

// Formatea una fecha ISO para mostrarla en español.
export function formatDate(iso: string): string {
    return dateFormatter.format(new Date(iso));
}
