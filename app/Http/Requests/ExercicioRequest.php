<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExercicioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'empresa' => 'required|integer',
            'nome' => 'required|string|max:100',
            'video' => 'nullable|string|max:100',
            'grupo_muscular_id' => 'nullable|exists:grupo_musculares,id',
            'status' => 'required|integer|in:0,1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'empresa.required' => 'O campo empresa é obrigatório.',
            'nome.required' => 'O nome do exercício é obrigatório.',
            'grupo_muscular_id.exists' => 'O grupo muscular selecionado não existe.',
            'status.required' => 'O campo status é obrigatório.',
            'status.in' => 'O campo status deve ser 0 (inativo) ou 1 (ativo).',
        ];
    }
}
