<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LingerieProductColorImage extends Model
{
    protected $fillable = ['product_id', 'color_id', 'image_url'];
}
