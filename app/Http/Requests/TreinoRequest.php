<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TreinoRequest extends FormRequest
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
            'aluno_id' => 'required|exists:alunos,id',
            'nome' => 'required|string|max:100',
            'exercicios' => 'required|string|max:200',
            'dias_semana' => 'required|array',
            'metodo' => 'required|exists:metodos,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'obs' => 'nullable|string',
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
            'aluno_id.required' => 'O aluno é obrigatório.',
            'aluno_id.exists' => 'O aluno selecionado não existe.',
            'nome.required' => 'O nome do treino é obrigatório.',
            'exercicios.required' => 'Os exercícios são obrigatórios.',
            'dias_semana.required' => 'Os dias da semana são obrigatórios.',
            'metodo.required' => 'O método é obrigatório.',
            'start_date.date' => 'A data de início deve ser uma data válida.',
            'end_date.date' => 'A data de término deve ser uma data válida.',
            'end_date.after_or_equal' => 'A data de término deve ser igual ou posterior à data de início.',
        ];
    }
}
