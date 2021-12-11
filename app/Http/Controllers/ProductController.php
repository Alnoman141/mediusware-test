<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Validator;
use Intervention\Image\Facades\Image;
use File;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    const ITEM_PER_PAGE = 5;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $products = Product::orderBy('id', 'desc')->paginate(static::ITEM_PER_PAGE);
        $variants = Variant::all();
        $total = Product::count();
        return view('products.index', compact('products', 'variants', 'total'));
    }

    public function filter(Request $request)
    {
        $searchParams = $request->all();
        $title = Arr::get($searchParams, 'title', '');
        $variant = Arr::get($searchParams, 'variant', '');
        $price_from = Arr::get($searchParams, 'price_from', '');
        $price_to = Arr::get($searchParams, 'price_to', '');
        $date = Arr::get($searchParams, 'date', '');
        $products = [];
        if(!empty($title)){
            $products = Product::where('title', 'like', '%'.$title.'%')->paginate(static::ITEM_PER_PAGE);
        }
        if(!empty($date)){
            $products = Product::where('created_at', 'like', '%'.$date.'%')->paginate(static::ITEM_PER_PAGE);
        }
        if(!empty($variant)){
            $productVariant = ProductVariantPrice::where('product_variant_one', $variant)->orwhere('product_variant_two', $variant)->orwhere('product_variant_three', $variant)->first();
            if(!empty($productVariant)){
                $products = Product::where('id', $productVariant->product_id)->paginate(static::ITEM_PER_PAGE);
            }
        }
        
        if(!empty($price_from) && !empty($price_to)){
            $productVariants = ProductVariantPrice::whereBetween('price', [$price_from, $price_to])->get();
            foreach ($productVariants as $productVariant) {
                $product = Product::where('id', $productVariant->product_id)->first();
                $exists = Arr::exists($products, $product->id);
                if(!$exists){
                    $products[] = $product;
                }
            }
        }
        if(!empty($price_from) && empty($price_to)){
            $productVariants = ProductVariantPrice::where('price', '>=', $price_from)->get();
            foreach ($productVariants as $productVariant) {
                $products[] = Product::where('id', $productVariant->product_id)->first();
                $exists = Arr::exists($products, $product->id);
                if(!$exists){
                    $products[] = $product;
                }
                    
            }
        }
        if(!empty($price_to) && empty($price_from)){
            $productVariants = ProductVariantPrice::where('price', '<=', $price_from)->get();
            foreach ($productVariants as $productVariant) {
                $products[] = Product::where('id', $productVariant->product_id)->first();
                $exists = Arr::exists($products, $product->id);
                if(!$exists){
                    $products[] = $product;
                }
            }
        }
        $total = Product::count();
        $variants = Variant::all();
        return view('products.index', compact('products', 'variants', 'total'));

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // check validation rules from getValidationRules method
        $validator = Validator::make(
            $request->all(),
            array_merge(
                $this->getValidationRules(),
            )
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 403);
        } else {
            // save product
            $product = new Product();
            $product->title = $request->title;
            $product->sku = $request->sku;
            $product->description = $request->description;
            $product->save();

            // save product variant
            if(count($request->product_variant) > 0){
                $variants = $request->product_variant;
                $prices = $request->product_variant_prices;
                foreach($variants as $variant){
                    foreach($variant['tags'] as $tag){
                        $pVariant = new ProductVariant();
                        $this->saveProductVariant($pVariant, $product, $variant, $tag);
                    };
                };

                // save product variant price
                foreach($prices as $price){
                    $names = explode('/', $price['title']);
                    $vars = [];
                    foreach($names as $name){
                        if(isset($name)){
                            $var = ProductVariant::where('product_id', $product->id)->where('variant', Str::lower($name))->first();
                            array_push($vars, $var);
                        }
                    }
                    $price['variants'] = $vars;
                    $priceVariant = new ProductVariantPrice();
                    $this->saveProductVariantPrice($priceVariant, $product, $price);
                }
            }

            // save product image
            $this->saveImage($request, $product);

            return response()->json(['success' => 'product saved successfully'], 200);
        }
    }

    public function saveProductVariant($pVariant, $product, $variant, $tag){
        $pVariant->product_id = $product->id;
        $pVariant->variant_id = $variant['option'];
        $pVariant->variant = $tag;
        $pVariant->save();
    }

    public function saveProductVariantPrice($priceVariant, $product, $price){
        $priceVariant->product_id = $product->id;
        $priceVariant->product_variant_one = $price['variants'][0] ? $price['variants'][0]->id : NULL;
        $priceVariant->product_variant_two = $price['variants'][1] ? $price['variants'][1]->id: NULL;
        $priceVariant->product_variant_three = $price['variants'][2] ? $price['variants'][2]->id: NULL;
        $priceVariant->price = $price['price']? $price['price'] : 0;
        $priceVariant->stock = $price['stock']? $price['stock']: 0;
        $priceVariant->save();
    }

    public function saveImage($request, $product){
        if(count($request->product_image) > 0 ){
            $images = $request->product_image;
            foreach($images as $image){
                $pImage = new ProductImage();
                if(isset($image)){
                    $extension = Str::between($image,'data:image/',';base64');
                    $location = public_path().'/images/product/';
                    $imageName = Str::slug($request->title).'-'.time().'.'.$extension;
                    Image::make($image)->save($location.$imageName);

                    $pImage->product_id = $product->id;
                    $pImage->file_path = $imageName;
                    $pImage->save();
                }
            }
        }
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $product = Product::where('id',$product->id)->with('ProductVariantPrice', 'ProductVariant', 'images')->first();
        $variants = Variant::with('ProductVariants')->get();
        return view('products.edit', compact('variants', 'product'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        // check requested product
        if ($product === null) {
            return response()->json(['error' => 'product not found'], 404);
        }

        // check validation rules form getValidationRules method
        $validator = Validator::make($request->all(), $this->getValidationRules($isNew = false, $product));
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 403);
        } else {
            $product->title = $request->title;
            $product->sku = $request->sku;
            $product->description = $request->description;
            $product->save();
            // save product variant
            if(count($request->product_variant) > 0){
                $variants = $request->product_variant;
                $prices = $request->product_variant_price;
                foreach($variants as $variant){
                    foreach($variant['tags'] as $tag){
                        if(isset($variant['id'])){
                            $pVariant = ProductVariant::where('id', $variant['id'])->first();
                            $this->saveProductVariant($pVariant, $product, $variant, $tag);
                        } else {
                            $pVariant = new ProductVariant();
                            $this->saveProductVariant($pVariant, $product, $variant, $tag);
                        }   
                    };
                };
                // save product variant price
                foreach($prices as $price){
                    $names = explode('/', $price['title']);
                    $vars = [];
                    foreach($names as $name){
                        if(isset($name)){
                            $var = ProductVariant::where('product_id', $product->id)->where('variant', Str::lower($name))->first();
                            array_push($vars, $var);
                        }
                    }
                    
                    $price['variants'] = $vars;
                    if(isset($price['id'])){
                        $priceVariant = ProductVariantPrice::where('id', $price['id'])->first();
                        $this->saveProductVariantPrice($priceVariant, $product, $price);
                    } else {
                        $priceVariant = new ProductVariantPrice();
                        $this->saveProductVariantPrice($priceVariant, $product, $price);
                    }
                    
                }
            }
            
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }

    /**
     * getValidationRules.
     *
     * @author	noman
     * @access	private
     * @param	boolean	$isNew	Default: true
     * @return	array
     */
    private function getValidationRules($isNew = true)
    {
        return [
            'title' => $isNew ? 'required' : 'nullable',
            'sku' => $isNew ? 'required|unique:products' : 'required',
        ];
    }
}
