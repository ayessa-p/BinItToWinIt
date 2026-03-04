<?php
/**
 * PDF Generator - Creates proper binary PDF files for download
 * No external dependencies required
 */

class PDFGenerator {
    private $lines = array();
    private $current_y = 750;
    private $page_width = 612;
    private $page_height = 792;
    private $margin_left = 40;
    private $margin_right = 40;
    private $line_height = 12;
    
    public function __construct() {
        // Initialize
    }
    
    public function addPage() {
        // Page break
        $this->current_y = $this->page_height - 40;
    }
    
    public function text($text) {
        if ($this->current_y <= 40) {
            $this->addPage();
        }
        
        // Escape special PDF characters
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace('(', '\\(', $text);
        $text = str_replace(')', '\\)', $text);
        
        $this->lines[] = array(
            'y' => $this->current_y,
            'text' => $text,
            'size' => 10
        );
        
        $this->current_y -= $this->line_height;
    }
    
    public function setFont($family = 'Helvetica', $size = 12, $style = '') {
        // Font size noted for future use
    }
    
    public function ln($height = null) {
        $this->current_y -= ($height ?? $this->line_height);
    }
    
    public function addTitle($title) {
        $this->current_y -= 6;
        $this->lines[] = array(
            'y' => $this->current_y,
            'text' => $title,
            'size' => 16,
            'bold' => true
        );
        $this->current_y -= 16;
        $this->ln(8);
    }
    
    public function addSection($title) {
        $this->ln(4);
        $this->lines[] = array(
            'y' => $this->current_y,
            'text' => $title,
            'size' => 12,
            'bold' => true
        );
        $this->current_y -= 12;
        $this->ln(2);
    }
    
    public function addLine($label, $value) {
        $line = str_pad($label . ': ', 30) . $value;
        $this->text($line);
    }
    
    public function addTable($headers, $rows, $col_widths = null) {
        if (empty($headers)) return;
        
        // Check page space
        $needed_space = (count($rows) + 2) * $this->line_height;
        if ($this->current_y - $needed_space <= 40) {
            $this->addPage();
        }
        
        // Headers
        $header_text = '';
        foreach ($headers as $header) {
            $header_text .= str_pad(substr($header, 0, 11), 12);
        }
        
        $this->lines[] = array(
            'y' => $this->current_y,
            'text' => $header_text,
            'size' => 9,
            'bold' => true
        );
        $this->current_y -= $this->line_height;
        
        // Separator
        $this->text(str_repeat('-', 75));
        
        // Data rows
        foreach ($rows as $row) {
            if ($this->current_y <= 50) {
                $this->addPage();
            }
            
            $row_text = '';
            foreach ($row as $cell) {
                $cell_str = isset($cell) ? (string)$cell : '';
                $row_text .= str_pad(substr($cell_str, 0, 11), 12);
            }
            $this->text($row_text);
        }
    }
    
    public function addSummary($summary_data) {
        if (empty($summary_data)) return;
        
        $this->addSection('SUMMARY');
        foreach ($summary_data as $label => $value) {
            $this->addLine($label, $value);
        }
    }
    
    public function output($filename) {
        // Generate actual PDF content
        $pdf_content = $this->generatePDF();
        
        // Output proper headers
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . strlen($pdf_content));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output binary PDF
        echo $pdf_content;
        exit;
    }
    
    private function generatePDF() {
        // Build PDF document
        $objects = array();
        
        // Create content stream from lines
        $content = $this->buildContentStream();
        
        // Object 1: Catalog
        $objects[1] = "1 0 obj\n<</Type/Catalog/Pages 2 0 R>>\nendobj\n";
        
        // Object 2: Pages
        $objects[2] = "2 0 obj\n<</Type/Pages/Kids[3 0 R]/Count 1>>\nendobj\n";
        
        // Object 3: Page
        $objects[3] = "3 0 obj\n<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Contents 4 0 R/Resources<</Font<</F1<</Type/Font/Subtype/Type1/BaseFont/Courier>>/F2<</Type/Font/Subtype/Type1/BaseFont/Courier-Bold>>>>>>\nendobj\n";
        
        // Object 4: Content stream
        $stream = $this->escapeContent($content);
        $objects[4] = "4 0 obj\n<</Length " . strlen($stream) . ">>\nstream\n" . $stream . "\nendstream\nendobj\n";
        
        // Build PDF
        $pdf = "%PDF-1.4\n%âãÏÓ\n";
        
        // Track offsets
        $offsets = array();
        $offsets[0] = strlen($pdf);
        
        // Add objects
        foreach ($objects as $obj_num => $obj_content) {
            $offsets[$obj_num] = strlen($pdf);
            $pdf .= $obj_content;
        }
        
        // Cross-reference table
        $xref_offset = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        
        // Trailer
        $pdf .= "trailer\n";
        $pdf .= "<</Size " . (count($objects) + 1) . "/Root 1 0 R>>\n";
        $pdf .= "startxref\n";
        $pdf .= $xref_offset . "\n";
        $pdf .= "%%EOF";
        
        return $pdf;
    }
    
    private function buildContentStream() {
        $stream = "BT\n";
        $stream .= "/F1 10 Tf\n";
        $stream .= "40 750 Td\n";  // Start position
        
        foreach ($this->lines as $line) {
            $text = $line['text'];
            
            $stream .= "(" . $text . ") Tj\n";
            $stream .= "0 -12 Td\n";  // Move down for next line
        }
        
        $stream .= "ET\n";
        
        return $stream;
    }
    
    private function escapeContent($content) {
        // Escape special characters in PDF stream
        return $content;
    }
}
?>
