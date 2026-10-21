import { formatDate } from '@/lib/incidents/format';
import type { IncidentComment } from '@/types';

// Comentarios de una incidencia, del más reciente al más antiguo.
export function CommentList({ comments }: { comments: IncidentComment[] }) {
    if (comments.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                Todavía no hay comentarios.
            </p>
        );
    }

    return (
        <ul className="grid gap-4">
            {comments.map((comment) => (
                <li
                    key={comment.id}
                    className="grid gap-1 rounded-lg border p-3"
                >
                    <p className="text-sm whitespace-pre-wrap">
                        {comment.body}
                    </p>
                    <p className="text-xs text-muted-foreground">
                        {comment.author_name} · {formatDate(comment.created_at)}
                    </p>
                </li>
            ))}
        </ul>
    );
}
