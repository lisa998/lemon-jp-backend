<?php

use App\Models\LingerieProduct;
use App\Models\LingerieProductColor;
use App\Models\LingerieProductSize;
use App\Models\LingerieProductSku;

describe('POST /api/lingerie/products', function () {
    it('creates a new product', function () {
        $payload = [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
            'description' => '舒適透氣的蕾絲內衣',
            'detail' => ['material' => 'lace', 'origin' => 'Japan'],
        ];

        $response = $this->postJson('/api/lingerie/products', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', '蕾絲內衣套裝')
            ->assertJsonPath('data.product_code', 'LP001');

        $this->assertDatabaseHas('lingerie_products', [
            'name' => '蕾絲內衣套裝',
            'product_code' => 'LP001',
        ]);
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/lingerie/products');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'product_code']);
    });

    it('validates unique product_code', function () {
        LingerieProduct::factory()->create(['product_code' => 'LP001']);

        $response = $this->postJson('/api/lingerie/products', [
            'name' => '新商品',
            'product_code' => 'LP001',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['product_code']);
    });
});

describe('PUT /api/lingerie/products/{id}', function () {
    it('updates an existing product', function () {
        $product = LingerieProduct::factory()->create([
            'name' => '舊名稱',
            'product_code' => 'LP001',
        ]);

        $response = $this->putJson('/api/lingerie/products/' . $product->getKey(), [
            'name' => '新名稱',
            'description' => '更新的描述',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', '新名稱')
            ->assertJsonPath('data.description', '更新的描述');
    });

    it('returns 404 for non-existent product', function () {
        $response = $this->putJson('/api/lingerie/products/999', [
            'name' => '新名稱',
        ]);

        $response->assertNotFound();
    });
});

describe('DELETE /api/lingerie/products/{id}', function () {
    it('deletes an existing product', function () {
        $product = LingerieProduct::factory()->create();

        $response = $this->deleteJson("/api/lingerie/products/$product->id");

        $response->assertNoContent();
        $this->assertDatabaseMissing('lingerie_products', ['id' => $product->id]);
    });

    it('returns 404 for non-existent product', function () {
        $response = $this->deleteJson('/api/lingerie/products/999');

        $response->assertNotFound();
    });
});

describe('GET /api/lingerie/products', function () {
    it('returns all products', function () {
        LingerieProduct::factory()->count(3)->create();

        $response = $this->getJson('/api/lingerie/products');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('can filter by size', function () {
        $size = LingerieProductSize::factory()->create(['size' => 'C70/M']);
        $otherSize = LingerieProductSize::factory()->create(['size' => 'B65/S']);

        $matchingProduct = LingerieProduct::factory()->create();
        $nonMatchingProduct = LingerieProduct::factory()->create();

        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $matchingProduct->id,
            'size_id' => $size->id,
        ]);
        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $nonMatchingProduct->id,
            'size_id' => $otherSize->id,
        ]);

        $response = $this->getJson('/api/lingerie/products?size=C70/M');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingProduct->id);
    });

    it('can filter by color', function () {
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);
        $otherColor = LingerieProductColor::factory()->create(['color' => '白色']);

        $matchingProduct = LingerieProduct::factory()->create();
        $nonMatchingProduct = LingerieProduct::factory()->create();

        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $matchingProduct->id,
            'color_id' => $color->id,
        ]);
        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $nonMatchingProduct->id,
            'color_id' => $otherColor->id,
        ]);

        $response = $this->getJson('/api/lingerie/products?color=黑色');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingProduct->id);
    });

    it('can filter by both size and color', function () {
        $size = LingerieProductSize::factory()->create(['size' => 'C70/M']);
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);

        $matchingProduct = LingerieProduct::factory()->create();
        $partialMatchProduct = LingerieProduct::factory()->create();

        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $matchingProduct->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
        ]);
        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $partialMatchProduct->id,
            'size_id' => $size->id,
            'color_id' => LingerieProductColor::factory()->create()->id,
        ]);

        $response = $this->getJson('/api/lingerie/products?size=C70/M&color=黑色');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingProduct->id);
    });

    it('returns empty array when no products match filters', function () {
        LingerieProductSize::factory()->create(['size' => 'C70/M']);
        LingerieProduct::factory()->create();

        $response = $this->getJson('/api/lingerie/products?size=D80/L');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    });
});

describe('GET /api/lingerie/products/{id}', function () {
    it('returns product with all skus', function () {
        $product = LingerieProduct::factory()->create([
            'name' => '蕾絲內衣',
            'product_code' => 'LP001',
        ]);

        $size1 = LingerieProductSize::factory()->create(['size' => 'C70/M']);
        $size2 = LingerieProductSize::factory()->create(['size' => 'B65/S']);
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);

        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product->id,
            'size_id' => $size1->id,
            'color_id' => $color->id,
            'price' => 1280.00,
            'stock_quantity' => 10,
        ]);
        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product->id,
            'size_id' => $size2->id,
            'color_id' => $color->id,
            'price' => 1280.00,
            'stock_quantity' => 5,
        ]);

        $response = $this->getJson("/api/lingerie/products/$product->id");

        $response->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', '蕾絲內衣')
            ->assertJsonPath('data.product_code', 'LP001')
            ->assertJsonCount(2, 'data.skus');
    });

    it('returns sku details with size and color info', function () {
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

        $response = $this->getJson("/api/lingerie/products/$product->id");

        $response->assertOk()
            ->assertJsonPath('data.skus.0.size', 'C70/M')
            ->assertJsonPath('data.skus.0.color', '黑色')
            ->assertJsonPath('data.skus.0.price', '1280.00')
            ->assertJsonPath('data.skus.0.stock_quantity', 10);
    });

    it('returns 404 for non-existent product', function () {
        $response = $this->getJson('/api/lingerie/products/999');

        $response->assertNotFound();
    });
});
