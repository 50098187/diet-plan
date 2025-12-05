<?php

namespace Tests\Unit;

use App\Data\FoodBasketData;
use PHPUnit\Framework\TestCase;

class FoodBasketDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear cache before each test
        FoodBasketData::clearCache();
    }

    /** @test */
    public function it_returns_all_foods()
    {
        $foods = FoodBasketData::getFoods();

        $this->assertIsArray($foods);
        $this->assertNotEmpty($foods);
    }

    /** @test */
    public function it_returns_exactly_18_food_items()
    {
        $foods = FoodBasketData::getFoods();

        $this->assertCount(18, $foods, 'Food basket should contain exactly 18 items');
    }

    /** @test */
    public function it_caches_foods_for_performance()
    {
        $foods1 = FoodBasketData::getFoods();
        $foods2 = FoodBasketData::getFoods();

        // Should return same instance when cached
        $this->assertSame($foods1, $foods2);
    }

    /** @test */
    public function it_clears_cache()
    {
        $foods1 = FoodBasketData::getFoods();
        FoodBasketData::clearCache();
        $foods2 = FoodBasketData::getFoods();

        // After clearing cache, should return same data but different instance
        $this->assertEquals($foods1, $foods2);
    }

    /** @test */
    public function each_food_has_required_fields()
    {
        $foods = FoodBasketData::getFoods();
        $requiredFields = ['name', 'serving_size', 'protein', 'carbs', 'fat', 'fiber', 'energy_kj', 'cost', 'source'];

        foreach ($foods as $index => $food) {
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey(
                    $field,
                    $food,
                    "Food item #{$index} ({$food['name']}) is missing required field: {$field}"
                );
            }
        }
    }

    /** @test */
    public function all_foods_have_sa_food_basket_source()
    {
        $foods = FoodBasketData::getFoods();

        foreach ($foods as $food) {
            $this->assertEquals('sa_food_basket', $food['source'], "{$food['name']} should have source 'sa_food_basket'");
        }
    }

    /** @test */
    public function all_nutritional_values_are_numeric_and_non_negative()
    {
        $foods = FoodBasketData::getFoods();

        foreach ($foods as $food) {
            $this->assertIsNumeric($food['protein'], "{$food['name']} protein should be numeric");
            $this->assertGreaterThanOrEqual(0, $food['protein'], "{$food['name']} protein should be >= 0");

            $this->assertIsNumeric($food['carbs'], "{$food['name']} carbs should be numeric");
            $this->assertGreaterThanOrEqual(0, $food['carbs'], "{$food['name']} carbs should be >= 0");

            $this->assertIsNumeric($food['fat'], "{$food['name']} fat should be numeric");
            $this->assertGreaterThanOrEqual(0, $food['fat'], "{$food['name']} fat should be >= 0");

            $this->assertIsNumeric($food['fiber'], "{$food['name']} fiber should be numeric");
            $this->assertGreaterThanOrEqual(0, $food['fiber'], "{$food['name']} fiber should be >= 0");
        }
    }

    /** @test */
    public function all_energy_kj_values_are_positive()
    {
        $foods = FoodBasketData::getFoods();

        foreach ($foods as $food) {
            $this->assertIsNumeric($food['energy_kj'], "{$food['name']} energy_kj should be numeric");
            $this->assertGreaterThan(0, $food['energy_kj'], "{$food['name']} energy_kj should be > 0");
        }
    }

    /** @test */
    public function all_costs_are_positive()
    {
        $foods = FoodBasketData::getFoods();

        foreach ($foods as $food) {
            $this->assertIsNumeric($food['cost'], "{$food['name']} cost should be numeric");
            $this->assertGreaterThan(0, $food['cost'], "{$food['name']} cost should be > 0");
        }
    }

    /** @test */
    public function it_returns_correct_count()
    {
        $count = FoodBasketData::count();

        $this->assertEquals(18, $count);
    }

    /** @test */
    public function it_gets_food_by_valid_index()
    {
        $food = FoodBasketData::getFood(0);

        $this->assertIsArray($food);
        $this->assertArrayHasKey('name', $food);
        $this->assertEquals('Eggs', $food['name']);
    }

    /** @test */
    public function it_returns_null_for_invalid_index()
    {
        $food = FoodBasketData::getFood(999);

        $this->assertNull($food);
    }

    /** @test */
    public function it_finds_food_by_name()
    {
        $food = FoodBasketData::findByName('Eggs');

        $this->assertIsArray($food);
        $this->assertEquals('Eggs', $food['name']);
    }

    /** @test */
    public function it_finds_food_by_name_case_insensitive()
    {
        $food = FoodBasketData::findByName('eggs');

        $this->assertIsArray($food);
        $this->assertEquals('Eggs', $food['name']);
    }

    /** @test */
    public function it_returns_null_for_non_existent_food_name()
    {
        $food = FoodBasketData::findByName('Non Existent Food');

        $this->assertNull($food);
    }

    /** @test */
    public function it_validates_food_data_successfully()
    {
        $isValid = FoodBasketData::validate();

        $this->assertTrue($isValid, 'Food basket data should be valid');
        $this->assertEmpty(FoodBasketData::getValidationErrors());
    }

    /** @test */
    public function it_includes_protein_sources()
    {
        $foods = FoodBasketData::getFoods();
        $foodNames = array_column($foods, 'name');

        $this->assertContains('Eggs', $foodNames);
        $this->assertContains('Beef mince', $foodNames);
        $this->assertContains('IQF chicken portions', $foodNames);
        $this->assertContains('Fish (tinned)', $foodNames);
    }

    /** @test */
    public function it_includes_carbohydrate_sources()
    {
        $foods = FoodBasketData::getFoods();
        $foodNames = array_column($foods, 'name');

        $this->assertContains('Brown bread', $foodNames);
        $this->assertContains('Maize meal', $foodNames);
        $this->assertContains('Rice', $foodNames);
    }

    /** @test */
    public function it_includes_vegetables()
    {
        $foods = FoodBasketData::getFoods();
        $foodNames = array_column($foods, 'name');

        $this->assertContains('Onions', $foodNames);
        $this->assertContains('Potatoes', $foodNames);
        $this->assertContains('Tomatoes', $foodNames);
        $this->assertContains('Cabbage', $foodNames);
    }

    /** @test */
    public function it_includes_fruits()
    {
        $foods = FoodBasketData::getFoods();
        $foodNames = array_column($foods, 'name');

        $this->assertContains('Apples', $foodNames);
        $this->assertContains('Bananas', $foodNames);
        $this->assertContains('Oranges', $foodNames);
    }

    /** @test */
    public function all_foods_have_package_information()
    {
        $foods = FoodBasketData::getFoods();

        foreach ($foods as $food) {
            $this->assertArrayHasKey('package_price', $food, "{$food['name']} should have package_price");
            $this->assertArrayHasKey('package_size', $food, "{$food['name']} should have package_size");
        }
    }
}
