<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Product;
use Piqer\Barcode;

class ProductManagement extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $products = Product::with('categories')
                ->paginate(10);

            return response()->json([
                'message' => 'List product data',
                'data' => $products
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'size' => 'required',
                'buy_price' => 'required|integer',
                'sell_price' => 'required|integer',
                'stock' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $check_product = Product::whereName($request->name)->get();

            if(count($check_product) > 0) {
                return response()->json([
                    'message' => "Product {$request->name}, its already been taken!"
                ]);
            }

            $new_product = new Product;
            $new_product->barcode = "PROD-".Str::random(10);
            $new_product->name = $request->name;
            $new_product->size = json_encode($request->size);
            $new_product->buy_price = $request->buy_price;
            $new_product->sell_price = $request->sell_price;
            $new_product->stock = $request->stock;
            $new_product->save();
            $new_productId = $new_product->id;
            $new_product->categories()->sync($request->category_id);

            $new_productAdd = Product::whereId($new_productId)
                ->with('categories')
                ->get();

            return response()->json([
                'message' => 'added new product',
                'data' => $new_productAdd
            ]);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
