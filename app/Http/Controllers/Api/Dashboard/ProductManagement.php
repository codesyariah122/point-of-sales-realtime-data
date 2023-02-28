<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\Category;
use Piqer\Barcode;
use App\Events\EventNotification;
use App\Helpers\UserHelpers;

class ProductManagement extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    private $helpers;

    public function __construct()
    {
        $this->helpers = new UserHelpers;   
    }


    public function read(Message $message)
    {
        $message->markAsRead();

        RateLimiter::clear('send-message:'.$message->user_id);

        return $message;
    }

    public function index(Request $request)
    {
        try {
            $name = $request->query('name');
            $barcode = $request->query('barcode');
            $category = $request->query('category_id');

            if($name) {
                $products = Product::whereNull('deleted_at')
                    ->with('categories')
                    ->where('name', 'LIKE', '%'.$name.'%')
                    // ->wherePivot('category_id', $category)
                    ->latest()
                    // ->orderBy('id', 'DESC')
                    ->paginate(5);
            } elseif ($category) {
                $products = Product::whereHas('categories', function($q) use ($category) {
                    $q->where('category_id', $category);
                })
                ->with('categories')
                ->latest()
                ->paginate(5);
                // var_dump($products); die;
            } elseif ($barcode) {

                $products = Product::whereNull('deleted_at')
                    ->with('categories')
                    ->where('barcode', 'LIKE', '%'.$barcode.'%')
                    // ->wherePivot('category_id', $category)
                    ->latest()
                    // ->orderBy('id', 'DESC')
                    ->paginate(5);
            } else {
                $products = Product::whereNull('deleted_at')
                    ->with('categories')
                    ->latest()
                    ->paginate(5);
            }

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
                // 'size' => 'required',
                'buy_price' => 'required|integer',
                'sell_price' => 'required|integer',
                'stock' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $check_roles = $this->helpers;
            $roles = json_decode($request->user()->roles[0]->roles);
            if($check_roles->checkRoles($request->user())):
                return response()->json([
                    'success' => false,
                    'message' => "Roles {$roles[0]}, tidak di ijinkan mengupdate data"
                ]);
            endif;
            

            $check_product = Product::whereName($request->name)->get();

            if(count($check_product) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Product {$request->name}, its already been taken!"
                ]);
            }

            // $tests = json_encode($request->size);
            // var_dump($tests); die;

            $new_product = new Product;
            $new_product->name = $request->name;
            $new_product->size = json_encode($request->size);
            $new_product->buy_price = $request->buy_price;
            $new_product->sell_price = $request->sell_price;
            $new_product->stock = $request->stock;
            $new_product->save();
            $new_productId = $new_product->id;
            $product_barcode = Product::findOrFail($new_productId);
            $product_barcode->barcode = $new_productId > 9 ? "PROD-0{$new_productId}" : "PROD-00{$new_productId}";
            $product_barcode->save();

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
                'success' => true,
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
    public function show($barcode)
    {
        try {
            $product = Product::where('barcode', $barcode)->with('categories')->get();

            // var_dump($product); die;
            if(count($product) > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "Detailed {$product[0]->name}",
                    'data' => $product[0]
                ]);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
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
    public function update(Request $request, $barcode)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                // 'buy_price' => 'required|integer',
                // 'sell_price' => 'required|integer',
                'stock' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $check_roles = $this->helpers;
            $roles = json_decode($request->user()->roles[0]->roles);
            if($check_roles->checkRoles($request->user())):
                return response()->json([
                    'success' => false,
                    'message' => "Roles {$roles[0]}, tidak di ijinkan mengupdate data"
                ]);
            endif;

            $prepare_product = Product::whereBarcode($barcode)->first();
            // var_dump($prepare_product); die;
            $prepare_product->name = $request->name;
            $prepare_product->size = json_encode($request->size);
            $prepare_product->buy_price = $request->buy_price;
            $prepare_product->sell_price = $request->sell_price;
            $prepare_product->stock = $request->stock;
            $prepare_product->save();

            $update_categoryId = $request->category_id;
            $prepare_product->categories()->sync($update_categoryId);

            $product_hasUpdate = Product::with('categories')
                ->findOrFail($prepare_product->id);

            $data_event = [
                'notif' => "{$product_hasUpdate->name}, berhasil di tambahkan!",
                'data' => $product_hasUpdate
            ];

            event(new EventNotification($data_event));
            
            return response()->json([
                'success' => true,
                'message' => "Success updated {$prepare_product->name}",
                'data' => $product_hasUpdate
            ]);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {
            $check_roles = $this->helpers;
            $roles = json_decode($request->user()->roles[0]->roles);
            if($check_roles->checkRoles($request->user())):
                return response()->json([
                    'success' => false,
                    'message' => "Roles {$roles[0]}, tidak di ijinkan mengupdate data"
                ]);
            endif;

            $delete_product = Product::findOrFail($id);
            $delete_product->delete();

            $data_event = [
                'notif' => "{$delete_product['name']}, success move to trash, please check trash!",
                'data' => $delete_product
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => "Product {$delete_product['name']} success move to trash, please check trash",
                'data' => $delete_product
            ]);
        }catch (\Throwable $th) {
            throw $th;
        }
    }
}
