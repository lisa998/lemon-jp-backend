<?php

namespace App\Http\Controllers;

use App\Http\Resources\LingerieProductColorImageResource;
use App\Models\LingerieProduct;
use App\Models\LingerieProductColor;
use App\Models\LingerieProductColorImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;

class LingerieProductColorImageController extends Controller
{
    public function get(string $productId)
    {
        LingerieProduct::findOrFail($productId);

        $images = LingerieProductColorImage::with('color')
            ->where('product_id', $productId)
            ->get();

        return LingerieProductColorImageResource::collection($images);
    }

    public function create(Request $request, string $productId)
    {
        $request->validate([
            'color_id' => [
                'required_without:color',
                'integer',
                'exists:lingerie_product_colors,id',
                Rule::unique('lingerie_product_color_images', 'color_id')
                    ->where('product_id', $productId)
            ],
            'color' => 'required_without:color_id|string|max:32',
            'image_url' => 'required|url',
        ]);


        if (!LingerieProduct::where('id', $productId)->exists()) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        if (!$request->input('color_id') && $request->input('color')) {
            $request->merge(['color' => trim($request->color)]);

            $color = LingerieProductColor::firstOrCreate([
                'color' => $request->color
            ]);

            $request->merge(['color_id' => $color->id]);
        }

        $image = LingerieProductColorImage::create([
            'product_id' => $productId,
            'color_id' => $request->input('color_id'),
            'image_url' => $request->input('image_url'),
        ]);
        return new LingerieProductColorImageResource($image);


    }

    public function update(Request $request, string $productId, string $colorImageId)
    {

        $request->validate([
            'image_url' => 'required|url',
        ]);

        $image = LingerieProductColorImage::where('product_id', $productId)->findOrFail($colorImageId);
        $image->update([
            'image_url' => $request->input('image_url'),
        ]);

        return response()->json(['data' => $image->fresh()]);


    }
}
