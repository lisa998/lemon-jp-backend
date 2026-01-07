<?php

namespace App\Models;

use Database\Factories\LingerieProductImageFactory;
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
 * @property string $image_url
 * @method static LingerieProductImageFactory factory($count = null, $state = [])
 * @method static Builder<static>|LingerieProductImage newModelQuery()
 * @method static Builder<static>|LingerieProductImage newQuery()
 * @method static Builder<static>|LingerieProductImage query()
 * @method static Builder<static>|LingerieProductImage whereCreatedAt($value)
 * @method static Builder<static>|LingerieProductImage whereId($value)
 * @method static Builder<static>|LingerieProductImage whereImageUrl($value)
 * @method static Builder<static>|LingerieProductImage whereLingerieProductId($value)
 * @method static Builder<static>|LingerieProductImage whereUpdatedAt($value)
 * @mixin Eloquent
 */
class LingerieProductImage extends Model
{
    use HasFactory;

    protected $fillable = ['lingerie_product_id', 'image_url'];
}
