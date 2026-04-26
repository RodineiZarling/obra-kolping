<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AlunoRequest extends FormRequest
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
            'matricula' => 'nullable|integer',
            'foto' => 'nullable|string|max:255',
            'nome' => 'required|string|max:100',
            'cpf_cnpj' => 'required|string|max:20',
            'email' => 'required|email|max:100',
            'telefone' => 'required|string|max:20',
            'sexo' => 'required|string|max:1',
            'rua' => 'nullable|string|max:100',
            'numero' => 'nullable|string|max:10',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'nullable|string|max:50',
            'cidade' => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:10',
            'uf' => 'nullable|string|max:2',
            'senha' => 'nullable|string|max:255',
            'data_venc_avaliacao' => 'nullable|date',
            'objetivo' => 'nullable|string',
            'biometria' => 'nullable|string',
            'area_atencao' => 'nullable|string',
            'ativ_laboral' => 'nullable|string',
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
            'nome.required' => 'O campo nome é obrigatório.',
            'cpf_cnpj.required' => 'O campo CPF/CNPJ é obrigatório.',
            'email.required' => 'O campo e-mail é obrigatório.',
            'email.email' => 'O campo e-mail deve ser um endereço de e-mail válido.',
            'telefone.required' => 'O campo telefone é obrigatório.',
            'sexo.required' => 'O campo sexo é obrigatório.',
            'status.required' => 'O campo status é obrigatório.',
            'status.in' => 'O campo status deve ser 0 (inativo) ou 1 (ativo).',
        ];
    }
}
