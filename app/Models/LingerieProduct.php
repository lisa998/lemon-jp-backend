<?php

namespace App\Models;

use Database\Factories\LingerieProductFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $name
 * @property string $product_code
 * @property string|null $description
 * @property string|null $detail
 * @method static LingerieProductFactory factory($count = null, $state = [])
 * @method static Builder<static>|LingerieProduct newModelQuery()
 * @method static Builder<static>|LingerieProduct newQuery()
 * @method static Builder<static>|LingerieProduct query()
 * @method static Builder<static>|LingerieProduct whereCreatedAt($value)
 * @method static Builder<static>|LingerieProduct whereDescription($value)
 * @method static Builder<static>|LingerieProduct whereDetail($value)
 * @method static Builder<static>|LingerieProduct whereId($value)
 * @method static Builder<static>|LingerieProduct whereName($value)
 * @method static Builder<static>|LingerieProduct whereProductCode($value)
 * @method static Builder<static>|LingerieProduct whereUpdatedAt($value)
 * @mixin Eloquent
 */
class LingerieProduct extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'product_code', 'description', 'detail'];

    protected $casts = [
        'detail' => 'array',
    ];

    public function skus():HasMany
    {
        return $this->hasMany(LingerieProductSku::class, 'lingerie_product_id');
    }
}
