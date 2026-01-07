<?php

namespace App\Models;

use Database\Factories\LingerieProductColorImageFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $product_id
 * @property int $color_id
 * @property string $image_url
 * @method static LingerieProductColorImageFactory factory($count = null, $state = [])
 * @method static Builder<static>|LingerieProductColorImage newModelQuery()
 * @method static Builder<static>|LingerieProductColorImage newQuery()
 * @method static Builder<static>|LingerieProductColorImage query()
 * @method static Builder<static>|LingerieProductColorImage whereColorId($value)
 * @method static Builder<static>|LingerieProductColorImage whereId($value)
 * @method static Builder<static>|LingerieProductColorImage whereImageUrl($value)
 * @method static Builder<static>|LingerieProductColorImage whereProductId($value)
 * @mixin Eloquent
 */
class LingerieProductColorImage extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = ['product_id', 'color_id', 'image_url'];
}
