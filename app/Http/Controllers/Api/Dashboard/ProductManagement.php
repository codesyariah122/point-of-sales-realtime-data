<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Product;
use Piqer\Barcode;
use App\Events\EventNotification;

class ProductManagement extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $name = $request->query('name');
            $barcode = $request->query('barcode');
            $products = Product::whereNull('deleted_at')
                ->with('categories')
                ->where('name', 'LIKE', '%'.$name.'%')
                ->where('barcode', 'LIKE', '%'.$barcode.'%')
                ->paginate(5);

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

            $data_event = [
                'notif' => "{$new_productAdd[0]->name}, berhasil di tambahkan!",
                'data' => $new_productAdd
            ];

            event(new EventNotification($data_event));

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
        try {
            $delete_product = Product::findOrFail($id);
            $delete_product->categories()->delete();
            $delete_product->delete();

             $data_event = [
                'notif' => "{$delete_product->name}, berhasil di hapus!",
                'data' => $delete_product
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => "Product {$delete_product->name} berhasil di hapus",
                'data' => $delete_product
            ]);
        }catch (\Throwable $th) {
            throw $th;
        }
    }
}
