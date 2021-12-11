<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    public function products()
    {
        return $this->hasMany(Product::class, 'variant_id', 'id');
    }

}
