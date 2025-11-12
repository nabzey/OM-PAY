<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // API publique pour création de comptes
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_client' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('comptes', 'id_client')
            ],
            'numero_compte' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('comptes', 'numero_compte'),
                'regex:/^OM\d{10,12}$/' // Format Orange Money: OM suivi de 10-12 chiffres
            ],
            'nom' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\-]+$/' // Lettres, espaces et tirets uniquement
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('comptes', 'email')
            ],
            'telephone' => [
                'required',
                'string',
                'regex:/^\+221(77|78|70|76|75)\d{7}$/', // Format international sénégalais: +221 + 77/78/70/76/75 + 7 chiffres (total 9 chiffres après +221)
                Rule::unique('comptes', 'telephone')
            ],
            'type_compte' => [
                'sometimes',
                Rule::in(['courant', 'epargne', 'entreprise'])
            ],
            'statut_compte' => [
                'sometimes',
                Rule::in(['actif', 'inactif', 'bloque', 'suspendu'])
            ],
            'password' => [
                'required',
                'string',
                'min:8'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_client.required' => 'L\'ID client est obligatoire.',
            'id_client.unique' => 'Cet ID client existe déjà.',
            'numero_compte.unique' => 'Ce numéro de compte existe déjà.',
            'numero_compte.regex' => 'Le numéro de compte doit être au format OM suivi de 10 à 12 chiffres.',
            'nom.required' => 'Le nom est obligatoire.',
            'nom.regex' => 'Le nom ne peut contenir que des lettres, espaces et tirets.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email existe déjà.',
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'telephone.regex' => 'Le numéro de téléphone doit être au format sénégalais (+221 + 77/78/70/76/75 + 7 chiffres, total 9 chiffres après +221).',
            'telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'type_compte.in' => 'Le type de compte doit être: courant, epargne ou entreprise.',
            'statut_compte.in' => 'Le statut doit être: actif, inactif, bloque ou suspendu.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'id_client' => 'ID client',
            'numero_compte' => 'numéro de compte',
            'nom' => 'nom',
            'email' => 'email',
            'telephone' => 'numéro de téléphone',
            'type_compte' => 'type de compte',
            'statut_compte' => 'statut du compte',
            'password' => 'mot de passe',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Nettoyer et formater le numéro de téléphone
        if ($this->telephone) {
            $telephone = preg_replace('/\s+/', '', $this->telephone);
            // Ajouter +221 si nécessaire
            if (!str_starts_with($telephone, '+221')) {
                if (preg_match('/^(77|78|70|76|75)/', $telephone)) {
                    $telephone = '+221' . $telephone;
                }
            }
            $this->merge([
                'telephone' => $telephone
            ]);
        }

        // Générer automatiquement l'ID client unique si non fourni
        if (!$this->id_client) {
            do {
                $idClient = 'CLI-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
            } while (\App\Models\Compte::where('id_client', $idClient)->exists());

            $this->merge([
                'id_client' => $idClient
            ]);
        }

        // Générer automatiquement le numéro de compte unique si non fourni
        if (!$this->numero_compte) {
            do {
                $numeroCompte = 'OM' . str_pad(mt_rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
            } while (\App\Models\Compte::where('numero_compte', $numeroCompte)->exists());

            $this->merge([
                'numero_compte' => $numeroCompte
            ]);
        }
    }
}
