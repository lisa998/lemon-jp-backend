<?php

use App\Models\LingerieProductSize;

describe('GET /api/lingerie/sizes', function () {
    it('returns all sizes', function () {
        LingerieProductSize::factory()->create(['size' => 'C70/M']);
        LingerieProductSize::factory()->create(['size' => 'B65/S']);
        LingerieProductSize::factory()->create(['size' => 'D75/L']);

        $response = $this->getJson('/api/lingerie/sizes');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('returns empty array when no sizes exist', function () {
        $response = $this->getJson('/api/lingerie/sizes');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('returns sizes sorted alphabetically', function () {
        LingerieProductSize::factory()->create(['size' => 'D75/L']);
        LingerieProductSize::factory()->create(['size' => 'B65/S']);
        LingerieProductSize::factory()->create(['size' => 'C70/M']);

        $response = $this->getJson('/api/lingerie/sizes?sort=size');

        $response->assertOk()
            ->assertJsonPath('data.0.size', 'B65/S')
            ->assertJsonPath('data.1.size', 'C70/M')
            ->assertJsonPath('data.2.size', 'D75/L');
    });
});

describe('GET /api/lingerie/sizes/{id}', function () {
    it('returns a single size', function () {
        $size = LingerieProductSize::factory()->create(['size' => 'C70/M']);

        $response = $this->getJson("/api/lingerie/sizes/$size->id");

        $response->assertOk()
            ->assertJsonPath('data.id', $size->id)
            ->assertJsonPath('data.size', 'C70/M');
    });

    it('returns 404 for non-existent size', function () {
        $response = $this->getJson('/api/lingerie/sizes/999');

        $response->assertNotFound();
    });

    it('returns 404 for invalid id format', function () {
        $response = $this->getJson('/api/lingerie/sizes/invalid');

        $response->assertNotFound();
    });
});

describe('POST /api/lingerie/sizes', function () {
    it('creates a new size', function () {
        $response = $this->postJson('/api/lingerie/sizes', [
            'size' => 'C70/M',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.size', 'C70/M');

        $this->assertDatabaseHas('lingerie_product_sizes', ['size' => 'C70/M']);
        $this->assertDatabaseCount('lingerie_product_sizes', 1);
    });

    it('validates size is required', function () {
        $response = $this->postJson('/api/lingerie/sizes');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['size']);

        $this->assertDatabaseCount('lingerie_product_sizes', 0);
    });

    it('validates size is a string', function () {
        $response = $this->postJson('/api/lingerie/sizes', [
            'size' => 12345,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['size']);

        $this->assertDatabaseCount('lingerie_product_sizes', 0);
    });

    it('validates size max length', function () {
        $response = $this->postJson('/api/lingerie/sizes', [
            'size' => str_repeat('A', 17), // max is 16
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['size']);

        $this->assertDatabaseCount('lingerie_product_sizes', 0);
    });

    it('validates size is unique', function () {
        LingerieProductSize::factory()->create(['size' => 'C70/M']);

        $response = $this->postJson('/api/lingerie/sizes', [
            'size' => 'C70/M',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['size']);

        $this->assertDatabaseCount('lingerie_product_sizes', 1);
    });

    it('trims whitespace from size', function () {
        $response = $this->postJson('/api/lingerie/sizes', [
            'size' => '  C70/M  ',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.size', 'C70/M');

        $this->assertDatabaseHas('lingerie_product_sizes', ['size' => 'C70/M']);
        $this->assertDatabaseMissing('lingerie_product_sizes', ['size' => '  C70/M  ']);
    });

    it('rejects empty string', function () {
        $response = $this->postJson('/api/lingerie/sizes', [
            'size' => '',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['size']);

        $this->assertDatabaseCount('lingerie_product_sizes', 0);
    });

    it('rejects whitespace only string', function () {
        $response = $this->postJson('/api/lingerie/sizes', [
            'size' => '   ',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['size']);

        $this->assertDatabaseCount('lingerie_product_sizes', 0);
    });
});

describe('PUT /api/lingerie/sizes/{id}', function () {
    it('updates an existing size', function () {
        $size = LingerieProductSize::factory()->create(['size' => 'C70/M']);

        $response = $this->putJson("/api/lingerie/sizes/$size->id", [
            'size' => 'D75/L',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.size', 'D75/L');

        $this->assertDatabaseHas('lingerie_product_sizes', [
            'id' => $size->id,
            'size' => 'D75/L',
        ]);
        $this->assertDatabaseMissing('lingerie_product_sizes', ['size' => 'C70/M']);
        $this->assertDatabaseCount('lingerie_product_sizes', 1);
    });

    it('returns 404 for non-existent size', function () {
        $response = $this->putJson('/api/lingerie/sizes/999', [
            'size' => 'C70/M',
        ]);

        $response->assertNotFound();

        $this->assertDatabaseCount('lingerie_product_sizes', 0);
    });

    it('allows updating to same value', function () {
        $size = LingerieProductSize::factory()->create(['size' => 'C70/M']);

        $response = $this->putJson("/api/lingerie/sizes/$size->id", [
            'size' => 'C70/M',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('lingerie_product_sizes', [
            'id' => $size->id,
            'size' => 'C70/M',
        ]);
    });

    it('validates size is unique against other sizes', function () {
        LingerieProductSize::factory()->create(['size' => 'D75/L']);
        $size = LingerieProductSize::factory()->create(['size' => 'C70/M']);

        $response = $this->putJson("/api/lingerie/sizes/$size->id", [
            'size' => 'D75/L',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['size']);

        $this->assertDatabaseHas('lingerie_product_sizes', [
            'id' => $size->id,
            'size' => 'C70/M', // unchanged
        ]);
    });

    it('validates size max length on update', function () {
        $size = LingerieProductSize::factory()->create(['size' => 'C70/M']);

        $response = $this->putJson("/api/lingerie/sizes/$size->id", [
            'size' => str_repeat('A', 17),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['size']);

        $this->assertDatabaseHas('lingerie_product_sizes', [
            'id' => $size->id,
            'size' => 'C70/M', // unchanged
        ]);
    });

    it('validates size is required on update', function () {
        $size = LingerieProductSize::factory()->create(['size' => 'C70/M']);

        $response = $this->putJson("/api/lingerie/sizes/$size->id");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['size']);

        $this->assertDatabaseHas('lingerie_product_sizes', [
            'id' => $size->id,
            'size' => 'C70/M', // unchanged
        ]);
    });
});