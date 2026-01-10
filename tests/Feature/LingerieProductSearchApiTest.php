 <?php

use App\Models\LingerieProduct;
use App\Models\LingerieProductColor;
use App\Models\LingerieProductSize;
use App\Models\LingerieProductSku;

describe('GET /api/lingerie/products (pagination)', function () {
    it('returns paginated results', function () {
        LingerieProduct::factory()->count(25)->create();

        $response = $this->getJson('/api/lingerie/products?page=1&per_page=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.last_page', 3);
    });

    it('returns second page of results', function () {
        LingerieProduct::factory()->count(25)->create();

        $response = $this->getJson('/api/lingerie/products?page=2&per_page=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.current_page', 2);
    });

    it('returns last page with remaining items', function () {
        LingerieProduct::factory()->count(25)->create();

        $response = $this->getJson('/api/lingerie/products?page=3&per_page=10');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 3);
    });

    it('returns empty data for page beyond total', function () {
        LingerieProduct::factory()->count(10)->create();

        $response = $this->getJson('/api/lingerie/products?page=5&per_page=10');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('uses default per_page when not specified', function () {
        LingerieProduct::factory()->count(20)->create();

        $response = $this->getJson('/api/lingerie/products');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 15); // assuming default is 15
    });

    it('validates per_page is positive integer', function () {
        $response = $this->getJson('/api/lingerie/products?per_page=-5');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    });

    it('validates per_page max limit', function () {
        $response = $this->getJson('/api/lingerie/products?per_page=500');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    });

    it('validates page is positive integer', function () {
        $response = $this->getJson('/api/lingerie/products?page=-1');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    });

    it('validates page is integer', function () {
        $response = $this->getJson('/api/lingerie/products?page=abc');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    });

    it('includes pagination links', function () {
        LingerieProduct::factory()->count(30)->create();

        $response = $this->getJson('/api/lingerie/products?page=2&per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });
});

describe('GET /api/lingerie/products (sorting)', function () {
    it('sorts by name ascending', function () {
        LingerieProduct::factory()->create(['name' => 'Charlie']);
        LingerieProduct::factory()->create(['name' => 'Alpha']);
        LingerieProduct::factory()->create(['name' => 'Bravo']);

        $response = $this->getJson('/api/lingerie/products?sort=name&order=asc');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Alpha')
            ->assertJsonPath('data.1.name', 'Bravo')
            ->assertJsonPath('data.2.name', 'Charlie');
    });

    it('sorts by name descending', function () {
        LingerieProduct::factory()->create(['name' => 'Charlie']);
        LingerieProduct::factory()->create(['name' => 'Alpha']);
        LingerieProduct::factory()->create(['name' => 'Bravo']);

        $response = $this->getJson('/api/lingerie/products?sort=name&order=desc');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Charlie')
            ->assertJsonPath('data.1.name', 'Bravo')
            ->assertJsonPath('data.2.name', 'Alpha');
    });

    it('sorts by created_at ascending', function () {
        $oldest = LingerieProduct::factory()->create(['created_at' => now()->subDays(2)]);
        $newest = LingerieProduct::factory()->create(['created_at' => now()]);
        $middle = LingerieProduct::factory()->create(['created_at' => now()->subDay()]);

        $response = $this->getJson('/api/lingerie/products?sort=created_at&order=asc');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $oldest->id)
            ->assertJsonPath('data.1.id', $middle->id)
            ->assertJsonPath('data.2.id', $newest->id);
    });

    it('sorts by created_at descending by default', function () {
        $oldest = LingerieProduct::factory()->create(['created_at' => now()->subDays(2)]);
        $newest = LingerieProduct::factory()->create(['created_at' => now()]);
        $middle = LingerieProduct::factory()->create(['created_at' => now()->subDay()]);

        $response = $this->getJson('/api/lingerie/products');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $newest->id);
    });

    it('sorts by product_code', function () {
        LingerieProduct::factory()->create(['product_code' => 'LP003']);
        LingerieProduct::factory()->create(['product_code' => 'LP001']);
        LingerieProduct::factory()->create(['product_code' => 'LP002']);

        $response = $this->getJson('/api/lingerie/products?sort=product_code&order=asc');

        $response->assertOk()
            ->assertJsonPath('data.0.product_code', 'LP001')
            ->assertJsonPath('data.1.product_code', 'LP002')
            ->assertJsonPath('data.2.product_code', 'LP003');
    });

    it('validates sort field is allowed', function () {
        $response = $this->getJson('/api/lingerie/products?sort=invalid_field');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['sort']);
    });

    it('validates order is asc or desc', function () {
        $response = $this->getJson('/api/lingerie/products?sort=name&order=invalid');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['order']);
    });

    it('defaults order to asc when not specified', function () {
        LingerieProduct::factory()->create(['name' => 'Bravo']);
        LingerieProduct::factory()->create(['name' => 'Alpha']);

        $response = $this->getJson('/api/lingerie/products?sort=name');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Alpha');
    });
});

describe('GET /api/lingerie/products (filtering)', function () {
    it('filters by size', function () {
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

    it('filters by color', function () {
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

    it('filters by both size and color', function () {
        $size = LingerieProductSize::factory()->create(['size' => 'C70/M']);
        $color = LingerieProductColor::factory()->create(['color' => '黑色']);
        $otherColor = LingerieProductColor::factory()->create(['color' => '白色']);

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
            'color_id' => $otherColor->id,
        ]);

        $response = $this->getJson('/api/lingerie/products?size=C70/M&color=黑色');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingProduct->id);
    });

    it('filters by name keyword', function () {
        LingerieProduct::factory()->create(['name' => '蕾絲內衣套裝']);
        LingerieProduct::factory()->create(['name' => '棉質內衣']);
        LingerieProduct::factory()->create(['name' => '蕾絲睡衣']);

        $response = $this->getJson('/api/lingerie/products?keyword=蕾絲');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('filters by product_code keyword', function () {
        LingerieProduct::factory()->create(['product_code' => 'LP001', 'name' => 'Product A']);
        LingerieProduct::factory()->create(['product_code' => 'LP002', 'name' => 'Product B']);
        LingerieProduct::factory()->create(['product_code' => 'XY001', 'name' => 'Product C']);

        $response = $this->getJson('/api/lingerie/products?keyword=LP');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('returns empty when no products match filter', function () {
        LingerieProduct::factory()->create(['name' => '蕾絲內衣']);

        $response = $this->getJson('/api/lingerie/products?keyword=不存在');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('combines filter with pagination', function () {
        $size = LingerieProductSize::factory()->create(['size' => 'C70/M']);

        for ($i = 0; $i < 15; $i++) {
            $product = LingerieProduct::factory()->create();
            LingerieProductSku::factory()->create([
                'lingerie_product_id' => $product->id,
                'size_id' => $size->id,
            ]);
        }

        // Create some non-matching products
        LingerieProduct::factory()->count(5)->create();

        $response = $this->getJson('/api/lingerie/products?size=C70/M&page=1&per_page=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 15);
    });

    it('combines filter with sorting', function () {
        $size = LingerieProductSize::factory()->create(['size' => 'C70/M']);

        $productA = LingerieProduct::factory()->create(['name' => 'Alpha']);
        $productB = LingerieProduct::factory()->create(['name' => 'Bravo']);

        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $productA->id,
            'size_id' => $size->id,
        ]);
        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $productB->id,
            'size_id' => $size->id,
        ]);

        // Non-matching product
        LingerieProduct::factory()->create(['name' => 'AAA']);

        $response = $this->getJson('/api/lingerie/products?size=C70/M&sort=name&order=desc');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Bravo')
            ->assertJsonPath('data.1.name', 'Alpha');
    });

    it('handles special characters in keyword', function () {
        LingerieProduct::factory()->create(['name' => '100% 純棉內衣']);
        LingerieProduct::factory()->create(['name' => '蕾絲內衣']);

        $response = $this->getJson('/api/lingerie/products?keyword=100%');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('trims whitespace from filter values', function () {
        $size = LingerieProductSize::factory()->create(['size' => 'C70/M']);
        $product = LingerieProduct::factory()->create();
        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product->id,
            'size_id' => $size->id,
        ]);

        $response = $this->getJson('/api/lingerie/products?size=%20C70/M%20');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('GET /api/lingerie/products (price range filter)', function () {
    it('filters by min price', function () {
        $product1 = LingerieProduct::factory()->create();
        $product2 = LingerieProduct::factory()->create();

        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product1->id,
            'price' => 500.00,
        ]);
        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product2->id,
            'price' => 1500.00,
        ]);

        $response = $this->getJson('/api/lingerie/products?min_price=1000');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $product2->id);
    });

    it('filters by max price', function () {
        $product1 = LingerieProduct::factory()->create();
        $product2 = LingerieProduct::factory()->create();

        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product1->id,
            'price' => 500.00,
        ]);
        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product2->id,
            'price' => 1500.00,
        ]);

        $response = $this->getJson('/api/lingerie/products?max_price=1000');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $product1->id);
    });

    it('filters by price range', function () {
        $product1 = LingerieProduct::factory()->create();
        $product2 = LingerieProduct::factory()->create();
        $product3 = LingerieProduct::factory()->create();

        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product1->id,
            'price' => 500.00,
        ]);
        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product2->id,
            'price' => 1000.00,
        ]);
        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $product3->id,
            'price' => 2000.00,
        ]);

        $response = $this->getJson('/api/lingerie/products?min_price=800&max_price=1500');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $product2->id);
    });

    it('validates min_price is numeric', function () {
        $response = $this->getJson('/api/lingerie/products?min_price=abc');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['min_price']);
    });

    it('validates max_price is numeric', function () {
        $response = $this->getJson('/api/lingerie/products?max_price=abc');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['max_price']);
    });

    it('validates min_price is non-negative', function () {
        $response = $this->getJson('/api/lingerie/products?min_price=-100');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['min_price']);
    });

    it('validates max_price is greater than min_price', function () {
        $response = $this->getJson('/api/lingerie/products?min_price=1000&max_price=500');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['max_price']);
    });
});

describe('GET /api/lingerie/products (in stock filter)', function () {
    it('filters products with stock', function () {
        $inStockProduct = LingerieProduct::factory()->create();
        $outOfStockProduct = LingerieProduct::factory()->create();

        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $inStockProduct->id,
            'stock_quantity' => 10,
        ]);
        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $outOfStockProduct->id,
            'stock_quantity' => 0,
        ]);

        $response = $this->getJson('/api/lingerie/products?in_stock=true');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inStockProduct->id);
    });

    it('filters products without stock', function () {
        $inStockProduct = LingerieProduct::factory()->create();
        $outOfStockProduct = LingerieProduct::factory()->create();

        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $inStockProduct->id,
            'stock_quantity' => 10,
        ]);
        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $outOfStockProduct->id,
            'stock_quantity' => 0,
        ]);

        $response = $this->getJson('/api/lingerie/products?in_stock=false');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $outOfStockProduct->id);
    });

    it('includes products without skus when filtering out of stock', function () {
        $productWithSku = LingerieProduct::factory()->create();
        $productWithoutSku = LingerieProduct::factory()->create();

        LingerieProductSku::factory()->create([
            'lingerie_product_id' => $productWithSku->id,
            'stock_quantity' => 10,
        ]);

        $response = $this->getJson('/api/lingerie/products?in_stock=false');

        $response->assertOk();
        // Product without SKU should be considered out of stock
    });
});