<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use App\Events\EventNotification;
use App\Helpers\UserHelpers;
use App\Models\Supplier;

class SupplierManagement extends Controller
{
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
            $company_name = $request->query('company_name');
            $phone = $request->query('phone');
            $email = $request->query('email');

            if($name) {
                $suppliers = Supplier::whereNull('deleted_at')
                ->where('name', 'LIKE', '%'.$name.'%')
                ->get();
            } elseif ($phone) {
                $suppliers = Supplier::whereNull('deleted_at')
                ->where('phone', 'LIKE', '%'.$phone.'%')
                ->get();
            } elseif ($email) {
                $suppliers = Supplier::whereNull('deleted_at')
                ->where('email', 'LIKE', '%'.$email.'%')
                ->get();
            } else {
                $suppliers = Supplier::whereNull('deleted_at')
                ->latest()
                ->paginate(5);
            }

            return response()->json([
                'message' => 'List Supplier',
                'data' => $suppliers
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
                'company_name' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $check_supplier = Supplier::whereName($request->name)->get();
            if(count($check_supplier) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Customer {$request->company_name}, its already been taken!"
                ]);
            }

            $new_supplier = new Supplier;
            $new_supplier->name = $request->name;
            $new_supplier->company_name = $request->company_name;
            $new_supplier->phone = $request->phone;
            $new_supplier->email = $request->email;
            $new_supplier->address = $request->address;
            $new_supplier->save();


            $new_supplierAdd = Supplier::whereId($new_supplier->id)
            ->get();

            $data_event = [
                'notif' => "{$new_supplierAdd[0]->company_name}, berhasil di tambahkan!",
                'data' => $new_supplierAdd
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => 'added new supplier',
                'data' => $new_supplierAdd
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
        try {
            $supplier = Supplier::findOrFail($id);
            return response()->json([
                'message' => 'Show detail supplier',
                'customer' => $supplier
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

            $edited_data = Supplier::findOrFail($id);
            $supplier_edit = Supplier::findOrFail($id);
            $supplier_edit->name = $request->name;
            $supplier_edit->phone = $request->phone;
            $supplier_edit->email = $request->email;
            $supplier_edit->notes = $request->notes;
            $supplier_edit->save();
            $update_supplier = Supplier::findOrFail($supplier_edit->id);

            $data_event = [
                'notif' => "Customer {$edited_data->company_name}, telah di update!",
                'data' => $update_supplier
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => $edited_data->company_name." telah di update!",
                'data' => $update_supplier
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
            $delete_supplier = Supplier::findOrFail($id);
            $destroy_supplier = Supplier::findOrFail($id);
            $destroy_supplier->delete();

            $data_event = [
                'notif' => "{$destroy_supplier->company_name}, success move to trash, please check trash!",
                'data' => $destroy_supplier
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => $delete_supplier['name']." success deleted customer data!",
                'data' => $destroy_supplier
            ]);

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
