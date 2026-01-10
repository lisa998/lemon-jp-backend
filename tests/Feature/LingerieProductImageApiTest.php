<?php

use App\Models\LingerieProduct;
use App\Models\LingerieProductImage;

describe('GET /api/lingerie/products/{productId}/images', function () {
    it('returns all images for a product', function () {
        $product = LingerieProduct::factory()->create();
        LingerieProductImage::factory()->count(3)->create([
            'lingerie_product_id' => $product->id,
        ]);

        $response = $this->getJson("/api/lingerie/products/$product->id/images");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('returns empty array when product has no images', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->getJson("/api/lingerie/products/$product->id/images");

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('returns 404 for non-existent product', function () {
        $response = $this->getJson('/api/lingerie/products/999/images');

        $response->assertNotFound();
    });

    it('does not return images from other products', function () {
        $product1 = LingerieProduct::factory()->create();
        $product2 = LingerieProduct::factory()->create();

        LingerieProductImage::factory()->count(2)->create([
            'lingerie_product_id' => $product1->id,
        ]);
        LingerieProductImage::factory()->count(3)->create([
            'lingerie_product_id' => $product2->id,
        ]);

        $response = $this->getJson("/api/lingerie/products/$product1->id/images");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });
});

describe('POST /api/lingerie/products/{productId}/images', function () {
    it('adds a new image to product', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/images", [
            'image_url' => 'https://example.com/image1.jpg',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.image_url', 'https://example.com/image1.jpg');

        $this->assertDatabaseHas('lingerie_product_images', [
            'lingerie_product_id' => $product->id,
            'image_url' => 'https://example.com/image1.jpg',
        ]);
        $this->assertDatabaseCount('lingerie_product_images', 1);
    });

    it('allows multiple images for same product', function () {
        $product = LingerieProduct::factory()->create();

        $this->postJson("/api/lingerie/products/$product->id/images", [
            'image_url' => 'https://example.com/image1.jpg',
        ]);
        $this->postJson("/api/lingerie/products/$product->id/images", [
            'image_url' => 'https://example.com/image2.jpg',
        ]);
        $this->postJson("/api/lingerie/products/$product->id/images", [
            'image_url' => 'https://example.com/image3.jpg',
        ]);

        $this->assertDatabaseCount('lingerie_product_images', 3);
    });

    it('returns 404 for non-existent product', function () {
        $response = $this->postJson('/api/lingerie/products/999/images', [
            'image_url' => 'https://example.com/image.jpg',
        ]);

        $response->assertNotFound();

        $this->assertDatabaseCount('lingerie_product_images', 0);
    });

    it('validates image_url is required', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/images");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image_url']);

        $this->assertDatabaseCount('lingerie_product_images', 0);
    });

    it('validates image_url is a valid url', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/images", [
            'image_url' => 'not-a-valid-url',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image_url']);

        $this->assertDatabaseCount('lingerie_product_images', 0);
    });

    it('validates image_url max length', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/images", [
            'image_url' => 'https://example.com/' . str_repeat('a', 2084), // exceeds 2083
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image_url']);

        $this->assertDatabaseCount('lingerie_product_images', 0);
    });

    it('validates image_url is a string', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/images", [
            'image_url' => 12345,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image_url']);

        $this->assertDatabaseCount('lingerie_product_images', 0);
    });

    it('allows same image_url for different products', function () {
        $product1 = LingerieProduct::factory()->create();
        $product2 = LingerieProduct::factory()->create();
        $imageUrl = 'https://example.com/shared-image.jpg';

        $this->postJson("/api/lingerie/products/$product1->id/images", [
            'image_url' => $imageUrl,
        ])->assertCreated();

        $this->postJson("/api/lingerie/products/$product2->id/images", [
            'image_url' => $imageUrl,
        ])->assertCreated();

        $this->assertDatabaseCount('lingerie_product_images', 2);
    });

    it('allows duplicate image_url for same product', function () {
        $product = LingerieProduct::factory()->create();
        $imageUrl = 'https://example.com/image.jpg';

        $this->postJson("/api/lingerie/products/$product->id/images", [
            'image_url' => $imageUrl,
        ])->assertCreated();

        $this->postJson("/api/lingerie/products/$product->id/images", [
            'image_url' => $imageUrl,
        ])->assertCreated();

        $this->assertDatabaseCount('lingerie_product_images', 2);
    });

    it('trims whitespace from image_url', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/images", [
            'image_url' => '  https://example.com/image.jpg  ',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('lingerie_product_images', [
            'image_url' => 'https://example.com/image.jpg',
        ]);
    });

    it('accepts https urls', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/images", [
            'image_url' => 'https://example.com/image.jpg',
        ]);

        $response->assertCreated();
    });

    it('accepts http urls', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->postJson("/api/lingerie/products/$product->id/images", [
            'image_url' => 'http://example.com/image.jpg',
        ]);

        $response->assertCreated();
    });
});

describe('PUT /api/lingerie/products/{productId}/images/{imageId}', function () {
    it('updates image url', function () {
        $product = LingerieProduct::factory()->create();
        $image = LingerieProductImage::factory()->create([
            'lingerie_product_id' => $product->id,
            'image_url' => 'https://example.com/old.jpg',
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id/images/$image->id", [
            'image_url' => 'https://example.com/new.jpg',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.image_url', 'https://example.com/new.jpg');

        $this->assertDatabaseHas('lingerie_product_images', [
            'id' => $image->id,
            'image_url' => 'https://example.com/new.jpg',
        ]);
        $this->assertDatabaseMissing('lingerie_product_images', [
            'image_url' => 'https://example.com/old.jpg',
        ]);
    });

    it('returns 404 for non-existent product', function () {
        $response = $this->putJson('/api/lingerie/products/999/images/1', [
            'image_url' => 'https://example.com/new.jpg',
        ]);

        $response->assertNotFound();
    });

    it('returns 404 for non-existent image', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->putJson("/api/lingerie/products/$product->id/images/999", [
            'image_url' => 'https://example.com/new.jpg',
        ]);

        $response->assertNotFound();
    });

    it('returns 404 when image belongs to different product', function () {
        $product1 = LingerieProduct::factory()->create();
        $product2 = LingerieProduct::factory()->create();
        $image = LingerieProductImage::factory()->create([
            'lingerie_product_id' => $product2->id,
            'image_url' => 'https://example.com/old.jpg',
        ]);

        $response = $this->putJson("/api/lingerie/products/$product1->id/images/$image->id", [
            'image_url' => 'https://example.com/new.jpg',
        ]);

        $response->assertNotFound();

        $this->assertDatabaseHas('lingerie_product_images', [
            'id' => $image->id,
            'image_url' => 'https://example.com/old.jpg', // unchanged
        ]);
    });

    it('validates image_url is required on update', function () {
        $product = LingerieProduct::factory()->create();
        $image = LingerieProductImage::factory()->create([
            'lingerie_product_id' => $product->id,
            'image_url' => 'https://example.com/old.jpg',
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id/images/$image->id");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image_url']);

        $this->assertDatabaseHas('lingerie_product_images', [
            'id' => $image->id,
            'image_url' => 'https://example.com/old.jpg', // unchanged
        ]);
    });

    it('validates image_url is valid url on update', function () {
        $product = LingerieProduct::factory()->create();
        $image = LingerieProductImage::factory()->create([
            'lingerie_product_id' => $product->id,
            'image_url' => 'https://example.com/old.jpg',
        ]);

        $response = $this->putJson("/api/lingerie/products/$product->id/images/$image->id", [
            'image_url' => 'not-a-valid-url',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image_url']);

        $this->assertDatabaseHas('lingerie_product_images', [
            'id' => $image->id,
            'image_url' => 'https://example.com/old.jpg', // unchanged
        ]);
    });
});

describe('Product images cascade behavior', function () {
    it('deletes images when product is deleted via database', function () {
        $product = LingerieProduct::factory()->create();
        LingerieProductImage::factory()->count(3)->create([
            'lingerie_product_id' => $product->id,
        ]);

        $this->assertDatabaseCount('lingerie_product_images', 3);

        $product->delete();

        $this->assertDatabaseCount('lingerie_product_images', 0);
    });
});