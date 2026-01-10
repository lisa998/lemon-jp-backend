<?php

namespace App\Http\Controllers;

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
        $size = LingerieProductSize::where('size', $sizeInput)->first();
        $color = LingerieProductColor::where('color', $colorInput)->first();

        if ($size === null && $sizeInput !== null) {
            return response()->json(['data' => []]);
        }
        if ($color === null && $colorInput !== null) {
            return response()->json(['data' => []]);
        }

        if ($size && $color) {
            $sizeId = $size->id;
            $colorId = $color->id;
            $products = LingerieProduct::whereHas('skus', function ($query) use ($sizeId, $colorId) {
                $query->where('size_id', $sizeId)
                    ->where('color_id', $colorId);
            })->get();
            return response()->json(['data' => $products]);
        }

        if ($size) {
            $sizeId = $size->id;
            $products = LingerieProduct::whereHas('skus', function ($query) use ($sizeId) {
                $query->where('size_id', $sizeId);
            })->get();
            return response()->json(['data' => $products]);
        }
        if ($color) {
            $colorId = $color->id;
            $products = LingerieProduct::whereHas('skus', function ($query) use ($colorId) {
                $query->where('color_id', $colorId);
            })->get();
            return response()->json(['data' => $products]);
        }

        $products = LingerieProduct::all();
        return response()->json(['data' => $products]);
    }


}
