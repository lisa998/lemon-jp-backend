<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LingerieProductSku extends Model
{
    protected $fillable = ['lingerie_product_id', 'size_id', 'color_id', 'price', 'stock_quantity'];
}
