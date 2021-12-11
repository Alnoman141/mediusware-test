<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPrice extends Model
{
    public function productVariantOne()
    {
        return $this->belongsTo('App\Models\ProductVariant', 'product_variant_one', 'id');
    }
    public function productVariantTwo()
    {
        return $this->belongsTo('App\Models\ProductVariant', 'product_variant_two', 'id');
    }
    public function productVariantThree()
    {
        return $this->belongsTo('App\Models\ProductVariant', 'product_variant_three', 'id');
    }
}
