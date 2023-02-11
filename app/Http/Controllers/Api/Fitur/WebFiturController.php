<?php

namespace App\Http\Controllers\Api\Fitur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Resources\ContextData;
use \Milon\Barcode\DNS1D;
use \Milon\Barcode\DNS2D;
use App\Models\User;
use App\Models\Product;
use App\Events\EventNotification;
use App\Http\Controllers\Api\Resources\ProductPercentage;

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

    public function qrcode_fitur(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'barcode' => 'required',
                'name' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }
          
            $qr = new DNS2D;
            $qrCode = $qr->getBarcodeHTML($request->barcode.'-'.$request->name, 'QRCODE', 4,4);

            if($qrCode) {
                return response()->json([
                    'success' => true,
                    'data' => $qrCode
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

    public function restoreTrash(Request $request, $id)
    {
        try {
            $dataType = $request->query('type');
            switch($dataType):
                case 'USER_DATA':
                    $deleted = User::withTrashed()
                        ->where('id', $id);
                    $deleted->restore();
                    $restored = User::findOrFail($id);
                    
                    $data_event = [
                        'notif' => "{$restored->name}, has been restored!",
                        'data' => $restored
                    ];

                    event(new EventNotification($data_event));
                break;

                case 'PRODUCT_DATA':
                    $deleted = Product::onlyTrashed()
                        ->where('id', $id);
                    $deleted->restore();
                    $restored = Product::findOrFail($id);
                    $data_event = [
                        'notif' => "{$restored->name}, has been restored!",
                        'data' => $restored
                    ];

                    event(new EventNotification($data_event));
                break;

                default:
                    $restored = [];
            endswitch;

            return response()->json([
                'message' => 'Restored data on trashed Success!',
                'data' => $restored
            ]);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function deletePermanently(Request $request, $id)
    {
        try {
            $dataType = $request->query('type');
            switch($dataType):
                case 'USER_DATA':
                    $deleted = User::onlyTrashed()
                        ->where('id', $id)->first();
                    $deleted->roles()->delete();
                    $deleted->forceDelete();
                break;

                case 'PRODUCT_DATA':
                    $deleted = Product::onlyTrashed()
                        ->where('id', $id)->first();
                        $deleted->categories()->delete();
                    $deleted->delete();
                break;

                default:
                    $deleted = [];
            endswitch;

            return response()->json([
                'message' => 'Deleted data on trashed Success!',
                'data' => $deleted
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function totalTrash(Request $request)
    {
        try {
            $type = $request->query('type');
            switch($type) {
                case 'USER_DATA':
                    $countTrash = User::onlyTrashed()
                        ->get();
                break;
                case 'PRODUCT_DATA':
                    $countTrash = Product::onlyTrashed()->get();
                break;
                default:
                    $countTrash = [];
            }
            
            return response()
                ->json([
                    'message' => 'All user trash',
                    'data' => count($countTrash)
                ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function totalData(Request $request)
    {
        try {
            $type = $request->query('type');
            switch($type) {
                case "TOTAL_USER":
                    $totalData = User::whereNull('deleted_at')
                        ->get();
                    $totals = count($totalData);
                break;

                case 'TOTAL_PRODUCT':
                    $totalData = Product::whereNull('deleted_at')->get();
                    $totals = count($totalData);
                    $productPercent = new ProductPercentage;
                    $Apel = $productPercent->getPercentage('Apel', $totals);
                    $Anggur = $productPercent->getPercentage('Anggur', $totals);
                    $Jeruk = $productPercent->getPercentage('Jeruk', $totals);
                    $Pear = $productPercent->getPercentage('Pear', $totals);
                    $Lengkeng = $productPercent->getPercentage('Lengkeng', $totals);
                break;

                default:
                    $totalData = [];
            }

            if($type == "TOTAL_PRODUCT")
                return response()
                ->json([
                    'message' => "Total {$type}",
                    'total' => $totals,
                    'percentage' => [
                        'Apel' => $Apel,
                        'Anggur' => $Anggur,
                        'Jeruk' => $Jeruk,
                        'Pear' => $Pear,
                        'Lengkeng' => $Lengkeng
                    ]
                ]);

            return response()
            ->json([
                'message' => "Total {$type}",
                'total' => $totals
            ]);
        } catch(\Throwable $th) {
            throw $th;
        }
    }
}
