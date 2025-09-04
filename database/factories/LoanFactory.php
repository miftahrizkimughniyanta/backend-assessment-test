<?php

namespace Database\Factories;

use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Loan::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            // TODO: Complete factory
            'user_id'            => \App\Models\User::factory(),
            'amount'             => $this->faker->numberBetween(1000, 10000),
            'terms'              => $this->faker->randomElement([3, 6]),
            'outstanding_amount' => fn (array $attributes) => $attributes['amount'],
            'currency_code'      => $this->faker->randomElement([Loan::CURRENCY_SGD, Loan::CURRENCY_VND]),
            'processed_at'       => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d H:i:s'),
            'status'             => Loan::STATUS_DUE,
        ];
    }
}
