@extends('layouts.app')

@section('content')

    @if (count($products) > 0)
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Products</h1>
    </div>
    
    <div class="card">
        <form action="{{ route('product.filter')}}" method="post" class="card-header">
            @csrf
            <div class="form-row justify-content-between">
                <div class="col-md-2">
                    <input type="text" name="title" placeholder="Product Title" class="form-control">
                </div>
                <div class="col-md-2">
                    <select name="variant" id="" class="form-control">
                        @foreach ($variants as $variant)
                            <option disabled value="{{ $variant->id }}">{{ $variant->title }}</option>
                            @foreach ($variant->ProductVariants as $productVariant)
                                <option value="{{ $productVariant->id }}">{{ $productVariant->variant }}</option> )
                                
                            @endforeach
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Price Range</span>
                        </div>
                        <input type="text" name="price_from" aria-label="First name" placeholder="From" class="form-control">
                        <input type="text" name="price_to" aria-label="Last name" placeholder="To" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date" placeholder="Date" class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary float-right"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>
        
        <div class="card-body">
            <div class="table-response">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th width="300px">Description</th>
                        <th>Variant</th>
                        <th width="150px">Action</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach ($products as $product )
                        <tr>
                        <td>{{ $loop->index +1 }}</td>
                        <td>{{ $product->title  }}
                         <br> Created at : {{date_format($product->created_at,'d-M-Y')}}</td>
                        <td> {{ \Illuminate\Support\Str::limit($product->description, 250, $end='...') }}</td>
                        <td>
                            @foreach ($product->ProductVariantPrice as $variant )
                            <dl class="row mb-0" style="height: 80px; overflow: hidden" id="variant">
                                    
                                <dt class="col-sm-3 pb-0" style="width: 350px">
                                    <p>{{ $variant->productVariantOne ? $variant->productVariantOne->variant: '' }} / {{ $variant->productVariantTwo ? $variant->productVariantTwo->variant: '' }} / {{ $variant->productVariantThree ? $variant->productVariantThree->variant: '' }}</p>
                                </dt>
                                <dd class="col-sm-9">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-4 pb-0">Price : {{ $variant->price }}</dt>
                                        <dd class="col-sm-8 pb-0">InStock : {{ $variant->stock }}</dd>
                                    </dl>
                                </dd>
                            </dl>
                             @endforeach
                            <button onclick="$('#variant').toggleClass('h-auto')" class="btn btn-sm btn-link">Show more</button>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('product.edit', $product->id ) }}" class="btn btn-success">Edit</a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    

                    </tbody>

                </table>
            </div>

        </div>
        <div class="card-footer">
            <div class="row justify-content-between">
                <div class="col-md-6">
                    <p>Showing {{ $products[count($products)-1]->id }} to {{$products[0]->id}} out of {{ $total }}</p>
                </div>
                <div class="col-md-2">
                    {{$products->render()}}
                    
                </div>
            </div>
        </div>
    </div>
    
    @else
        <div class="card">
            <div class="card-header">
                <h1 class="h3 mb-0 text-gray-800">Products</h1>
            </div>
            <div class="card-body">
                <p>No Product Found</p>
            </div>
        </div>
        
    @endif

@endsection
