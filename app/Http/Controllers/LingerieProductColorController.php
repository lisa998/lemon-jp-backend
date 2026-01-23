<?php

namespace App\Http\Controllers;

use App\Models\LingerieProductColor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LingerieProductColorController extends Controller
{
    private array $validators = [
        'color'=> 'required|string|max:32|unique:lingerie_product_colors,color'
    ];
    public function get(Request $request){
        $sort = $request->query('sort');
        $allowedSort = ['color'];

        if(in_array($sort, $allowedSort)){
            $colors = LingerieProductColor::orderBy($sort)->get();
            return response()->json(['data' => $colors]);
        }
        $colors = LingerieProductColor::all();
        return response()->json(['data' => $colors]);
    }
    public function getById(string $id){

       $color = LingerieProductColor::findOrFail($id);
       return response()->json(['data' => $color]);
    }

    public function create(Request $request){
        $request->merge([
            'color'=>trim($request->color)
        ]);
        $request->validate([
            'color'=> 'required|string|max:32|unique:lingerie_product_colors,color'
        ]);
        $color = LingerieProductColor::create([
            'color' =>$request->color
        ]);
        return response()->json(['data' => $color], 201);
    }

    public function update(Request $request, string $id){

        $request->validate([
            'color'=> [
                'required',
                'string',
                'max:32',
                Rule::unique('lingerie_product_colors','color')->ignore($id)
            ]
        ]);
        $color = LingerieProductColor::findOrFail($id);
        $color->update([
            'color' => $request->input('color')
        ]);
        return response()->json(['data'=> $color->fresh()]);
    }
}
