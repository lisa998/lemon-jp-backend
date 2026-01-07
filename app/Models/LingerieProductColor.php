<?php

namespace App\Models;

use Database\Factories\LingerieProductColorFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $color
 * @method static LingerieProductColorFactory factory($count = null, $state = [])
 * @method static Builder<static>|LingerieProductColor newModelQuery()
 * @method static Builder<static>|LingerieProductColor newQuery()
 * @method static Builder<static>|LingerieProductColor query()
 * @method static Builder<static>|LingerieProductColor whereColor($value)
 * @method static Builder<static>|LingerieProductColor whereId($value)
 * @mixin Eloquent
 */
class LingerieProductColor extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = ['color'];
}
