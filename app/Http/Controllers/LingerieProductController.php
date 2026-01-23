<?php

namespace App\Http\Controllers;

use App\Http\Resources\LingerieProductResource;
use App\Models\LingerieProduct;
use App\Models\LingerieProductColor;
use App\Models\LingerieProductSize;
use Illuminate\Http\Request;

class LingerieProductController extends Controller
{

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'product_code' => 'required|unique:lingerie_products,product_code',
        ]);


        $product = LingerieProduct::create([
            'name' => $request->input('name'),
            'product_code' => $request->input('product_code'),
            'description' => $request->input('description', ''),
            'detail' => $request->input('detail', []),
        ]);

        return response()->json(['data' => [
            'name' => $product->name,
            'product_code' => $product->product_code,
        ]], 201);
    }

    public function update(Request $request, string $id)
    {
        $product = LingerieProduct::findOrFail($id);
        $product->update([
            'name' => $request->input('name', $product->name),
            'description' => $request->input('description', $product->description),
            'detail' => $request->input('detail', $product->detail),
        ]);
        return response()->json(['data' => [
            'name' => $product->name,
            'description' => $product->description,
            'detail' => $product->detail,
        ]]);


    }

    public function delete(string $id)
    {
        LingerieProduct::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function get(Request $request)
    {
        $sizeInput = $request->query('size');
        $colorInput = $request->query('color');
        $sizeId = $sizeInput ? LingerieProductSize::where('size', $sizeInput)->value('id') : null;
        $colorId = $colorInput ? LingerieProductColor::where('color', $colorInput)->value('id') : null;

        if ($sizeId === null && $sizeInput !== null) {
            return response()->json(['data' => []]);
        }
        if ($colorId === null && $colorInput !== null) {
            return response()->json(['data' => []]);
        }

        $skuFilter = array_filter(
            ['size_id' => $sizeId, 'color_id' => $colorId],
            fn($value) => $value !== null
        );

        if (!empty($skuFilter)) {
            $products = LingerieProduct::whereHas('skus', function ($query) use ($skuFilter) {
                $query->where($skuFilter);
            })->get();
            return response()->json(['data' => $products]);
        }

        $products = LingerieProduct::all();
        return response()->json(['data' => $products]);
    }

    public function getSkus(string $id)
    {
        $product = LingerieProduct::with(['skus.size', 'skus.color'])->findOrFail($id);

        return new LingerieProductResource($product);
    }


}
