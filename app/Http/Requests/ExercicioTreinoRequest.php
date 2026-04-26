<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExercicioTreinoRequest extends FormRequest
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
            'treino_id' => 'required|exists:treinos,id',
            'exercicio_id' => 'required|exists:exercicios,id',
            'num_series' => 'required|integer|min:1',
            'num_repeticoes' => 'required|integer|min:1',
            'carga' => 'nullable|integer|min:0',
            'intervalo' => 'nullable|integer|min:0',
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
            'treino_id.required' => 'O treino é obrigatório.',
            'treino_id.exists' => 'O treino selecionado não existe.',
            'exercicio_id.required' => 'O exercício é obrigatório.',
            'exercicio_id.exists' => 'O exercício selecionado não existe.',
            'num_series.required' => 'O número de séries é obrigatório.',
            'num_series.min' => 'O número de séries deve ser pelo menos 1.',
            'num_repeticoes.required' => 'O número de repetições é obrigatório.',
            'num_repeticoes.min' => 'O número de repetições deve ser pelo menos 1.',
            'carga.min' => 'A carga não pode ser negativa.',
            'intervalo.min' => 'O intervalo não pode ser negativo.',
        ];
    }
}
