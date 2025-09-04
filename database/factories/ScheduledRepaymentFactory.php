<?php

namespace Database\Factories;

use App\Models\ScheduledRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledRepaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScheduledRepayment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            // TODO: Complete factory
            'loan_id'            => \App\Models\Loan::factory(),
            'amount'             => $this->faker->numberBetween(500, 5000),
            'outstanding_amount' => fn (array $attributes) => $attributes['amount'],
            'currency_code'      => $this->faker->randomElement(['VND', 'SGD']),
            'due_date'           => $this->faker->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'status'             => ScheduledRepayment::STATUS_DUE,
        ];
    }
}
