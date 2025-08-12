<?php

namespace App\Http\Controllers;

use App\Models\Mapping;
use App\Models\TempSyncData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MappingController extends Controller
{
    public function updatePrimaryKey(Request $request, $id)
    {
        // Kiểm tra primary_key chỉ nhận '1' hoặc NULL
        $request->validate([
            'primary_key' => 'nullable|in:1' // Chỉ chấp nhận '1' hoặc NULL
        ]);

        try {
            Log::info('Bắt đầu cập nhật primary_key', [
                'id' => $id,
                'primary_key' => $request->input('primary_key')
            ]);

            // Tìm Mapping theo ID
            $mapping = Mapping::findOrFail($id);
            $oldPrimaryKey = $mapping->primary_key;

            // SỬA: Thêm giao dịch để đảm bảo nhất quán
            DB::beginTransaction();

            // Cập nhật primary_key
            $mapping->primary_key = $request->input('primary_key');
            $mapping->save();

            // SỬA: Đảm bảo gọi clearRelatedData khi primary_key đổi từ '1' sang NULL
            if ($oldPrimaryKey === '1' && is_null($mapping->primary_key)) {
                Log::info('Gọi clearRelatedData do primary_key đổi thành NULL', [
                    'doc_variable_id' => $mapping->doc_variable_id
                ]);
                TempSyncData::clearRelatedData($mapping->doc_variable_id);
            }

            DB::commit();

            Log::info('Cập nhật primary_key thành công', [
                'doc_variable_id' => $mapping->doc_variable_id,
                'primary_key' => $mapping->primary_key
            ]);

            return response()->json(['success' => 'Cập nhật primary_key thành công']);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Lỗi cập nhật primary_key: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Lỗi cập nhật primary_key: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Log::info('Bắt đầu xóa Mapping', ['id' => $id]);

            // Tìm Mapping theo ID
            $mapping = Mapping::findOrFail($id);
            $variableId = $mapping->doc_variable_id;

            // SỬA: Thêm giao dịch để đảm bảo nhất quán
            DB::beginTransaction();

            // Gọi clearRelatedData trước khi xóa
            Log::info('Gọi clearRelatedData trước khi xóa Mapping', [
                'doc_variable_id' => $variableId
            ]);
            TempSyncData::clearRelatedData($variableId);

            // Xóa Mapping
            $mapping->delete();

            DB::commit();

            Log::info('Xóa Mapping thành công', [
                'doc_variable_id' => $variableId
            ]);

            return response()->json(['success' => 'Xóa Mapping thành công']);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Lỗi xóa Mapping: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Lỗi xóa Mapping: ' . $e->getMessage()], 500);
        }
    }
}