<?php

namespace App\Models;

use Database\Factories\LingerieProductSizeFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $size
 * @method static LingerieProductSizeFactory factory($count = null, $state = [])
 * @method static Builder<static>|LingerieProductSize newModelQuery()
 * @method static Builder<static>|LingerieProductSize newQuery()
 * @method static Builder<static>|LingerieProductSize query()
 * @method static Builder<static>|LingerieProductSize whereId($value)
 * @method static Builder<static>|LingerieProductSize whereSize($value)
 * @mixin Eloquent
 */
class LingerieProductSize extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = ['size'];
}
