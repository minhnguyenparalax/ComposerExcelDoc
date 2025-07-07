<?php

namespace App\Http\Controllers;

use App\Models\Docfile;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Style\Paragraph;
use Illuminate\Support\Facades\Log;

class DocController extends Controller
{
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

            $content = $this->extractWordContent($phpWord); // Trích xuất nội dung

            $docfile = Docfile::create([
                'name' => $fileName,
                'path' => $filePath,
                'content' => $content, // Lưu nội dung vào cột content
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
     * Trích xuất nội dung từ file Word.
     */
    protected function extractWordContent($phpWord)
    {
        $content = '';
        foreach ($phpWord->getSections() as $section) {
            // 👉 Đọc header
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
                if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                    $paraStyle = method_exists($element, 'getParagraphStyle') ? $element->getParagraphStyle() : null;
                    $alignment = $this->getAlignment($paraStyle);
                    $content .= "<p style=\"text-align: $alignment; margin-bottom: 16px;\">";
                    foreach ($element->getElements() as $textElement) {
                        if (method_exists($textElement, 'getText')) {
                            $text = htmlspecialchars($textElement->getText(), ENT_QUOTES, 'UTF-8');
                            $fontStyle = method_exists($textElement, 'getFontStyle') ? $textElement->getFontStyle() : null;
                            $style = '';
                            if ($fontStyle) {
                                // Lấy kích thước phông chữ (half-points, chia 2 để ra points gần đúng với px)
                                $fontSize = method_exists($fontStyle, 'getSize') && $fontStyle->getSize() ? ($fontStyle->getSize() * 0.75) : 11;;
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
                } elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                    $content .= '<table class="table table-bordered" style="min-width: 600px; border-collapse: collapse;">';
                    foreach ($element->getRows() as $row) {
                        $content .= '<tr>';
                        foreach ($row->getCells() as $cell) {
                            $content .= '<td style="border: 1px solid #dee2e6; padding: 12px;">';
                            foreach ($cell->getElements() as $cellElement) {
                                if ($cellElement instanceof \PhpOffice\PhpWord\Element\TextRun) {
                                    $paraStyle = method_exists($cellElement, 'getParagraphStyle') ? $cellElement->getParagraphStyle() : null;
                                    $alignment = $this->getAlignment($paraStyle);
                                    $content .= "<div style=\"text-align: $alignment;\">";
                                    foreach ($cellElement->getElements() as $textElement) {
                                        if (method_exists($textElement, 'getText')) {
                                            $text = htmlspecialchars($textElement->getText(), ENT_QUOTES, 'UTF-8');
                                            $fontStyle = method_exists($textElement, 'getFontStyle') ? $textElement->getFontStyle() : null;
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
                } elseif ($element instanceof \PhpOffice\PhpWord\Element\ListItem) {
                    // Thay getListStyle() bằng getStyle() hoặc kiểm tra kiểu danh sách
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
    }
    return $content;
    }

    /**
     * Xóa file Doc.
     */
    public function removeDoc(Request $request)
    {
        $request->validate([
            'doc_id' => 'required|integer|exists:docfile,id',
        ]);

        $doc = Docfile::findOrFail($request->doc_id);
        $filePath = $doc->path;
        $doc->delete();

        return redirect()->route('file.index')->with('success', 'Đã xóa file Word: ' . $filePath);
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

        $content = $doc->content ?: 'Nội dung không có sẵn'; // Sử dụng nội dung từ cột content

        return view('doc_data', [
            'content' => $content,
            'fileName' => $doc->name,
            'success' => 'Đã đọc file Word "' . $doc->name . '" thành công.'
        ]);
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