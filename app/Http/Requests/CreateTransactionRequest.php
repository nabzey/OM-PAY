<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'montant' => 'required|numeric|min:100|max:5000000',
            'devise' => 'sometimes|string|size:3|in:XOF,USD,EUR',
            'description' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ];

        // Règles spécifiques selon le type de transaction
        if ($this->route() && str_contains($this->route()->getName(), 'paiement')) {
            $rules = array_merge($rules, [
                'methode_paiement' => 'required|in:code_marchand,numero_telephone',
                'destinataire' => 'required|string',
            ]);

            // Validation spécifique selon la méthode de paiement
            if ($this->input('methode_paiement') === 'code_marchand') {
                $rules['destinataire'] = 'required|string|min:6|max:20|regex:/^[A-Z0-9]+$/';
            } elseif ($this->input('methode_paiement') === 'numero_telephone') {
                $rules['destinataire'] = 'required|string|regex:/^\+221[76-8][0-9]{7}$/';
            }
        }

        if ($this->route() && str_contains($this->route()->getName(), 'transfert')) {
            $rules = array_merge($rules, [
                'destinataire' => 'required|string|regex:/^\+221[76-8][0-9]{7}$/',
            ]);

            // Vérifier que le destinataire n'est pas le même que l'expéditeur
            $rules['destinataire'] .= '|different:auth.user.telephone';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant minimum est de 100 FCFA.',
            'montant.max' => 'Le montant maximum est de 5 000 000 FCFA.',
            'methode_paiement.required' => 'La méthode de paiement est obligatoire.',
            'methode_paiement.in' => 'La méthode de paiement doit être soit "code_marchand" soit "numero_telephone".',
            'destinataire.required' => 'Le destinataire est obligatoire.',
            'destinataire.regex' => 'Le format du destinataire est invalide.',
            'destinataire.different' => 'Vous ne pouvez pas vous transférer de l\'argent à vous-même.',
            'devise.size' => 'La devise doit contenir exactement 3 caractères.',
            'devise.in' => 'La devise doit être XOF, USD ou EUR.',
            'description.max' => 'La description ne peut pas dépasser 255 caractères.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'methode_paiement' => 'méthode de paiement',
            'destinataire' => 'destinataire',
            'montant' => 'montant',
            'devise' => 'devise',
            'description' => 'description',
        ];
    }
}
