<?php
/**
 * Simple PDF Generator for Reports
 * Creates basic PDF files without external dependencies
 */

class SimplePDFGenerator {
    private $content = '';
    private $x = 50;
    private $y = 50;
    private $width = 545; // A4 width in points (210mm)
    private $height = 792; // A4 height in points (297mm)
    private $page_count = 1;
    private $objects = [];
    private $current_object = 1;
    
    public function __construct() {
        $this->content = "%PDF-1.4\n";
        $this->objects[1] = '1 0 obj << /Type /Catalog /Pages 2 0 R >>\nendobj\n';
        $this->objects[2] = '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n';
        $this->objects[3] = '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R >>\nendobj\n';
        $this->current_object = 4;
    }
    
    public function AddPage() {
        $this->objects[$this->current_object] = $this->current_object . ' 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents ' . ($this->current_object + 1) . ' 0 R >>\nendobj\n';
        $this->current_object++;
        $this->page_count++;
        $this->x = 50;
        $this->y = 792 - 50; // Reset y position for new page
    }
    
    public function SetFont($family = 'Arial', $size = 12, $style = '') {
        $this->current_font = [
            'family' => $family,
            'size' => $size,
            'style' => $style
        ];
    }
    
    public function SetTextColor($r, $g, $b) {
        $this->text_color = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
    }
    
    public function SetFillColor($r, $g, $b) {
        $this->fill_color = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
    }
    
    public function Cell($w, $h, $txt, $border = 0, $ln = 0, $align = 'L') {
        // Calculate text width (approximate)
        $text_width = strlen($txt) * $this->current_font['size'] * 0.6;
        
        if ($this->x + $text_width > $this->width - 50) {
            $this->Ln();
        }
        
        // Add text to content stream
        $txt = str_replace(['\\', '(', ')], ['\\\\', '\\(', '\\ '], $txt);
        $this->content .= "BT\n/F1 " . $this->current_font['size'] . " Tf\n";
        $this->content .= $this->x . " " . $this->y . " Td\n";
        $this->content .= "(" . $txt . ") Tj\n";
        
        $this->x += $w;
        
        if ($ln) {
            $this->Ln();
        }
    }
    
    public function Ln($h = null) {
        $this->y -= ($h ?: $this->current_font['size'] + 2);
    }
    
    public function MultiCell($w, $h, $txt) {
        $lines = explode("\n", wordwrap($txt, floor($w / ($this->current_font['size'] * 6)));
        foreach ($lines as $line) {
            $this->Cell($w, $h, $line, 0, 1);
        }
    
    public function Header($txt, $font_size = 18, $center = true) {
        $this->SetFont('Arial', 'B');
        $this->SetTextColor(33, 33, 33);
        
        if ($center) {
            $text_width = strlen($txt) * $font_size * 0.6;
            $x = ($this->width - $text_width) / 2;
            $this->x = max(50, $x);
        }
        
        $this->Cell(0, 10, $txt, 0, 1);
        $this->Ln(15);
    }
    
    public function TableHeader($headers, $widths = null) {
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(240, 240, 240);
        
        if (!$widths) {
            $widths = array_fill(0, count($headers), 50);
        }
        
        foreach ($headers as $i => $header) {
            $this->Cell($widths[$i], 8, $header, 1, 0, 'C');
        }
        $this->Ln();
    }
    
    public function TableRow($data, $widths = null) {
        $this->SetFont('Arial', '', 10);
        
        if (!$widths) {
            $widths = array_fill(0, count($data), 50);
        }
        
        foreach ($data as $i => $cell) {
            $this->Cell($widths[$i], 8, $cell, 1, 0, 'L');
        }
        $this->Ln();
    }
    
    public function Output($filename) {
        // Add catalog
        $this->objects[1] = '1 0 obj << /Type /Catalog /Pages 2 0 R >>\nendobj\n';
        $this->objects[2] = '2 0 obj << /Type /Pages /Kids [' . implode(' ', array_slice($this->objects, 3, $this->current_object - 1)) . '] 0 R >>\nendobj\n';
        
        // Add pages
        $this->objects[2] = '2 0 obj << /Type /Pages /Kids [' . implode(' ', array_slice($this->objects, 3, $this->current_object - 1)) . '] 0 R >>\nendobj\n';
        
        // Add xref table
        $xref = "xref\n0 {$this->current_object} {$this->current_object}\n";
        
        foreach ($this->objects as $obj_num => $obj_data) {
            $xref .= sprintf("%010d 00000 n \n", $obj_num * 100);
        }
        
        $xref .= "trailer\n<<\n/Size {$this->current_object}\n>>\nstartxref\n" . (strlen($this->content) + strlen($xref)) . "\n%%EOF";
        
        $this->content .= $xref;
        
        // Set headers
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        echo $this->content;
        exit;
    }
}
?>
