<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncidentCommentRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'author_name' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:2000'],
        ];
    }
}
