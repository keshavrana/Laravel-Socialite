<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $model = Test::class;
    public function definition()
    {
        return [
            'name' => $this->faker('50'),
            'email' => $this->faker('50'),
            'password' => $this->faker('50')
        ];
    }
}
