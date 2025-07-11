<?php

namespace App\Http\Controllers;

use App\Models\Docfile;
use App\Models\DocVariable;
use App\Models\Excelfiles;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Style\Paragraph;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Voku\Helper\ASCII;

class DocController extends Controller
{
    /**
     * Hiển thị trang chính với danh sách tài liệu và biến đã chọn.
     */
    public function index()
    {
        $docFiles = Docfile::all();
        $selectedDocs = Docfile::where('is_selected', 1)->with('variables')->get();
        $excelFiles = Excelfiles::with('sheets')->get();
        $excelFilesWithCreatedSheets = Excelfiles::whereHas('sheets', function ($query) {
            $query->where('is_table_created', true);
        })->with(['sheets' => function ($query) {
            $query->where('is_table_created', true);
        }])->get();

        return view('file_reader', compact('docFiles', 'selectedDocs', 'excelFiles', 'excelFilesWithCreatedSheets'));
    }

    /**
     * Thêm file Doc mới.
     */
    public function addDoc(Request $request)
    {
        $request->validate([
            'file_path' => 'required|string',
        ]);

        $filePath = trim($request->input('file_path'), "\"'");
        $filePath = str_replace('/', '\\', $filePath);

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File không tồn tại tại: ' . $filePath], 404);
        }

        $allowedExtensions = ['doc', 'docx'];
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            return response()->json(['error' => 'Định dạng file không hợp lệ. Chỉ hỗ trợ .doc hoặc .docx.'], 400);
        }

        try {
            $phpWord = IOFactory::load($filePath, 'Word2007');
            $fileName = basename($filePath);
            if (Docfile::where('path', $filePath)->exists()) {
                return response()->json(['error' => 'File Word với đường dẫn này đã tồn tại trong hệ thống.'], 400);
            }

            $content = $this->extractWordContent($phpWord);

            $docfile = Docfile::create([
                'name' => $fileName,
                'path' => $filePath,
                'content' => $content,
                'is_selected' => 0,
            ]);

            return redirect()->route('file.index')->with('success', 'Đã thêm file Word: ' . $fileName);
        } catch (\PhpOffice\PhpWord\Exception\Exception $e) {
            Log::error('Lỗi khi đọc file Word: ' . $e->getMessage());
            return response()->json(['error' => 'Không thể đọc file Word: Định dạng sai hoặc file hỏng.'], 400);
        } catch (\Exception $e) {
            Log::error('Lỗi hệ thống: ' . $e->getMessage());
            return response()->json(['error' => 'Lỗi không xác định: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Xóa file Doc và bảng động tương ứng.
     */
    public function removeDoc(Request $request)
    {
        $request->validate([
            'doc_id' => 'required|integer|exists:docfile,id',
        ]);

        $doc = Docfile::findOrFail($request->doc_id);
        $filePath = $doc->path;

        // Xóa bảng động nếu tồn tại
        if ($doc->table_name && Schema::hasTable($doc->table_name)) {
            try {
                Schema::dropIfExists($doc->table_name);
                DocVariable::where('table_var_name', $doc->table_name)->update(['is_table_variable_created' => 0]);
            } catch (\Exception $e) {
                Log::error('Lỗi khi xóa bảng động: ' . $e->getMessage());
                return redirect()->route('file.index')->with('error', 'Không thể xóa bảng động "' . $doc->table_name . '": ' . $e->getMessage());
            }
        }

        $doc->delete(); // Xóa docfile, tự động xóa doc_variables nhờ onDelete('cascade')

        return redirect()->route('file.index')->with('success', 'Đã xóa file Word "' . $filePath . '" và bảng động (nếu có).');
    }

    /**
     * Đọc nội dung file Doc.
     */
    public function readDoc($docId)
    {
        $doc = Docfile::findOrFail($docId);
        $filePath = $doc->path;

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File không tồn tại tại: ' . $filePath], 404);
        }

        $content = $doc->content ?: 'Nội dung không có sẵn';

        return view('doc_data', [
            'content' => $content,
            'fileName' => $doc->name,
            'success' => 'Đã đọc file Word "' . $doc->name . '" thành công.'
        ]);
    }

    /**
     * Chọn file Doc, trích xuất biến, tạo bảng động, và đánh dấu đã chọn.
     */
    public function selectDoc($docId)
    {
        $doc = Docfile::findOrFail($docId);
        $content = $doc->content ?: '';

        // Chuyển đổi HTML thành văn bản thuần túy
        $plainText = strip_tags($content);
        $plainText = html_entity_decode($plainText, ENT_QUOTES, 'UTF-8');

        // Trích xuất các biến dạng {{variable}}
        preg_match_all('/\{\{([^{}]+)\}\}/', $plainText, $matches);
        $variables = array_unique(array_map('trim', $matches[1] ?? []));

        // Lưu các biến vào bảng doc_variables
        foreach ($variables as $variable) {
            DocVariable::firstOrCreate([
                'docfile_id' => $doc->id,
                'var_name' => $variable,
            ], [
                'table_var_name' => null,
                'is_table_variable_created' => 0,
            ]);
        }

        // Chuẩn hóa tên bảng: doc_{doc_id}_{tên_doc}
        $docName = $doc->name ?: 'unnamed_doc';
        $docName = $this->convertVietnameseToNonAccent(pathinfo($docName, PATHINFO_FILENAME));
        $docName = preg_replace('/[^a-z0-9_]/', '_', strtolower($docName));
        $docName = preg_replace('/_+/', '_', $docName);
        $docName = trim($docName, '_');
        $docName = Str::limit($docName, 50, '');
        $tableName = empty($docName) ? "doc_{$docId}_unnamed" : "doc_{$docId}_{$docName}";

        // Kiểm tra xem bảng đã tồn tại chưa
        if (!Schema::hasTable($tableName)) {
            try {
                Schema::create($tableName, function (Blueprint $table) use ($variables) {
                    $table->id();
                    $usedColumns = [];
                    foreach ($variables as $variable) {
                        // Chuẩn hóa tên cột
                        $columnName = $this->convertVietnameseToNonAccent($variable);
                        $columnName = preg_replace('/[^a-z0-9_]/', '_', strtolower($columnName));
                        $columnName = preg_replace('/_+/', '_', $columnName);
                        $columnName = trim($columnName, '_');
                        $columnName = Str::limit($columnName, 64, '');

                        // Kiểm tra trùng lặp tên cột
                        $originalColumnName = $columnName;
                        $suffix = 1;
                        while (in_array($columnName, $usedColumns)) {
                            $columnName = Str::limit($originalColumnName . '_' . $suffix++, 64, '');
                        }
                        $usedColumns[] = $columnName;

                        // Dự phòng nếu tên cột rỗng
                        if (empty($columnName)) {
                            $columnName = 'column_' . md5($variable);
                        }

                        $table->string($columnName)->nullable();
                    }
                    $table->timestamps();
                });

                // Cập nhật bảng doc_variables
                DocVariable::where('docfile_id', $doc->id)
                    ->whereIn('var_name', $variables)
                    ->update([
                        'table_var_name' => $tableName,
                        'is_table_variable_created' => 1,
                    ]);

                // Lưu tên bảng vào cột table_name của docfile
                $doc->update(['table_name' => $tableName]);
            } catch (\Exception $e) {
                Log::error('Lỗi khi tạo bảng động: ' . $e->getMessage());
                return redirect()->route('file.index')->with('error', 'Không thể tạo bảng cho file "' . $doc->name . '": ' . $e->getMessage());
            }
        }

        // Đánh dấu tài liệu đã chọn
        $doc->update(['is_selected' => 1]);

        // Lấy lại danh sách biến thực tế từ doc_variables
        $docVariables = DocVariable::where('docfile_id', $doc->id)->pluck('var_name')->toArray();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'doc_name' => $doc->name,
                'variables' => $docVariables, // Mảng tên biến
            ]);
        }
        // Nếu là request thường, redirect như cũ
        return redirect()->route('file.index')->with('success', 'Đã chọn tài liệu "' . $doc->name . '" và trích xuất biến thành công.');
    }

    /**
     * Trích xuất nội dung từ file Word.
     */
    protected function extractWordContent($phpWord)
    {
        $content = '';
        foreach ($phpWord->getSections() as $section) {
            $header = $section->getHeader();
            if ($header) {
                foreach ($header->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $headerText = htmlspecialchars($element->getText(), ENT_QUOTES, 'UTF-8');
                        $content .= "<div style='font-weight: bold; margin-bottom: 16px; font-size: 20px; color: #333;'>$headerText</div>";
                    }
                }
            }
            foreach ($section->getElements() as $element) {
                if ($element instanceof TextRun) {
                    $paraStyle = method_exists($element, 'getParagraphStyle') ? $element->getParagraphStyle() : null;
                    $alignment = $this->getAlignment($paraStyle);
                    $content .= "<p style=\"text-align: $alignment; margin-bottom: 16px;\">";
                    foreach ($element->getElements() as $textElement) {
                        if (method_exists($textElement, 'getText')) {
                            $text = htmlspecialchars($textElement->getText(), ENT_QUOTES, 'UTF-8');
                            $fontStyle = method_exists($textElement, 'getFontStyle') ? $textElement->getFontStyle() : null;
                            $style = '';
                            if ($fontStyle) {
                                $fontSize = method_exists($fontStyle, 'getSize') && $fontStyle->getSize() ? $fontStyle->getSize() : 14;
                                $style .= "font-size: {$fontSize}px; color: #000;";
                                if ($fontStyle->isBold()) {
                                    $style .= 'font-weight: bold;';
                                }
                                if ($fontStyle->isItalic()) {
                                    $style .= 'font-style: italic;';
                                }
                                if (method_exists($fontStyle, 'getName') && $fontStyle->getName()) {
                                    $style .= "font-family: '{$fontStyle->getName()}';";
                                }
                            } else {
                                $style .= 'font-size: 11px; color: #000;';
                            }
                            $content .= $style ? "<span style=\"$style\">$text</span>" : $text;
                        }
                    }
                    $content .= '</p>';
                } elseif ($element instanceof Table) {
                    $content .= '<table class="table table-bordered" style="min-width: 600px; border-collapse: collapse;">';
                    foreach ($element->getRows() as $row) {
                        $content .= '<tr>';
                        foreach ($row->getCells() as $cell) {
                            $content .= '<td style="border: 1px solid #dee2e6; padding: 12px;">';
                            foreach ($cell->getElements() as $cellElement) {
                                if ($cellElement instanceof TextRun) {
                                    $paraStyle = method_exists($cellElement, 'getParagraphStyle') ? $cellElement->getParagraphStyle() : null;
                                    $alignment = $this->getAlignment($paraStyle);
                                    $content .= "<div style=\"text-align: $alignment;\">";
                                    foreach ($cellElement->getElements() as $textElement) {
                                        if (method_exists($textElement, 'getText')) {
                                            $text = htmlspecialchars($textElement->getText(), ENT_QUOTES, 'UTF-8');
                                            $fontStyle = method_exists($textElement, 'getFontStyle') ? $textElement->getFontStyle() : null;
                                            $style = '';
                                            if ($fontStyle) {
                                                $fontSize = method_exists($fontStyle, 'getSize') && $fontStyle->getSize() ? $fontStyle->getSize() : 14;
                                                $style .= "font-size: {$fontSize}px; color: #000;";
                                                if ($fontStyle->isBold()) {
                                                    $style .= 'font-weight: bold;';
                                                }
                                                if ($fontStyle->isItalic()) {
                                                    $style .= 'font-style: italic;';
                                                }
                                                if (method_exists($fontStyle, 'getName') && $fontStyle->getName()) {
                                                    $style .= "font-family: '{$fontStyle->getName()}';";
                                                }
                                            } else {
                                                $style .= 'font-size: 11px; color: #000;';
                                            }
                                            $content .= $style ? "<span style=\"$style\">$text</span>" : $text;
                                        }
                                    }
                                    $content .= '</div>';
                                }
                            }
                            $content .= '</td>';
                        }
                        $content .= '</tr>';
                    }
                    $content .= '</table>';
                } elseif ($element instanceof ListItem) {
                    $listStyle = method_exists($element, 'getStyle') && $element->getStyle() ? $element->getStyle() : 'bullet';
                    $level = method_exists($element, 'getDepth') ? $element->getDepth() : 0;
                    $indent = $level * 20;
                    $content .= "<ul style=\"margin-left: {$indent}px;\">";
                    $text = htmlspecialchars($element->getText(), ENT_QUOTES, 'UTF-8');
                    $fontStyle = method_exists($element, 'getFontStyle') ? $element->getFontStyle() : null;
                    $style = '';
                    if ($fontStyle) {
                        $fontSize = method_exists($fontStyle, 'getSize') && $fontStyle->getSize() ? ($fontStyle->getSize() / 2) : 11;
                        $style .= "font-size: {$fontSize}px; color: #000;";
                        if ($fontStyle->isBold()) {
                            $style .= 'font-weight: bold;';
                        }
                        if ($fontStyle->isItalic()) {
                            $style .= 'font-style: italic;';
                        }
                        if (method_exists($fontStyle, 'getName') && $fontStyle->getName()) {
                            $style .= "font-family: '{$fontStyle->getName()}';";
                        }
                    } else {
                        $style .= 'font-size: 11px; color: #000;';
                    }
                    $content .= "<li style=\"$style\">$text</li>";
                    $content .= '</ul>';
                } elseif (method_exists($element, 'getText') && method_exists($element, 'getParagraphStyle')) {
                    $paraStyle = $element->getParagraphStyle();
                    $alignment = $this->getAlignment($paraStyle);
                    $content .= "<p style=\"text-align: $alignment; margin-bottom: 16px;\">";
                    $text = htmlspecialchars($element->getText(), ENT_QUOTES, 'UTF-8');
                    $fontStyle = method_exists($element, 'getFontStyle') ? $element->getFontStyle() : null;
                    $style = '';
                    if ($fontStyle) {
                        $fontSize = method_exists($fontStyle, 'getSize') && $fontStyle->getSize() ? ($fontStyle->getSize() / 2) : 11;
                        $style .= "font-size: {$fontSize}px; color: #000;";
                        if ($fontStyle->isBold()) {
                            $style .= 'font-weight: bold;';
                        }
                        if ($fontStyle->isItalic()) {
                            $style .= 'font-style: italic;';
                        }
                        if (method_exists($fontStyle, 'getName') && $fontStyle->getName()) {
                            $style .= "font-family: '{$fontStyle->getName()}';";
                        }
                    } else {
                        $style .= 'font-size: 11px; color: #000;';
                    }
                    $content .= $style ? "<span style=\"$style\">$text</span>" : $text;
                    $content .= '</p>';
                }
                $content .= '<br>';
            }
        }
        return $content;
    }

    /**
     * Chuyển đổi tiếng Việt có dấu thành không dấu.
     */
    private function convertVietnameseToNonAccent($string)
    {
        return ASCII::to_ascii($string, 'vi');
    }

    /**
     * Lấy giá trị căn lề từ Paragraph Style.
     */
    private function getAlignment($paraStyle)
    {
        if ($paraStyle instanceof Paragraph) {
            $alignment = $paraStyle->getAlignment();
            switch ($alignment) {
                case 'center':
                    return 'center';
                case 'right':
                    return 'right';
                case 'justify':
                case 'both':
                    return 'justify';
                default:
                    return 'left';
            }
        }
        return 'left';
    }
}