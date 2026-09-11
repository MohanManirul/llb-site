<?php

namespace Database\Factories;

use App\Models\College;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<College>
 */
class CollegeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->city().' Law College';

        return [
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'name_bn' => $name,
            'name_en' => $name,
            'district_bn' => fake()->city(),
            'district_en' => fake()->city(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
