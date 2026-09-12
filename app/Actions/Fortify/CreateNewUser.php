<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\Customer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered customer account.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): Customer
    {
        $input['phone'] = preg_replace('/\D/', '', $input['phone'] ?? '');

        Validator::make($input, [
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(Customer::class)->whereNull('deleted_at')],
            'phone' => ['required', 'regex:/^(01|05|07)\d{8}$/', Rule::unique(Customer::class)->whereNull('deleted_at')],
            'password' => $this->passwordRules(),
        ], [
            'phone.regex' => 'Entrez un numéro ivoirien valide (10 chiffres, commençant par 01, 05 ou 07).',
        ])->validate();

        return Customer::create([
            'email' => $input['email'],
            'phone' => $input['phone'],
            'password' => $input['password'],
        ]);
    }
}
