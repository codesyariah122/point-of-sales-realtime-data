<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use App\Events\EventNotification;
use App\Helpers\UserHelpers;
use App\Models\Customer;

class CustomerManagement extends Controller
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
            $phone = $request->query('phone');
            $email = $request->query('email');

            if($name) {
                $customers = Customer::whereNull('deleted_at')
                ->where('name', 'LIKE', '%'.$name.'%')
                ->get();
            } elseif ($phone) {
                $customers = Customer::whereNull('deleted_at')
                ->where('phone', 'LIKE', '%'.$phone.'%')
                ->get();
            } elseif ($email) {
                $customers = Customer::whereNull('deleted_at')
                ->where('email', 'LIKE', '%'.$email.'%')
                ->get();
            } else {
                $customers = Customer::whereNull('deleted_at')
                ->latest()
                ->paginate(5);
            }

            return response()->json([
                'message' => 'List customer',
                'data' => $customers
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
            $helper = new UserHelpers;
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'phone' => 'numeric|min:12'
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }


            $check_customer = Customer::whereName($request->name)->get();
            if(count($check_customer) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Customer {$request->name}, its already been taken!"
                ]);
            }

            $customer_phone = $helper->formatPhoneNumber($request->phone);

            if($customer_phone) {
                $new_customer = new Customer;
                $new_customer->name = $request->name;
                $new_customer->phone = $request->phone ? $helper->formatPhoneNumber($request->phone) : null;
                $new_customer->email = $request->email;
                $new_customer->address = $request->address;
                $new_customer->save();


                $new_customerAdd = Customer::whereId($new_customer->id)
                ->get();

                $data_event = [
                    'notif' => "{$new_customerAdd[0]->name}, berhasil di tambahkan!",
                    'data' => $new_customerAdd
                ];

                event(new EventNotification($data_event));

                return response()->json([
                    'success' => true,
                    'message' => 'added new customer',
                    'data' => $new_customerAdd
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Phone number it's not valid!"
                ]);
            }

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
        try {
            $customer = Customer::findOrFail($id);
            return response()->json([
                'message' => 'Show detail customer',
                'customer' => $customer
            ]);
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
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required'
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

            $edited_data = Customer::findOrFail($id);
            $customer_edit = Customer::findOrFail($id);
            $customer_edit->name = $request->name;
            $customer_edit->phone = $request->phone;
            $customer_edit->email = $request->email;
            $customer_edit->notes = $request->notes;
            $customer_edit->save();
            $update_customer = Customer::findOrFail($customer_edit->id);

            $data_event = [
                'notif' => "Customer {$edited_data->name}, telah di update menjadi {$customer_edit->name}!",
                'data' => $update_customer
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => $edited_data->name." telah di ubah menjadi {$update_customer->name}!",
                'data' => $update_customer
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
            $deleted_customer = Customer::findOrFail($id);
            $destroy_customer = Customer::findOrFail($id);
            $destroy_customer->delete();

            $data_event = [
                'notif' => "{$destroy_customer->name}, success move to trash, please check trash!",
                'data' => $destroy_customer
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => $deleted_customer['name']." success deleted customer data!",
                'data' => $destroy_customer
            ]);

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
