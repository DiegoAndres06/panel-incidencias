<?php

// Mensajes de validación en español. Solo incluye las reglas que usa la aplicación;
// el resto cae al idioma de respaldo (inglés).
return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'max' => [
        'string' => 'El campo :attribute no puede superar los :max caracteres.',
    ],
    'enum' => 'El valor seleccionado para :attribute no es válido.',
    'exists' => 'El valor seleccionado para :attribute no es válido.',
    'present' => 'El campo :attribute debe estar presente.',

    // Nombres legibles de los campos, para que los mensajes no muestren `requester_name`.
    'attributes' => [
        'title' => 'título',
        'description' => 'descripción',
        'priority' => 'prioridad',
        'requester_name' => 'solicitante',
        'assigned_to' => 'responsable',
        'status' => 'estado',
        'user_name' => 'nombre',
        'note' => 'motivo',
        'author_name' => 'autor',
        'body' => 'comentario',
    ],
];
