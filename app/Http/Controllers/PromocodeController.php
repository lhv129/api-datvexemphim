<?php

namespace App\Http\Controllers;

use App\Models\Promo_code;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PromocodeController extends Controller
{
    public function index() {
        $promo_codes = Promo_code::select('id','code','description','discount_amount','start_date','end_date','status')
                                    ->get();
        return $this->responseCommon(200,'Lấy Danh Sách Thành Công!',$promo_codes);
    }

    public function store(Request $request) {
        // dd($request);
        $rules = $this->validateCreatePromocode();
        $alert = $this->alertCreatePromocode();
        $validator = Validator::make($request->all(), $rules, $alert);

        if ($validator->fails()) {
            return $this->responseError(422, 'Dữ liệu không hợp lệ.', $validator->errors());
        }

        try {
            // Kiểm tra xem Code có bị xóa mềm không
            $promo_code = Promo_code::withTrashed()->where('code', $request->code)->first();

            if ($promo_code) {
                // Nếu đã bị xóa mềm, khôi phục lại
                $promo_code->restore();
                $promo_code->update([
                    'code' => $request->code,
                    'description' => $request->description,
                    'discount_amount' => $request->discount_amount,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                ]);

                return $this->responseCommon(200, "Code đã được khôi phục và cập nhật.", $promo_code);
            }

            $promo_code = Promo_code::create([
                'code' => $request->code,
                'description' => $request->description,
                'discount_amount' => $request->discount_amount,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            return $this->responseCommon(201, "Thêm mới thành công.", $promo_code);
        } catch (\Exception $e) {
            return $this->responseError(500, "Lỗi xử lý.", [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function update(Request $request,$id) {
        try{
            // dd($request);
            $promo_code = Promo_code::findOrFail($id);
            // if(!$promo_code) {
            //     return response()->json([
            //         'message' => 'Mã  k tồn tại'
            //     ],404);
            // }
            // dd($promo_code);
            $rules = $this->validateUpdatePromocode($id);
            $alert = $this->alertUpdatePromocode();
            $validator = Validator::make($request->all(),$rules,$alert);
            if($validator->fails()) {
                return $this->responseError(422,'Dữ Liệu không hợp lệ', $validator->errors());

            } else {

                $promo_code->update([
                    'code' => $request->code,
                    'description' => $request->description,
                    'discount_amount' => $request->discount_amount,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    // 'status' => $request->status,
                ]);
                return $this->responseCommon(200,"Cập Nhật thành công.",$promo_code);
            }
        }catch (\Exception $e) {
            Log::error('Lỗi chi tiết: ' . $e->getMessage());
            return $this->responseCommon(404, "Code Không tồn tại hoặc đã bị xóa.", []);
        }
    }

    public function destroy($id) {
        try {
            $promo_code = Promo_code::findOrFail($id);
            $promo_code->delete();
            return $this->responseCommon(200,"Xóa Thành công.",[]);
        } catch (\Exception $e) {
            return $this->responseCommon(404,"Code Không tồn tại hoặc đã bị xóa.",[]);

        }
    }

    //Validate

    public function validateCreatePromocode(){
        return [

            'code' => 'required|unique:promo_codes,code,NULL,id,deleted_at,NULL',
            'description' =>'required' ,
            'discount_amount' =>'required' ,
            'start_date' =>'required' ,
            'end_date' =>'required' ,

        ];
    }
    public function alertCreatePromocode(){
        return [
            'required' => 'không được để trống',
            'unique' => 'code bị trùng',
        ];
    }
    public function validateUpdatePromocode($id){
        return [
            'code' => 'required|unique:promo_codes,code,'.$id,
            'description' =>'required' ,
            'discount_amount' =>'required' ,
            'start_date' =>'required' ,
            'end_date' =>'required' ,
        ];
    }
    public function alertUpdatePromocode(){
        return [
            'required' => 'không được để trống',
            'unique' => 'code bị trùng',
        ];
    }
}
