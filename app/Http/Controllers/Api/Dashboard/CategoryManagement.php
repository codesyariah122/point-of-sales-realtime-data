<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Events\EventNotification;

class CategoryManagement extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $categories = Category::whereNull('deleted_at')
                ->paginate(10);

            return response()->json([
                'message' => 'List categories product data',
                'data' => $categories
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
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $check_ready = Category::whereName($request->name)->get();

            if(count($check_ready) > 0) {
                return response()->json([
                    'message' => "{$request->name}, its already taken!"
                ]);
            }

            $new_category = new Category;
            $new_category->code = 'CAT-'.Str::random(10);
            $new_category->name = $request->name;
            $new_category->icon = $request->icon;
            $new_category->save();

            return response()->json([
                'message' => 'added new product',
                'data' => $new_category
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
            $roles = json_decode($request->user()->roles[0]->roles);
            if($roles[0] !== "OWNER" && $roles[0] !== "ADMIN") {
                return response()->json([
                    'success' => false,
                    'message' => "Roles $roles[0], tidak di ijinkan menghapus data"
                ]);
            }

            $delete_category = Category::findOrFail($id);
            $delete_category->delete();
            $delete_category->delete();

            $data_event = [
                'notif' => "{$delete_category->name}, success move to trash, please check trash!",
                'data' => $delete_category
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => "Product {$delete_category->name} success move to trash, please check trash",
                'data' => $delete_category
            ]);
        }catch (\Throwable $th) {
            throw $th;
        }
    }
}
