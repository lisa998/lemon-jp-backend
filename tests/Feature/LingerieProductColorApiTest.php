<?php

use App\Models\LingerieProductColor;

describe('GET /api/lingerie/colors', function () {
    it('returns all colors', function () {
        LingerieProductColor::factory()->create(['color' => '黑色']);
        LingerieProductColor::factory()->create(['color' => '白色']);
        LingerieProductColor::factory()->create(['color' => '粉色']);

        $response = $this->getJson('/api/lingerie/colors');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('returns empty array when no colors exist', function () {
        $response = $this->getJson('/api/lingerie/colors');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('returns colors sorted alphabetically', function () {
        LingerieProductColor::factory()->create(['color' => '粉色']);
        LingerieProductColor::factory()->create(['color' => '白色']);
        LingerieProductColor::factory()->create(['color' => '黑色']);

        $response = $this->getJson('/api/lingerie/colors?sort=color');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });
});

describe('GET /api/lingerie/colors/{id}', function () {
    it('returns a single color', function () {
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);

        $response = $this->getJson("/api/lingerie/colors/$color->id");

        $response->assertOk()
            ->assertJsonPath('data.id', $color->id)
            ->assertJsonPath('data.color', '黑色');
    });

    it('returns 404 for non-existent color', function () {
        $response = $this->getJson('/api/lingerie/colors/999');

        $response->assertNotFound();
    });

    it('returns 404 for invalid id format', function () {
        $response = $this->getJson('/api/lingerie/colors/invalid');

        $response->assertNotFound();
    });
});

describe('POST /api/lingerie/colors', function () {
    it('creates a new color', function () {
        $response = $this->postJson('/api/lingerie/colors', [
            'color' => '黑色',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.color', '黑色');

        $this->assertDatabaseHas('lingerie_product_colors', ['color' => '黑色']);
        $this->assertDatabaseCount('lingerie_product_colors', 1);
    });

    it('validates color is required', function () {
        $response = $this->postJson('/api/lingerie/colors');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color']);

        $this->assertDatabaseCount('lingerie_product_colors', 0);
    });

    it('validates color is a string', function () {
        $response = $this->postJson('/api/lingerie/colors', [
            'color' => 12345,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color']);

        $this->assertDatabaseCount('lingerie_product_colors', 0);
    });

    it('validates color max length', function () {
        $response = $this->postJson('/api/lingerie/colors', [
            'color' => str_repeat('色', 33), // max is 32
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color']);

        $this->assertDatabaseCount('lingerie_product_colors', 0);
    });

    it('validates color is unique', function () {
        LingerieProductColor::factory()->create(['color' => '黑色']);

        $response = $this->postJson('/api/lingerie/colors', [
            'color' => '黑色',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color']);

        $this->assertDatabaseCount('lingerie_product_colors', 1);
    });

    it('trims whitespace from color', function () {
        $response = $this->postJson('/api/lingerie/colors', [
            'color' => '  黑色  ',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.color', '黑色');

        $this->assertDatabaseHas('lingerie_product_colors', ['color' => '黑色']);
        $this->assertDatabaseMissing('lingerie_product_colors', ['color' => '  黑色  ']);
    });

    it('rejects empty string', function () {
        $response = $this->postJson('/api/lingerie/colors', [
            'color' => '',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color']);

        $this->assertDatabaseCount('lingerie_product_colors', 0);
    });

    it('rejects whitespace only string', function () {
        $response = $this->postJson('/api/lingerie/colors', [
            'color' => '   ',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color']);

        $this->assertDatabaseCount('lingerie_product_colors', 0);
    });
});

describe('PUT /api/lingerie/colors/{id}', function () {
    it('updates an existing color', function () {
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);

        $response = $this->putJson("/api/lingerie/colors/$color->id", [
            'color' => '白色',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.color', '白色');

        $this->assertDatabaseHas('lingerie_product_colors', [
            'id' => $color->id,
            'color' => '白色',
        ]);
        $this->assertDatabaseMissing('lingerie_product_colors', ['color' => '黑色']);
        $this->assertDatabaseCount('lingerie_product_colors', 1);
    });

    it('returns 404 for non-existent color', function () {
        $response = $this->putJson('/api/lingerie/colors/999', [
            'color' => '黑色',
        ]);

        $response->assertNotFound();

        $this->assertDatabaseCount('lingerie_product_colors', 0);
    });

    it('allows updating to same value', function () {
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);

        $response = $this->putJson("/api/lingerie/colors/$color->id", [
            'color' => '黑色',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('lingerie_product_colors', [
            'id' => $color->id,
            'color' => '黑色',
        ]);
    });

    it('validates color is unique against other colors', function () {
        LingerieProductColor::factory()->create(['color' => '白色']);
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);

        $response = $this->putJson("/api/lingerie/colors/$color->id", [
            'color' => '白色',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color']);

        $this->assertDatabaseHas('lingerie_product_colors', [
            'id' => $color->id,
            'color' => '黑色', // unchanged
        ]);
    });

    it('validates color max length on update', function () {
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);

        $response = $this->putJson("/api/lingerie/colors/$color->id", [
            'color' => str_repeat('色', 33),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color']);

        $this->assertDatabaseHas('lingerie_product_colors', [
            'id' => $color->id,
            'color' => '黑色', // unchanged
        ]);
    });

    it('validates color is required on update', function () {
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);

        $response = $this->putJson("/api/lingerie/colors/$color->id");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color']);

        $this->assertDatabaseHas('lingerie_product_colors', [
            'id' => $color->id,
            'color' => '黑色', // unchanged
        ]);
    });
});