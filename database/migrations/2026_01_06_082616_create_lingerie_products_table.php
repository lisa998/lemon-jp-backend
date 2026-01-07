<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lingerie_products', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name', 128);
            $table->string('product_code', 64)->unique();
            $table->text('description')->nullable();
            $table->json('detail')->nullable();
        });

        Schema::create('lingerie_product_images', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('lingerie_product_id')->constrained('lingerie_products')->onDelete('cascade');
            $table->string('image_url', 2083);
        });

        Schema::create('lingerie_product_sizes', function (Blueprint $table) {
            $table->id();
            $table->string('size', 16)->unique();
        });
        Schema::create('lingerie_product_colors', function (Blueprint $table) {
            $table->id();
            $table->string('color', 32)->unique();
        });

        Schema::create('lingerie_product_skus', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('lingerie_product_id')->constrained('lingerie_products')->onDelete('cascade');
            $table->decimal('price', 8, 2);
            $table->integer('stock_quantity')->default(0);
            $table->foreignId('size_id')->constrained('lingerie_product_sizes')->onDelete('cascade');
            $table->foreignId('color_id')->constrained('lingerie_product_colors')->onDelete('cascade');
            $table->unique(['size_id', 'color_id', 'lingerie_product_id'], 'unique_product_size_color');
        });

        Schema::create('lingerie_product_color_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('lingerie_products')->onDelete('cascade');
            $table->foreignId('color_id')->constrained('lingerie_product_colors')->onDelete('cascade');
            $table->string('image_url', 2083);
            $table->unique(['product_id', 'color_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lingerie_product_color_images');
        Schema::dropIfExists('lingerie_product_skus');
        Schema::dropIfExists('lingerie_product_colors');
        Schema::dropIfExists('lingerie_product_sizes');
        Schema::dropIfExists('lingerie_product_images');
        Schema::dropIfExists('lingerie_products');
    }
};
