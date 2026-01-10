<?php

use App\Models\LingerieProduct;
use App\Models\LingerieProductColor;
use App\Models\LingerieProductColorImage;
use App\Models\LingerieProductImage;
use App\Models\LingerieProductSize;
use App\Models\LingerieProductSku;

describe('POST /api/lingerie/products (aggregate)', function () {
    it('creates product with skus and auto-creates size and color', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'description' => '舒適透氣',
            'skus' => [
                [
                    'size' => 'C70/M',
                    'color' => '黑色',
                    'price' => 1280.00,
                    'stock_quantity' => 10,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', '蕾絲內衣套裝')
            ->assertJsonPath('data.skus.0.size', 'C70/M')
            ->assertJsonPath('data.skus.0.color', '黑色')
            ->assertJsonCount(1, 'data.skus');

        $this->assertDatabaseHas('lingerie_products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
        ]);
        $this->assertDatabaseCount('lingerie_products', 1);
        $this->assertDatabaseCount('lingerie_product_sizes', 1);
        $this->assertDatabaseCount('lingerie_product_colors', 1);
        $this->assertDatabaseCount('lingerie_product_skus', 1);

        $this->assertDatabaseHas('lingerie_product_sizes', ['size' => 'C70/M']);
        $this->assertDatabaseHas('lingerie_product_colors', ['color' => '黑色']);
        $this->assertDatabaseHas('lingerie_product_skus', [
            'price' => 1280.00,
            'stock_quantity' => 10,
        ]);
    });

    it('creates product with images', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'images' => [
                'https://example.com/image1.jpg',
                'https://example.com/image2.jpg',
                'https://example.com/image3.jpg',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonCount(3, 'data.images');

        $this->assertDatabaseCount('lingerie_product_images', 3);
        $this->assertDatabaseHas('lingerie_product_images', [
            'image_url' => 'https://example.com/image1.jpg',
        ]);
        $this->assertDatabaseHas('lingerie_product_images', [
            'image_url' => 'https://example.com/image2.jpg',
        ]);
        $this->assertDatabaseHas('lingerie_product_images', [
            'image_url' => 'https://example.com/image3.jpg',
        ]);
    });

    it('creates product with color images', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'color_images' => [
                ['color' => '黑色', 'image_url' => 'https://example.com/black.jpg'],
                ['color' => '白色', 'image_url' => 'https://example.com/white.jpg'],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonCount(2, 'data.color_images');

        $this->assertDatabaseCount('lingerie_product_color_images', 2);
        $this->assertDatabaseCount('lingerie_product_colors', 2);
        $this->assertDatabaseHas('lingerie_product_colors', ['color' => '黑色']);
        $this->assertDatabaseHas('lingerie_product_colors', ['color' => '白色']);
    });

    it('creates complete product with skus, images and color images', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'description' => '舒適透氣',
            'images' => [
                'https://example.com/main1.jpg',
                'https://example.com/main2.jpg',
            ],
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色', 'price' => 1280.00],
                ['size' => 'C70/M', 'color' => '白色', 'price' => 1280.00],
            ],
            'color_images' => [
                ['color' => '黑色', 'image_url' => 'https://example.com/black.jpg'],
                ['color' => '白色', 'image_url' => 'https://example.com/white.jpg'],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonCount(2, 'data.images')
            ->assertJsonCount(2, 'data.skus')
            ->assertJsonCount(2, 'data.color_images');

        $this->assertDatabaseCount('lingerie_products', 1);
        $this->assertDatabaseCount('lingerie_product_images', 2);
        $this->assertDatabaseCount('lingerie_product_skus', 2);
        $this->assertDatabaseCount('lingerie_product_color_images', 2);
        $this->assertDatabaseCount('lingerie_product_colors', 2); // reused between skus and color_images
    });

    it('reuses colors between skus and color_images', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色', 'price' => 1280.00],
            ],
            'color_images' => [
                ['color' => '黑色', 'image_url' => 'https://example.com/black.jpg'],
            ],
        ]);

        $response->assertCreated();

        $this->assertDatabaseCount('lingerie_product_colors', 1); // only one color created
    });

    it('reuses existing size and color when names match', function () {
        LingerieProductSize::factory()->create(['size' => 'C70/M']);
        LingerieProductColor::factory()->create(['color' => '黑色']);

        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色', 'price' => 1280.00],
            ],
        ]);

        $response->assertCreated();

        $this->assertDatabaseCount('lingerie_product_sizes', 1);
        $this->assertDatabaseCount('lingerie_product_colors', 1);
    });

    it('creates product with multiple skus', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色', 'price' => 1280.00, 'stock_quantity' => 10],
                ['size' => 'C70/M', 'color' => '白色', 'price' => 1280.00, 'stock_quantity' => 5],
                ['size' => 'B65/S', 'color' => '黑色', 'price' => 1180.00, 'stock_quantity' => 8],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonCount(3, 'data.skus');

        $this->assertDatabaseCount('lingerie_product_skus', 3);
        $this->assertDatabaseCount('lingerie_product_sizes', 2); // C70/M, B65/S
        $this->assertDatabaseCount('lingerie_product_colors', 2); // 黑色, 白色
    });

    it('creates product without skus', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', '蕾絲內衣套裝');

        $this->assertDatabaseCount('lingerie_products', 1);
        $this->assertDatabaseCount('lingerie_product_skus', 0);
        $this->assertDatabaseCount('lingerie_product_sizes', 0);
        $this->assertDatabaseCount('lingerie_product_colors', 0);
    });

    it('validates duplicate sku in same request', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色', 'price' => 1280.00],
                ['size' => 'C70/M', 'color' => '黑色', 'price' => 1500.00], // duplicate
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['skus']);

        $this->assertDatabaseCount('lingerie_products', 0);
        $this->assertDatabaseCount('lingerie_product_skus', 0);
        $this->assertDatabaseCount('lingerie_product_sizes', 0);
        $this->assertDatabaseCount('lingerie_product_colors', 0);
    });

    it('validates skus array items have required fields', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => 'C70/M'], // missing color and price
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['skus.0.color', 'skus.0.price']);

        $this->assertDatabaseCount('lingerie_products', 0);
    });

    it('validates size is required in sku', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['color' => '黑色', 'price' => 1280.00],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['skus.0.size']);

        $this->assertDatabaseCount('lingerie_products', 0);
    });

    it('validates color is required in sku', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => 'C70/M', 'price' => 1280.00],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['skus.0.color']);

        $this->assertDatabaseCount('lingerie_products', 0);
    });

    it('validates price is required in sku', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色'],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['skus.0.price']);

        $this->assertDatabaseCount('lingerie_products', 0);
    });

    it('validates price is positive', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色', 'price' => -100],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['skus.0.price']);

        $this->assertDatabaseCount('lingerie_products', 0);
    });

    it('validates price is numeric', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色', 'price' => 'not-a-number'],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['skus.0.price']);

        $this->assertDatabaseCount('lingerie_products', 0);
    });

    it('validates stock_quantity is non-negative', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色', 'price' => 1280.00, 'stock_quantity' => -5],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['skus.0.stock_quantity']);

        $this->assertDatabaseCount('lingerie_products', 0);
    });

    it('validates stock_quantity is integer', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色', 'price' => 1280.00, 'stock_quantity' => 10.5],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['skus.0.stock_quantity']);

        $this->assertDatabaseCount('lingerie_products', 0);
    });

    it('defaults stock_quantity to 0 when not provided', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色', 'price' => 1280.00],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.skus.0.stock_quantity', 0);

        $this->assertDatabaseHas('lingerie_product_skus', ['stock_quantity' => 0]);
    });

    it('rolls back all changes when sku validation fails', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色', 'price' => 1280.00],
                ['size' => 'B65/S', 'color' => '白色', 'price' => -100], // invalid price
            ],
        ]);

        $response->assertUnprocessable();

        $this->assertDatabaseCount('lingerie_products', 0);
        $this->assertDatabaseCount('lingerie_product_skus', 0);
        $this->assertDatabaseCount('lingerie_product_sizes', 0);
        $this->assertDatabaseCount('lingerie_product_colors', 0);
    });

    it('validates size name max length', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => str_repeat('A', 17), 'color' => '黑色', 'price' => 1280.00],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['skus.0.size']);

        $this->assertDatabaseCount('lingerie_products', 0);
    });

    it('validates color name max length', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => 'C70/M', 'color' => str_repeat('色', 33), 'price' => 1280.00],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['skus.0.color']);

        $this->assertDatabaseCount('lingerie_products', 0);
    });

    it('trims whitespace from size and color names', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => '  C70/M  ', 'color' => '  黑色  ', 'price' => 1280.00],
            ],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('lingerie_product_sizes', ['size' => 'C70/M']);
        $this->assertDatabaseHas('lingerie_product_colors', ['color' => '黑色']);
        $this->assertDatabaseMissing('lingerie_product_sizes', ['size' => '  C70/M  ']);
        $this->assertDatabaseMissing('lingerie_product_colors', ['color' => '  黑色  ']);
    });

    it('validates size is not empty string', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => '', 'color' => '黑色', 'price' => 1280.00],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['skus.0.size']);

        $this->assertDatabaseCount('lingerie_products', 0);
    });

    it('validates color is not empty string', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => 'C70/M', 'color' => '', 'price' => 1280.00],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['skus.0.color']);

        $this->assertDatabaseCount('lingerie_products', 0);
    });

    it('validates skus is an array', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => 'not-an-array',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['skus']);

        $this->assertDatabaseCount('lingerie_products', 0);
    });

    it('validates images is an array', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'images' => 'not-an-array',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['images']);

        $this->assertDatabaseCount('lingerie_products', 0);
    });

    it('validates images items are valid urls', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'images' => [
                'https://example.com/valid.jpg',
                'not-a-valid-url',
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['images.1']);

        $this->assertDatabaseCount('lingerie_products', 0);
        $this->assertDatabaseCount('lingerie_product_images', 0);
    });

    it('validates images items max length', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'images' => [
                'https://example.com/' . str_repeat('a', 2084),
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['images.0']);

        $this->assertDatabaseCount('lingerie_products', 0);
    });

    it('validates color_images is an array', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'color_images' => 'not-an-array',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color_images']);

        $this->assertDatabaseCount('lingerie_products', 0);
    });

    it('validates color_images items have required color', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'color_images' => [
                ['image_url' => 'https://example.com/image.jpg'],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color_images.0.color']);

        $this->assertDatabaseCount('lingerie_products', 0);
    });

    it('validates color_images items have required image_url', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'color_images' => [
                ['color' => '黑色'],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color_images.0.image_url']);

        $this->assertDatabaseCount('lingerie_products', 0);
    });

    it('validates color_images image_url is valid url', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'color_images' => [
                ['color' => '黑色', 'image_url' => 'not-a-valid-url'],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color_images.0.image_url']);

        $this->assertDatabaseCount('lingerie_products', 0);
    });

    it('validates duplicate color in color_images', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'color_images' => [
                ['color' => '黑色', 'image_url' => 'https://example.com/black1.jpg'],
                ['color' => '黑色', 'image_url' => 'https://example.com/black2.jpg'],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color_images']);

        $this->assertDatabaseCount('lingerie_products', 0);
        $this->assertDatabaseCount('lingerie_product_color_images', 0);
    });

    it('rolls back all changes when image validation fails', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色', 'price' => 1280.00],
            ],
            'images' => [
                'https://example.com/valid.jpg',
                'invalid-url',
            ],
        ]);

        $response->assertUnprocessable();

        $this->assertDatabaseCount('lingerie_products', 0);
        $this->assertDatabaseCount('lingerie_product_skus', 0);
        $this->assertDatabaseCount('lingerie_product_images', 0);
    });
});

describe('PUT /api/lingerie/products/{id} (aggregate)', function () {
    it('updates product and adds new skus', function () {
        $product = LingerieProduct::factory()->create([
            'name' => '舊名稱',
            'product_code' => 'LP001',
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id", [
            'name' => '新名稱',
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色', 'price' => 1280.00],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', '新名稱')
            ->assertJsonCount(1, 'data.skus');

        $this->assertDatabaseHas('lingerie_products', [
            'id' => $product->id,
            'name' => '新名稱',
        ]);
        $this->assertDatabaseCount('lingerie_product_skus', 1);
        $this->assertDatabaseCount('lingerie_product_sizes', 1);
        $this->assertDatabaseCount('lingerie_product_colors', 1);
    });

    it('replaces all skus when skus array provided', function () {
        $product = LingerieProduct::factory()->create();
        $size = LingerieProductSize::factory()->create(['size' => 'C70/M']);
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);
        $oldSku = LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id", [
            'skus' => [
                ['size' => 'B65/S', 'color' => '白色', 'price' => 1500.00],
            ],
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data.skus')
            ->assertJsonPath('data.skus.0.size', 'B65/S')
            ->assertJsonPath('data.skus.0.color', '白色');

        $this->assertDatabaseCount('lingerie_product_skus', 1);
        $this->assertDatabaseMissing('lingerie_product_skus', ['id' => $oldSku->id]);
    });

    it('keeps existing skus when skus array not provided', function () {
        $product = LingerieProduct::factory()->create(['name' => '舊名稱']);
        $sku = LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product->id,
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id", [
            'name' => '新名稱',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('lingerie_products', [
            'id' => $product->id,
            'name' => '新名稱',
        ]);
        $this->assertDatabaseHas('lingerie_product_skus', ['id' => $sku->id]);
        $this->assertDatabaseCount('lingerie_product_skus', 1);
    });

    it('clears all skus when empty skus array provided', function () {
        $product = LingerieProduct::factory()->create();
        LingerieProductSku::factory()->count(3)->create([
            'lingerie_product_id' => $product->id,
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id", [
            'skus' => [],
        ]);

        $response->assertOk()
            ->assertJsonCount(0, 'data.skus');

        $this->assertDatabaseCount('lingerie_product_skus', 0);
    });

    it('validates duplicate sku in update request', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->putJson("/api/lingerie/products/$product->id", [
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色', 'price' => 1280.00],
                ['size' => 'C70/M', 'color' => '黑色', 'price' => 1500.00],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['skus']);

        $this->assertDatabaseCount('lingerie_product_skus', 0);
    });

    it('rolls back all changes when sku validation fails on update', function () {
        $product = LingerieProduct::factory()->create(['name' => '舊名稱']);
        $existingSku = LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product->id,
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id", [
            'name' => '新名稱',
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色', 'price' => -100], // invalid
            ],
        ]);

        $response->assertUnprocessable();

        $this->assertDatabaseHas('lingerie_products', [
            'id' => $product->id,
            'name' => '舊名稱', // unchanged
        ]);
        $this->assertDatabaseHas('lingerie_product_skus', [
            'id' => $existingSku->id, // still exists
        ]);
    });

    it('reuses existing size and color when names match on update', function () {
        $product = LingerieProduct::factory()->create();
        $size = LingerieProductSize::factory()->create(['size' => 'C70/M']);
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);

        $response = $this->putJson("/api/lingerie/products/$product->id", [
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色', 'price' => 1500.00],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseCount('lingerie_product_sizes', 1);
        $this->assertDatabaseCount('lingerie_product_colors', 1);
        $this->assertDatabaseHas('lingerie_product_skus', [
            'lingerie_product_id' => $product->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
        ]);
    });

    it('returns 404 for non-existent product', function () {
        $response = $this->putJson('/api/lingerie/products/999', [
            'name' => '新名稱',
        ]);

        $response->assertNotFound();
    });

    it('replaces all images when images array provided', function () {
        $product = LingerieProduct::factory()->create();
        LingerieProductImage::factory()->count(2)->create([
            'lingerie_product_id' => $product->id,
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id", [
            'images' => [
                'https://example.com/new1.jpg',
                'https://example.com/new2.jpg',
                'https://example.com/new3.jpg',
            ],
        ]);

        $response->assertOk()
            ->assertJsonCount(3, 'data.images');

        $this->assertDatabaseCount('lingerie_product_images', 3);
        $this->assertDatabaseHas('lingerie_product_images', [
            'lingerie_product_id' => $product->id,
            'image_url' => 'https://example.com/new1.jpg',
        ]);
    });

    it('keeps existing images when images array not provided', function () {
        $product = LingerieProduct::factory()->create(['name' => '舊名稱']);
        $image = LingerieProductImage::factory()->create([
            'lingerie_product_id' => $product->id,
            'image_url' => 'https://example.com/existing.jpg',
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id", [
            'name' => '新名稱',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('lingerie_product_images', [
            'id' => $image->id,
            'image_url' => 'https://example.com/existing.jpg',
        ]);
        $this->assertDatabaseCount('lingerie_product_images', 1);
    });

    it('clears all images when empty images array provided', function () {
        $product = LingerieProduct::factory()->create();
        LingerieProductImage::factory()->count(3)->create([
            'lingerie_product_id' => $product->id,
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id", [
            'images' => [],
        ]);

        $response->assertOk()
            ->assertJsonCount(0, 'data.images');

        $this->assertDatabaseCount('lingerie_product_images', 0);
    });

    it('validates images items are valid urls on update', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->putJson("/api/lingerie/products/$product->id", [
            'images' => [
                'https://example.com/valid.jpg',
                'not-a-valid-url',
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['images.1']);
    });

    it('replaces all color_images when color_images array provided', function () {
        $product = LingerieProduct::factory()->create();
        LingerieProductColorImage::factory()->count(2)->create([
            'product_id' => $product->id,
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id", [
            'color_images' => [
                ['color' => '黑色', 'image_url' => 'https://example.com/black.jpg'],
                ['color' => '白色', 'image_url' => 'https://example.com/white.jpg'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonCount(2, 'data.color_images');

        $this->assertDatabaseCount('lingerie_product_color_images', 2);
        $this->assertDatabaseHas('lingerie_product_colors', ['color' => '黑色']);
        $this->assertDatabaseHas('lingerie_product_colors', ['color' => '白色']);
    });

    it('keeps existing color_images when color_images array not provided', function () {
        $product = LingerieProduct::factory()->create(['name' => '舊名稱']);
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);
        $colorImage = LingerieProductColorImage::factory()->create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'image_url' => 'https://example.com/existing.jpg',
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id", [
            'name' => '新名稱',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('lingerie_product_color_images', [
            'id' => $colorImage->id,
            'image_url' => 'https://example.com/existing.jpg',
        ]);
        $this->assertDatabaseCount('lingerie_product_color_images', 1);
    });

    it('clears all color_images when empty color_images array provided', function () {
        $product = LingerieProduct::factory()->create();
        LingerieProductColorImage::factory()->count(3)->create([
            'product_id' => $product->id,
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id", [
            'color_images' => [],
        ]);

        $response->assertOk()
            ->assertJsonCount(0, 'data.color_images');

        $this->assertDatabaseCount('lingerie_product_color_images', 0);
    });

    it('validates duplicate color in color_images on update', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->putJson("/api/lingerie/products/$product->id", [
            'color_images' => [
                ['color' => '黑色', 'image_url' => 'https://example.com/black1.jpg'],
                ['color' => '黑色', 'image_url' => 'https://example.com/black2.jpg'],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color_images']);
    });

    it('validates color_images items have required fields on update', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->putJson("/api/lingerie/products/$product->id", [
            'color_images' => [
                ['color' => '黑色'], // missing image_url
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color_images.0.image_url']);
    });

    it('reuses existing colors for color_images on update', function () {
        $product = LingerieProduct::factory()->create();
        LingerieProductColor::factory()->create(['color' => '黑色']);

        $response = $this->putJson("/api/lingerie/products/$product->id", [
            'color_images' => [
                ['color' => '黑色', 'image_url' => 'https://example.com/black.jpg'],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseCount('lingerie_product_colors', 1);
    });

    it('rolls back all changes when image validation fails on update', function () {
        $product = LingerieProduct::factory()->create(['name' => '舊名稱']);
        $existingImage = LingerieProductImage::factory()->create([
            'lingerie_product_id' => $product->id,
            'image_url' => 'https://example.com/existing.jpg',
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id", [
            'name' => '新名稱',
            'images' => [
                'invalid-url',
            ],
        ]);

        $response->assertUnprocessable();

        $this->assertDatabaseHas('lingerie_products', [
            'id' => $product->id,
            'name' => '舊名稱', // unchanged
        ]);
        $this->assertDatabaseHas('lingerie_product_images', [
            'id' => $existingImage->id, // still exists
        ]);
    });

    it('updates product with all nested data at once', function () {
        $product = LingerieProduct::factory()->create(['name' => '舊名稱']);
        LingerieProductSku::factory()->create(['lingerie_product_id' => $product->id]);
        LingerieProductImage::factory()->create(['lingerie_product_id' => $product->id]);
        LingerieProductColorImage::factory()->create(['product_id' => $product->id]);

        $response = $this->putJson("/api/lingerie/products/$product->id", [
            'name' => '新名稱',
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色', 'price' => 1280.00],
                ['size' => 'B65/S', 'color' => '白色', 'price' => 1180.00],
            ],
            'images' => [
                'https://example.com/new1.jpg',
                'https://example.com/new2.jpg',
            ],
            'color_images' => [
                ['color' => '黑色', 'image_url' => 'https://example.com/black.jpg'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', '新名稱')
            ->assertJsonCount(2, 'data.skus')
            ->assertJsonCount(2, 'data.images')
            ->assertJsonCount(1, 'data.color_images');

        $this->assertDatabaseCount('lingerie_product_skus', 2);
        $this->assertDatabaseCount('lingerie_product_images', 2);
        $this->assertDatabaseCount('lingerie_product_color_images', 1);
    });

    it('shares colors between skus and color_images on update', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->putJson("/api/lingerie/products/$product->id", [
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色', 'price' => 1280.00],
            ],
            'color_images' => [
                ['color' => '黑色', 'image_url' => 'https://example.com/black.jpg'],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseCount('lingerie_product_colors', 1); // color shared
    });
});

describe('Aggregate edge cases', function () {
    it('handles multiple products with same size/color names', function () {
        $response1 = $this->postJson('/api/lingerie/products', [
            'name' => '商品一',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色', 'price' => 1280.00],
            ],
        ]);

        $response2 = $this->postJson('/api/lingerie/products', [
            'name' => '商品二',
            'product_code' => 'LP002',
            'skus' => [
                ['size' => 'C70/M', 'color' => '黑色', 'price' => 1500.00],
            ],
        ]);

        $response1->assertCreated();
        $response2->assertCreated();

        $this->assertDatabaseCount('lingerie_products', 2);
        $this->assertDatabaseCount('lingerie_product_skus', 2);
        $this->assertDatabaseCount('lingerie_product_sizes', 1); // reused
        $this->assertDatabaseCount('lingerie_product_colors', 1); // reused
    });

    it('creates multiple sizes and colors in single request', function () {
        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => 'A65/S', 'color' => '黑色', 'price' => 1000.00],
                ['size' => 'B70/M', 'color' => '白色', 'price' => 1100.00],
                ['size' => 'C75/L', 'color' => '粉色', 'price' => 1200.00],
                ['size' => 'D80/L', 'color' => '紅色', 'price' => 1300.00],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonCount(4, 'data.skus');

        $this->assertDatabaseCount('lingerie_product_sizes', 4);
        $this->assertDatabaseCount('lingerie_product_colors', 4);
        $this->assertDatabaseCount('lingerie_product_skus', 4);
    });

    it('handles case sensitivity in size and color matching', function () {
        LingerieProductSize::factory()->create(['size' => 'C70/M']);
        LingerieProductColor::factory()->create(['color' => 'Black']);

        $response = $this->postJson('/api/lingerie/products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'skus' => [
                ['size' => 'c70/m', 'color' => 'black', 'price' => 1280.00], // different case
            ],
        ]);

        $response->assertCreated();

        // Check behavior - either reuses or creates new based on case sensitivity
        $this->assertDatabaseCount('lingerie_products', 1);
        $this->assertDatabaseCount('lingerie_product_skus', 1);
    });
});