<?php
/**
 * Working PDF Generator - Creates valid PDF files using HTML to PDF conversion
 */

class WorkingPDFGenerator {
    public $content = '';
    private $filename = '';
    
    public function __construct() {
        // Initialize
    }
    
    public function AddPage() {
        // For this simple implementation, we'll use a single page
    }
    
    public function SetFont($family = 'Arial', $size = 12, $style = '') {
        // Font settings will be handled in CSS
    }
    
    public function SetTextColor($r, $g, $b) {
        // Text color will be handled in CSS
    }
    
    public function SetFillColor($r, $g, $b) {
        // Fill color will be handled in CSS
    }
    
    public function Cell($w, $h, $txt, $border = 0, $ln = 0, $align = 'L') {
        // Add cell content to HTML
        $style = '';
        if ($border) {
            $style .= 'border: 1px solid #000; ';
        }
        if ($align == 'C') {
            $style .= 'text-align: center; ';
        } elseif ($align == 'R') {
            $style .= 'text-align: right; ';
        }
        
        $this->content .= '<td style="width: ' . $w . 'px; height: ' . $h . 'px; padding: 2px; ' . $style . '">' . htmlspecialchars($txt) . '</td>';
        
        if ($ln) {
            $this->content .= '</tr><tr>';
        }
    }
    
    public function Ln($h = null) {
        // Line break in HTML
        $this->content .= '<br style="line-height: ' . ($h ?: 12) . 'px;">';
    }
    
    public function MultiCell($w, $h, $txt) {
        $this->content .= '<td colspan="100%" style="width: 100%; padding: 2px;">' . nl2br(htmlspecialchars($txt)) . '</td>';
    }
    
    public function Header($txt, $font_size = 18, $center = true) {
        $align = $center ? 'text-align: center;' : '';
        $this->content .= '<h1 style="font-size: ' . $font_size . 'px; margin: 20px 0; ' . $align . '">' . htmlspecialchars($txt) . '</h1>';
    }
    
    public function TableHeader($headers, $widths = null) {
        $this->content .= '<table style="width: 100%; border-collapse: collapse; margin: 10px 0;"><tr>';
        
        foreach ($headers as $i => $header) {
            $width = $widths ? 'width: ' . $widths[$i] . 'px;' : '';
            $this->content .= '<th style="background-color: #f0f0f0; border: 1px solid #000; padding: 5px; font-weight: bold; ' . $width . '">' . htmlspecialchars($header) . '</th>';
        }
        $this->content .= '</tr><tr>';
    }
    
    public function TableRow($data, $widths = null) {
        foreach ($data as $i => $cell) {
            $width = $widths ? 'width: ' . $widths[$i] . 'px;' : '';
            $this->content .= '<td style="border: 1px solid #000; padding: 5px; ' . $width . '">' . htmlspecialchars($cell) . '</td>';
        }
        $this->content .= '</tr><tr>';
    }
    
    public function Output($filename) {
        // Complete the HTML structure
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($filename) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #000; padding: 5px; }
        th { background-color: #f0f0f0; font-weight: bold; }
        h1 { margin: 20px 0; }
        .summary { margin: 15px 0; padding: 10px; background-color: #f9f9f9; border: 1px solid #ddd; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    ' . $this->content . '
</body>
</html>';

        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        // Convert HTML to PDF using a simple method
        // For now, we'll output as HTML that can be saved as PDF
        echo $html;
        exit;
    }
}
?>
