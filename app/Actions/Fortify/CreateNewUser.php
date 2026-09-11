<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered customer account.
     *
     * Anonymous orders already placed with the same phone number are
     * attached to the new account so its purchase history is complete.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): Customer
    {
        Validator::make($input, [
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(Customer::class)->whereNull('deleted_at')],
            'phone' => ['required', 'string', 'max:255', Rule::unique(Customer::class)->whereNull('deleted_at')],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $customer = Customer::create([
                'email' => $input['email'],
                'phone' => $input['phone'],
                'password' => $input['password'],
            ]);

            Order::query()
                ->where('phone', $customer->phone)
                ->whereNull('customer_id')
                ->update(['customer_id' => $customer->id]);

            return $customer;
        });
    }
}
