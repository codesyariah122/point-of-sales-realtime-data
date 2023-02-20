<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use App\Events\EventNotification;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\GrandTotalOrder;
use App\Models\Product;
use App\Helpers\UserHelpers;

class OrderManagement extends Controller
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
            $invoice_number = $request->query('invoice_number');
            $from = $request->query('from') ? Carbon::createFromFormat('Y-m-d', $request->query('from'))->startOfDay() : NULL;

            $to = $request->query('to') ? Carbon::createFromFormat('Y-m-d',  $request->query('to'))->endOfDay() : NULL;
            // var_dump($from); die;

            if($invoice_number) {
                $orders = Order::whereNull('deleted_at')
                    ->with('products')
                    ->with('customers')
                    ->with('users')
                    ->where('invoice_number', 'LIKE', '%'.$invoice_number.'%')
                    ->get();
            }elseif($from || $to){
                $orders = Order::whereNull('deleted_at')
                    ->with('products')
                    ->with('customers')
                    ->with('users')
                    ->whereBetween('created_at', [$from, $to])
                    ->get();
            } else {
                $orders = Order::whereNull('deleted_at')
                    ->with('products')
                    ->with('customers')
                    ->with('users')
                    ->latest()
                    ->paginate(10);
            }

            return response()->json([
                'message' => 'List of order data',
                'data' => $orders
            ]);

        } catch(\Throwable $th) {
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
                'product_id' => 'required',
                'user_id' => 'required',
                'qty' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $latest = Order::latest()->first();

            if(!$latest) {
                $inv_number = date('dmy').rand(0,100);
            } else {
                $inv_number = date('dmy').$latest->id+1;
            }

            $productInOrder = Product::findOrFail($request->product_id);

            $totals = [];
            $new_order = new Order;
            $new_order->invoice_number = $inv_number;
            $new_order->product_id = $request->product_id;
            $new_order->price = $productInOrder['buy_price'];
            $new_order->user_id = $request->user_id;
            $new_order->customer_id = $request->customer_id;
            $new_order->qty = $request->qty;
            $new_order->total = ($request->qty * $productInOrder['buy_price']);
            $new_order->save();
            $new_orderId = $new_order->id;
            $new_order->products()->sync($request->product_id);
            $new_order->users()->sync($request->user_id);

            if($new_order->customer_id !== NULL) {
                $new_order->customers()->sync($request->customer_id);
            }

            array_push($totals, $request->qty * $productInOrder['buy_price']);

            // var_dump($totals); die;

            $grand_total_order = new GrandTotalOrder;
            $grand_total_order->order_id = $new_order->id;
            $grand_total_order->order_date = Carbon::now();
            $grand_total_order->total_in_orders = json_encode($totals);
            $grand_total_order->totals = $new_order->total;
            
            // foreach($totals as $key => $value) {
            //     $grand_total_order->totals = $value + $value;
            // }
            
            $grand_total_order->save();

            $new_orderAdd = Order::whereId($new_orderId)
            ->get();

            $data_event = [
                'notif' => "{$new_orderAdd[0]->invoice_number}, berhasil di tambahkan!",
                'data' => $new_orderAdd,
                'totals' => $grand_total_order
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => "Product order with invoice number {$new_orderAdd[0]->invoice_number}, hass been created!",
                'data' => $new_orderAdd,
                'totals' => $grand_total_order
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
            // var_dump($id); die;
            $delete_order = Order::findOrFail($id);
            $delete_order->delete();

            $data_event = [
                'notif' => "{$delete_order['invoice_number']}, success move to trash, please check trash!",
                'data' => $delete_order
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => "Order with invoice number: {$delete_order['invoice_number']} success move to trash, please check trash",
                'data' => $delete_order
            ]);

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
