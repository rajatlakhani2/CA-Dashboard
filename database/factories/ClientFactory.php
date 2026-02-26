<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'client_code' => $this->faker->unique()->bothify('CL###'),
            'name' => $this->faker->company,
            'entity_type' => $this->faker->randomElement(['Private Limited', 'Proprietorship', 'Partnership']),
            'pan' => $this->faker->regexify('[A-Z]{5}[0-9]{4}[A-Z]{1}'),
            'status' => 'Active',
            'category' => 'A',
            'manager_id' => 1, // Assuming user with ID 1 exists or using a factory for user if needed
            'billing_cycle' => 'Monthly',
            'payment_terms_days' => 30,
        ];
    }
}
