<?php

namespace App\Models;

use Database\Factories\LingerieProductSkuFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $lingerie_product_id
 * @property numeric $price
 * @property int $stock_quantity
 * @property int $size_id
 * @property int $color_id
 * @method static LingerieProductSkuFactory factory($count = null, $state = [])
 * @method static Builder<static>|LingerieProductSku newModelQuery()
 * @method static Builder<static>|LingerieProductSku newQuery()
 * @method static Builder<static>|LingerieProductSku query()
 * @method static Builder<static>|LingerieProductSku whereColorId($value)
 * @method static Builder<static>|LingerieProductSku whereCreatedAt($value)
 * @method static Builder<static>|LingerieProductSku whereId($value)
 * @method static Builder<static>|LingerieProductSku whereLingerieProductId($value)
 * @method static Builder<static>|LingerieProductSku wherePrice($value)
 * @method static Builder<static>|LingerieProductSku whereSizeId($value)
 * @method static Builder<static>|LingerieProductSku whereStockQuantity($value)
 * @method static Builder<static>|LingerieProductSku whereUpdatedAt($value)
 * @mixin Eloquent
 */
class LingerieProductSku extends Model
{
    use HasFactory;

    protected $fillable = [
        'lingerie_product_id',
        'size_id',
        'color_id',
        'price',
        'stock_quantity'
    ];
}
