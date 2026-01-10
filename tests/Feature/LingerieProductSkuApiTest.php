<?php

use App\Models\LingerieProduct;
use App\Models\LingerieProductColor;
use App\Models\LingerieProductSize;
use App\Models\LingerieProductSku;

describe('GET /api/lingerie/products/{productId}/skus', function () {
    it('returns all skus for a product', function () {
        $product = LingerieProduct::factory()->create();
        $size1 = LingerieProductSize::factory()->create(['size' => 'C70/M']);
        $size2 = LingerieProductSize::factory()->create(['size' => 'B65/S']);
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);

        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product->id,
            'size_id' => $size1->id,
            'color_id' => $color->id,
        ]);
        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product->id,
            'size_id' => $size2->id,
            'color_id' => $color->id,
        ]);

        $response = $this->getJson("/api/lingerie/products/$product->id/skus");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('returns empty array when product has no skus', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->getJson("/api/lingerie/products/$product->id/skus");

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('returns 404 for non-existent product', function () {
        $response = $this->getJson('/api/lingerie/products/999/skus');

        $response->assertNotFound();
    });

    it('includes size and color details in response', function () {
        $product = LingerieProduct::factory()->create();
        $size = LingerieProductSize::factory()->create(['size' => 'C70/M']);
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);

        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
            'price' => 1280.00,
            'stock_quantity' => 10,
        ]);

        $response = $this->getJson("/api/lingerie/products/$product->id/skus");

        $response->assertOk()
            ->assertJsonPath('data.0.size', 'C70/M')
            ->assertJsonPath('data.0.color', '黑色')
            ->assertJsonPath('data.0.price', '1280.00')
            ->assertJsonPath('data.0.stock_quantity', 10);
    });
});

describe('GET /api/lingerie/products/{productId}/skus/{skuId}', function () {
    it('returns a single sku', function () {
        $product = LingerieProduct::factory()->create();
        $size = LingerieProductSize::factory()->create(['size' => 'C70/M']);
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);
        $sku = LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
            'price' => 1280.00,
        ]);

        $response = $this->getJson("/api/lingerie/products/$product->id/skus/$sku->id");

        $response->assertOk()
            ->assertJsonPath('data.id', $sku->id)
            ->assertJsonPath('data.size', 'C70/M')
            ->assertJsonPath('data.color', '黑色');
    });

    it('returns 404 for non-existent sku', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->getJson("/api/lingerie/products/$product->id/skus/999");

        $response->assertNotFound();
    });

    it('returns 404 when sku belongs to different product', function () {
        $product1 = LingerieProduct::factory()->create();
        $product2 = LingerieProduct::factory()->create();
        $sku = LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product2->id,
        ]);

        $response = $this->getJson("/api/lingerie/products/$product1->id/skus/$sku->id");

        $response->assertNotFound();
    });
});

describe('POST /api/lingerie/products/{productId}/skus', function () {
    it('creates a new sku with existing size and color', function () {
        $product = LingerieProduct::factory()->create();
        $size = LingerieProductSize::factory()->create(['size' => 'C70/M']);
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);

        $response = $this->postJson("/api/lingerie/products/$product->id/skus", [
            'size_id' => $size->id,
            'color_id' => $color->id,
            'price' => 1280.00,
            'stock_quantity' => 10,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.size', 'C70/M')
            ->assertJsonPath('data.color', '黑色')
            ->assertJsonPath('data.price', '1280.00')
            ->assertJsonPath('data.stock_quantity', 10);

        $this->assertDatabaseHas('lingerie_product_skus', [
            'lingerie_product_id' => $product->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
            'price' => 1280.00,
            'stock_quantity' => 10,
        ]);
        $this->assertDatabaseCount('lingerie_product_skus', 1);
    });

    it('returns 404 for non-existent product', function () {
        $size = LingerieProductSize::factory()->create();
        $color = LingerieProductColor::factory()->create();

        $response = $this->postJson('/api/lingerie/products/999/skus', [
            'size_id' => $size->id,
            'color_id' => $color->id,
            'price' => 1280.00,
        ]);

        $response->assertNotFound();

        $this->assertDatabaseCount('lingerie_product_skus', 0);
    });

    it('validates size_id is required', function () {
        $product = LingerieProduct::factory()->create();
        $color = LingerieProductColor::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/skus", [
            'color_id' => $color->id,
            'price' => 1280.00,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['size_id']);

        $this->assertDatabaseCount('lingerie_product_skus', 0);
    });

    it('validates color_id is required', function () {
        $product = LingerieProduct::factory()->create();
        $size = LingerieProductSize::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/skus", [
            'size_id' => $size->id,
            'price' => 1280.00,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color_id']);

        $this->assertDatabaseCount('lingerie_product_skus', 0);
    });

    it('validates price is required', function () {
        $product = LingerieProduct::factory()->create();
        $size = LingerieProductSize::factory()->create();
        $color = LingerieProductColor::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/skus", [
            'size_id' => $size->id,
            'color_id' => $color->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);

        $this->assertDatabaseCount('lingerie_product_skus', 0);
    });

    it('validates price is numeric', function () {
        $product = LingerieProduct::factory()->create();
        $size = LingerieProductSize::factory()->create();
        $color = LingerieProductColor::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/skus", [
            'size_id' => $size->id,
            'color_id' => $color->id,
            'price' => 'not-a-number',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);

        $this->assertDatabaseCount('lingerie_product_skus', 0);
    });

    it('validates price is positive', function () {
        $product = LingerieProduct::factory()->create();
        $size = LingerieProductSize::factory()->create();
        $color = LingerieProductColor::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/skus", [
            'size_id' => $size->id,
            'color_id' => $color->id,
            'price' => -100,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);

        $this->assertDatabaseCount('lingerie_product_skus', 0);
    });

    it('validates price max value', function () {
        $product = LingerieProduct::factory()->create();
        $size = LingerieProductSize::factory()->create();
        $color = LingerieProductColor::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/skus", [
            'size_id' => $size->id,
            'color_id' => $color->id,
            'price' => 1000000.00, // exceeds decimal(8,2) max
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);

        $this->assertDatabaseCount('lingerie_product_skus', 0);
    });

    it('validates stock_quantity is integer', function () {
        $product = LingerieProduct::factory()->create();
        $size = LingerieProductSize::factory()->create();
        $color = LingerieProductColor::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/skus", [
            'size_id' => $size->id,
            'color_id' => $color->id,
            'price' => 1280.00,
            'stock_quantity' => 10.5,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['stock_quantity']);

        $this->assertDatabaseCount('lingerie_product_skus', 0);
    });

    it('validates stock_quantity is non-negative', function () {
        $product = LingerieProduct::factory()->create();
        $size = LingerieProductSize::factory()->create();
        $color = LingerieProductColor::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/skus", [
            'size_id' => $size->id,
            'color_id' => $color->id,
            'price' => 1280.00,
            'stock_quantity' => -5,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['stock_quantity']);

        $this->assertDatabaseCount('lingerie_product_skus', 0);
    });

    it('defaults stock_quantity to 0 when not provided', function () {
        $product = LingerieProduct::factory()->create();
        $size = LingerieProductSize::factory()->create();
        $color = LingerieProductColor::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/skus", [
            'size_id' => $size->id,
            'color_id' => $color->id,
            'price' => 1280.00,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.stock_quantity', 0);

        $this->assertDatabaseHas('lingerie_product_skus', [
            'lingerie_product_id' => $product->id,
            'stock_quantity' => 0,
        ]);
    });

    it('validates size_id exists', function () {
        $product = LingerieProduct::factory()->create();
        $color = LingerieProductColor::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/skus", [
            'size_id' => 999,
            'color_id' => $color->id,
            'price' => 1280.00,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['size_id']);

        $this->assertDatabaseCount('lingerie_product_skus', 0);
    });

    it('validates color_id exists', function () {
        $product = LingerieProduct::factory()->create();
        $size = LingerieProductSize::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/skus", [
            'size_id' => $size->id,
            'color_id' => 999,
            'price' => 1280.00,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color_id']);

        $this->assertDatabaseCount('lingerie_product_skus', 0);
    });

    it('validates unique combination of product, size, and color', function () {
        $product = LingerieProduct::factory()->create();
        $size = LingerieProductSize::factory()->create();
        $color = LingerieProductColor::factory()->create();

        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
        ]);

        $response = $this->postJson("/api/lingerie/products/$product->id/skus", [
            'size_id' => $size->id,
            'color_id' => $color->id,
            'price' => 1500.00,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['size_id']);

        $this->assertDatabaseCount('lingerie_product_skus', 1);
    });

    it('allows same size-color combination for different products', function () {
        $product1 = LingerieProduct::factory()->create();
        $product2 = LingerieProduct::factory()->create();
        $size = LingerieProductSize::factory()->create();
        $color = LingerieProductColor::factory()->create();

        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product1->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
        ]);

        $response = $this->postJson("/api/lingerie/products/$product2->id/skus", [
            'size_id' => $size->id,
            'color_id' => $color->id,
            'price' => 1500.00,
        ]);

        $response->assertCreated();

        $this->assertDatabaseCount('lingerie_product_skus', 2);
    });
});

describe('PUT /api/lingerie/products/{productId}/skus/{skuId}', function () {
    it('updates sku price', function () {
        $product = LingerieProduct::factory()->create();
        $sku = LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product->id,
            'price' => 1000.00,
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id/skus/$sku->id", [
            'price' => 1500.00,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.price', '1500.00');

        $this->assertDatabaseHas('lingerie_product_skus', [
            'id' => $sku->id,
            'price' => 1500.00,
        ]);
    });

    it('updates sku stock_quantity', function () {
        $product = LingerieProduct::factory()->create();
        $sku = LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product->id,
            'stock_quantity' => 10,
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id/skus/$sku->id", [
            'stock_quantity' => 25,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.stock_quantity', 25);

        $this->assertDatabaseHas('lingerie_product_skus', [
            'id' => $sku->id,
            'stock_quantity' => 25,
        ]);
    });

    it('updates multiple fields at once', function () {
        $product = LingerieProduct::factory()->create();
        $sku = LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product->id,
            'price' => 1000.00,
            'stock_quantity' => 10,
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id/skus/$sku->id", [
            'price' => 1500.00,
            'stock_quantity' => 25,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.price', '1500.00')
            ->assertJsonPath('data.stock_quantity', 25);

        $this->assertDatabaseHas('lingerie_product_skus', [
            'id' => $sku->id,
            'price' => 1500.00,
            'stock_quantity' => 25,
        ]);
    });

    it('returns 404 for non-existent sku', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->putJson("/api/lingerie/products/$product->id/skus/999", [
            'price' => 1500.00,
        ]);

        $response->assertNotFound();
    });

    it('returns 404 when sku belongs to different product', function () {
        $product1 = LingerieProduct::factory()->create();
        $product2 = LingerieProduct::factory()->create();
        $sku = LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product2->id,
            'price' => 1000.00,
        ]);

        $response = $this->putJson("/api/lingerie/products/$product1->id/skus/$sku->id", [
            'price' => 1500.00,
        ]);

        $response->assertNotFound();

        $this->assertDatabaseHas('lingerie_product_skus', [
            'id' => $sku->id,
            'price' => 1000.00, // unchanged
        ]);
    });

    it('validates price is positive on update', function () {
        $product = LingerieProduct::factory()->create();
        $sku = LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product->id,
            'price' => 1000.00,
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id/skus/$sku->id", [
            'price' => -100,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);

        $this->assertDatabaseHas('lingerie_product_skus', [
            'id' => $sku->id,
            'price' => 1000.00, // unchanged
        ]);
    });

    it('validates stock_quantity is non-negative on update', function () {
        $product = LingerieProduct::factory()->create();
        $sku = LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product->id,
            'stock_quantity' => 10,
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id/skus/$sku->id", [
            'stock_quantity' => -5,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['stock_quantity']);

        $this->assertDatabaseHas('lingerie_product_skus', [
            'id' => $sku->id,
            'stock_quantity' => 10, // unchanged
        ]);
    });

    it('does not allow changing size_id', function () {
        $product = LingerieProduct::factory()->create();
        $size1 = LingerieProductSize::factory()->create();
        $size2 = LingerieProductSize::factory()->create();
        $sku = LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product->id,
            'size_id' => $size1->id,
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id/skus/$sku->id", [
            'size_id' => $size2->id,
        ]);

        $response->assertOk(); // silently ignores size_id

        $this->assertDatabaseHas('lingerie_product_skus', [
            'id' => $sku->id,
            'size_id' => $size1->id, // unchanged
        ]);
    });

    it('does not allow changing color_id', function () {
        $product = LingerieProduct::factory()->create();
        $color1 = LingerieProductColor::factory()->create();
        $color2 = LingerieProductColor::factory()->create();
        $sku = LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product->id,
            'color_id' => $color1->id,
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id/skus/$sku->id", [
            'color_id' => $color2->id,
        ]);

        $response->assertOk(); // silently ignores color_id

        $this->assertDatabaseHas('lingerie_product_skus', [
            'id' => $sku->id,
            'color_id' => $color1->id, // unchanged
        ]);
    });
});