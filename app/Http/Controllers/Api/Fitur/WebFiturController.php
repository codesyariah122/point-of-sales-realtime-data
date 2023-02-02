<?php

namespace App\Http\Controllers\Api\Fitur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Resources\ContextData;
use \Milon\Barcode\DNS1D;
use App\Models\User;
use App\Models\Product;

class WebFiturController extends Controller
{

    public function web_data()
    {
        try {
            $my_context = new ContextData;
            $ownerInfo = $my_context->getInfoData('COD(O.t)');
            return response()->json([
                'message' => 'Owner data info',
                'data' => $ownerInfo
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function barcode_fitur(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'barcode' => 'required',
                'name' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }
            // $d = new DNS1D;
            // $d->setStorPath(__DIR__.'/cache/');
            $barcode = DNS1D::getBarcodePNG($request->barcode.' - '.$request->name, 'C39+');

            if($barcode) {
                return response()->json([
                    'success' => true,
                    'data' => "data:image/png;base64, {$barcode}"
                ]);
            }

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function trash(Request $request)
    {
        try {
            $dataType = $request->query('type');
            switch($dataType):
                case 'USER_DATA':
                    $deleted = User::onlyTrashed()
                        ->with('roles')
                        ->paginate(10);
                break;

                case 'PRODUCT_DATA':
                    $deleted = Product::onlyTrashed()
                        ->with('categories')
                        ->paginate(10);
                break;

                default:
                    $deleted = [];
                break;
            endswitch;

            return response()->json([
                'message' => 'Deleted data on trashed!',
                'data' => $deleted
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
