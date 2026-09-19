<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

if (!class_exists(UserPreferencesRequest::class)) {
    final class UserPreferencesRequest extends FormRequest
    {
        public function authorize(): bool
        {
            return $this->user() !== null;
        }

        /**
         * @return array<string, list<string>>
         */
        public function rules(): array
        {
            return [
                'locale' => ['nullable', 'string', 'max:10', 'in:pt_BR,pt-BR,pt,en_US,en-US,en'],
                'timezone' => ['nullable', 'string', 'timezone:all'],
                'color_scheme' => ['nullable', 'string', 'in:light,dark,system'],
            ];
        }
    }
}
