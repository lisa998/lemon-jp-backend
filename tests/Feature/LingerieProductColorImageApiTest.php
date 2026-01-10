<?php

use App\Models\LingerieProduct;
use App\Models\LingerieProductColor;
use App\Models\LingerieProductColorImage;

describe('GET /api/lingerie/products/{productId}/color-images', function () {
    it('returns all color images for a product', function () {
        $product = LingerieProduct::factory()->create();
        $color1 = LingerieProductColor::factory()->create(['color' => '黑色']);
        $color2 = LingerieProductColor::factory()->create(['color' => '白色']);

        LingerieProductColorImage::factory()->create([
            'product_id' => $product->id,
            'color_id' => $color1->id,
        ]);
        LingerieProductColorImage::factory()->create([
            'product_id' => $product->id,
            'color_id' => $color2->id,
        ]);

        $response = $this->getJson("/api/lingerie/products/$product->id/color-images");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('returns empty array when product has no color images', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->getJson("/api/lingerie/products/$product->id/color-images");

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('returns 404 for non-existent product', function () {
        $response = $this->getJson('/api/lingerie/products/999/color-images');

        $response->assertNotFound();
    });

    it('includes color name in response', function () {
        $product = LingerieProduct::factory()->create();
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);

        LingerieProductColorImage::factory()->create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'image_url' => 'https://example.com/black.jpg',
        ]);

        $response = $this->getJson("/api/lingerie/products/$product->id/color-images");

        $response->assertOk()
            ->assertJsonPath('data.0.color', '黑色')
            ->assertJsonPath('data.0.image_url', 'https://example.com/black.jpg');
    });
});

describe('POST /api/lingerie/products/{productId}/color-images', function () {
    it('creates color image with existing color_id', function () {
        $product = LingerieProduct::factory()->create();
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);

        $response = $this->postJson("/api/lingerie/products/$product->id/color-images", [
            'color_id' => $color->id,
            'image_url' => 'https://example.com/black.jpg',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.color', '黑色')
            ->assertJsonPath('data.image_url', 'https://example.com/black.jpg');

        $this->assertDatabaseHas('lingerie_product_color_images', [
            'product_id' => $product->id,
            'color_id' => $color->id,
            'image_url' => 'https://example.com/black.jpg',
        ]);
        $this->assertDatabaseCount('lingerie_product_color_images', 1);
    });

    it('creates color image with color name (insert if not exist)', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/color-images", [
            'color' => '黑色',
            'image_url' => 'https://example.com/black.jpg',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.color', '黑色');

        $this->assertDatabaseHas('lingerie_product_colors', ['color' => '黑色']);
        $this->assertDatabaseCount('lingerie_product_color_images', 1);
        $this->assertDatabaseCount('lingerie_product_colors', 1);
    });

    it('reuses existing color when name matches', function () {
        $product = LingerieProduct::factory()->create();
        $existingColor = LingerieProductColor::factory()->create(['color' => '黑色']);

        $response = $this->postJson("/api/lingerie/products/$product->id/color-images", [
            'color' => '黑色',
            'image_url' => 'https://example.com/black.jpg',
        ]);

        $response->assertCreated();

        $this->assertDatabaseCount('lingerie_product_colors', 1);
        $this->assertDatabaseHas('lingerie_product_color_images', [
            'color_id' => $existingColor->id,
        ]);
    });

    it('returns 404 for non-existent product', function () {
        $color = LingerieProductColor::factory()->create();

        $response = $this->postJson('/api/lingerie/products/999/color-images', [
            'color_id' => $color->id,
            'image_url' => 'https://example.com/image.jpg',
        ]);

        $response->assertNotFound();

        $this->assertDatabaseCount('lingerie_product_color_images', 0);
    });

    it('validates color or color_id is required', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/color-images", [
            'image_url' => 'https://example.com/image.jpg',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color']);

        $this->assertDatabaseCount('lingerie_product_color_images', 0);
    });

    it('validates image_url is required', function () {
        $product = LingerieProduct::factory()->create();
        $color = LingerieProductColor::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/color-images", [
            'color_id' => $color->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image_url']);

        $this->assertDatabaseCount('lingerie_product_color_images', 0);
    });

    it('validates image_url is valid url', function () {
        $product = LingerieProduct::factory()->create();
        $color = LingerieProductColor::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/color-images", [
            'color_id' => $color->id,
            'image_url' => 'not-a-valid-url',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image_url']);

        $this->assertDatabaseCount('lingerie_product_color_images', 0);
    });

    it('validates color_id exists', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/color-images", [
            'color_id' => 999,
            'image_url' => 'https://example.com/image.jpg',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color_id']);

        $this->assertDatabaseCount('lingerie_product_color_images', 0);
    });

    it('enforces one image per product-color combination', function () {
        $product = LingerieProduct::factory()->create();
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);

        LingerieProductColorImage::factory()->create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'image_url' => 'https://example.com/old.jpg',
        ]);

        $response = $this->postJson("/api/lingerie/products/$product->id/color-images", [
            'color_id' => $color->id,
            'image_url' => 'https://example.com/new.jpg',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color_id']);

        $this->assertDatabaseCount('lingerie_product_color_images', 1);
        $this->assertDatabaseHas('lingerie_product_color_images', [
            'image_url' => 'https://example.com/old.jpg', // unchanged
        ]);
    });

    it('allows same color for different products', function () {
        $product1 = LingerieProduct::factory()->create();
        $product2 = LingerieProduct::factory()->create();
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);

        $this->postJson("/api/lingerie/products/$product1->id/color-images", [
            'color_id' => $color->id,
            'image_url' => 'https://example.com/product1-black.jpg',
        ])->assertCreated();

        $this->postJson("/api/lingerie/products/$product2->id/color-images", [
            'color_id' => $color->id,
            'image_url' => 'https://example.com/product2-black.jpg',
        ])->assertCreated();

        $this->assertDatabaseCount('lingerie_product_color_images', 2);
    });

    it('validates color name max length', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/color-images", [
            'color' => str_repeat('色', 33),
            'image_url' => 'https://example.com/image.jpg',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color']);

        $this->assertDatabaseCount('lingerie_product_color_images', 0);
        $this->assertDatabaseCount('lingerie_product_colors', 0);
    });

    it('trims whitespace from color name', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/color-images", [
            'color' => '  黑色  ',
            'image_url' => 'https://example.com/black.jpg',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('lingerie_product_colors', ['color' => '黑色']);
        $this->assertDatabaseMissing('lingerie_product_colors', ['color' => '  黑色  ']);
    });
});

describe('PUT /api/lingerie/products/{productId}/color-images/{colorImageId}', function () {
    it('updates color image url', function () {
        $product = LingerieProduct::factory()->create();
        $color = LingerieProductColor::factory()->create();
        $colorImage = LingerieProductColorImage::factory()->create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'image_url' => 'https://example.com/old.jpg',
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id/color-images/$colorImage->id", [
            'image_url' => 'https://example.com/new.jpg',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.image_url', 'https://example.com/new.jpg');

        $this->assertDatabaseHas('lingerie_product_color_images', [
            'id' => $colorImage->id,
            'image_url' => 'https://example.com/new.jpg',
        ]);
    });

    it('returns 404 for non-existent product', function () {
        $response = $this->putJson('/api/lingerie/products/999/color-images/1', [
            'image_url' => 'https://example.com/new.jpg',
        ]);

        $response->assertNotFound();
    });

    it('returns 404 for non-existent color image', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->putJson("/api/lingerie/products/$product->id/color-images/999", [
            'image_url' => 'https://example.com/new.jpg',
        ]);

        $response->assertNotFound();
    });

    it('returns 404 when color image belongs to different product', function () {
        $product1 = LingerieProduct::factory()->create();
        $product2 = LingerieProduct::factory()->create();
        $colorImage = LingerieProductColorImage::factory()->create([
            'product_id' => $product2->id,
            'image_url' => 'https://example.com/old.jpg',
        ]);

        $response = $this->putJson("/api/lingerie/products/$product1->id/color-images/$colorImage->id", [
            'image_url' => 'https://example.com/new.jpg',
        ]);

        $response->assertNotFound();

        $this->assertDatabaseHas('lingerie_product_color_images', [
            'id' => $colorImage->id,
            'image_url' => 'https://example.com/old.jpg', // unchanged
        ]);
    });

    it('does not allow changing color_id', function () {
        $product = LingerieProduct::factory()->create();
        $color1 = LingerieProductColor::factory()->create();
        $color2 = LingerieProductColor::factory()->create();
        $colorImage = LingerieProductColorImage::factory()->create([
            'product_id' => $product->id,
            'color_id' => $color1->id,
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id/color-images/$colorImage->id", [
            'color_id' => $color2->id,
            'image_url' => 'https://example.com/new.jpg',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('lingerie_product_color_images', [
            'id' => $colorImage->id,
            'color_id' => $color1->id, // unchanged
        ]);
    });

    it('validates image_url is required on update', function () {
        $product = LingerieProduct::factory()->create();
        $colorImage = LingerieProductColorImage::factory()->create([
            'product_id' => $product->id,
            'image_url' => 'https://example.com/old.jpg',
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id/color-images/$colorImage->id");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image_url']);

        $this->assertDatabaseHas('lingerie_product_color_images', [
            'id' => $colorImage->id,
            'image_url' => 'https://example.com/old.jpg', // unchanged
        ]);
    });

    it('validates image_url is valid url on update', function () {
        $product = LingerieProduct::factory()->create();
        $colorImage = LingerieProductColorImage::factory()->create([
            'product_id' => $product->id,
            'image_url' => 'https://example.com/old.jpg',
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id/color-images/$colorImage->id", [
            'image_url' => 'not-a-valid-url',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image_url']);

        $this->assertDatabaseHas('lingerie_product_color_images', [
            'id' => $colorImage->id,
            'image_url' => 'https://example.com/old.jpg', // unchanged
        ]);
    });
});

describe('Color image cascade behavior', function () {
    it('deletes color images when product is deleted via database', function () {
        $product = LingerieProduct::factory()->create();
        LingerieProductColorImage::factory()->count(3)->create([
            'product_id' => $product->id,
        ]);

        $this->assertDatabaseCount('lingerie_product_color_images', 3);

        $product->delete();

        $this->assertDatabaseCount('lingerie_product_color_images', 0);
    });
});