<?php
/**
 * Simple HTML PDF Generator - Creates properly formatted PDF reports
 */

class SimpleHTMLPDF {
    private $title = '';
    private $period = '';
    private $generated_on = '';
    private $summary_data = array();
    private $headers = array();
    private $table_data = array();
    
    public function __construct() {
        // Initialize
    }
    
    public function Header($txt, $font_size = 18, $center = true) {
        $this->title = $txt;
    }
    
    public function AddInfo($period, $generated_on) {
        $this->period = $period;
        $this->generated_on = $generated_on;
    }
    
    public function AddSummary($summary_data) {
        $this->summary_data = $summary_data;
    }
    
    public function AddTable($headers, $data, $widths = null) {
        $this->headers = $headers;
        $this->table_data = $data;
    }
    
    public function AddFooter() {
        // Footer will be added during output
    }
    
    public function Output($filename) {
        // Ensure filename has .pdf extension
        if (!preg_match('/\.pdf$/i', $filename)) {
            $filename .= '.pdf';
        }
        
        // Generate PDF
        $pdf_content = $this->generatePdf();
        
        // Set proper headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        header('Content-Length: ' . strlen($pdf_content));
        
        echo $pdf_content;
        exit;
    }
    
    private function generatePdf() {
        // Create HTML representation first for PDF conversion
        $html = $this->generateHtml();
        
        // Convert to PDF
        return $this->htmlToPdfFormat($html);
    }
    
    private function generateHtml() {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($this->title) . '</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #007cba;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #007cba;
        }
        .info {
            margin: 20px 0;
            padding: 12px;
            background-color: #e8f4f8;
            border-left: 4px solid #007cba;
            font-size: 12px;
        }
        .info p {
            margin: 5px 0;
        }
        .summary {
            margin: 20px 0;
            padding: 15px;
            background-color: #f0f8ff;
            border: 2px solid #007cba;
        }
        .summary h2 {
            margin-top: 0;
            color: #007cba;
            font-size: 16px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .summary-item {
            padding: 8px;
            background-color: white;
            border: 1px solid #ddd;
        }
        .summary-label {
            font-weight: bold;
            color: #007cba;
        }
        .summary-value {
            font-size: 14px;
            color: #333;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0;
        }
        th, td { 
            border: 1px solid #999; 
            padding: 10px; 
            text-align: left;
            font-size: 12px;
        }
        th { 
            background-color: #007cba; 
            color: white; 
            font-weight: bold;
        }
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>';
        
        // Add header
        $html .= '<div class="header"><h1>' . htmlspecialchars($this->title) . '</h1></div>';
        
        // Add info
        if ($this->period || $this->generated_on) {
            $html .= '<div class="info">';
            if ($this->period) {
                $html .= '<p><strong>Period:</strong> ' . htmlspecialchars($this->period) . '</p>';
            }
            if ($this->generated_on) {
                $html .= '<p><strong>Generated on:</strong> ' . htmlspecialchars($this->generated_on) . '</p>';
            }
            $html .= '</div>';
        }
        
        // Add summary
        if (!empty($this->summary_data)) {
            $html .= '<div class="summary">';
            $html .= '<h2>Summary</h2>';
            $html .= '<div class="summary-grid">';
            foreach ($this->summary_data as $label => $value) {
                $html .= '<div class="summary-item">';
                $html .= '<div class="summary-label">' . htmlspecialchars($label) . '</div>';
                $html .= '<div class="summary-value">' . htmlspecialchars($value) . '</div>';
                $html .= '</div>';
            }
            $html .= '</div></div>';
        }
        
        // Add table
        if (!empty($this->headers) && !empty($this->table_data)) {
            $html .= '<table>';
            
            // Add headers
            $html .= '<thead><tr>';
            foreach ($this->headers as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $html .= '</tr></thead>';
            
            // Add data rows
            $html .= '<tbody>';
            foreach ($this->table_data as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }
        
        // Add footer
        $html .= '<div class="footer">';
        $html .= '<p>Generated by MTICS Admin System on ' . date('F j, Y g:i A') . '</p>';
        $html .= '</div>';
        
        $html .= '</body></html>';
        
        return $html;
    }
    
    private function htmlToPdfFormat($html) {
        // Try to use TCPDF if available
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            return $this->tryUseTcpdf($html);
        }
        
        // Fallback to custom PDF generation
        return $this->createPdfFromHtml($html);
    }
    
    private function tryUseTcpdf($html) {
        try {
            // Try to include TCPDF if available via composer
            if (file_exists(__DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php')) {
                require_once(__DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php');
                
                $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                $pdf->SetMargins(15, 15, 15);
                $pdf->AddPage();
                $pdf->SetFont('helvetica', '', 10);
                $pdf->writeHTML($html, true, false, true, false, '');
                
                return $pdf->Output('', 'S');
            }
        } catch (Exception $e) {
            // Fall through to custom PDF
        }
        
        return false;
    }
    
    private function createPdfFromHtml($html) {
        // Try wkhtmltopdf first if available
        $pdf_result = $this->tryUseWkhtmltopdf($html);
        if ($pdf_result !== false) {
            return $pdf_result;
        }
        
        // Fallback to basic PDF generation
        return $this->createBasicPdf($html);
    }
    
    private function tryUseWkhtmltopdf($html) {
        // Check if wkhtmltopdf is available
        $output = null;
        $return_var = null;
        
        // Test if wkhtmltopdf exists
        @exec('which wkhtmltopdf', $output, $return_var);
        
        if ($return_var === 0) {
            // Save HTML to temp file
            $temp_html = sys_get_temp_dir() . '/report_' . time() . '.html';
            $temp_pdf = sys_get_temp_dir() . '/report_' . time() . '.pdf';
            
            file_put_contents($temp_html, $html);
            
            // Convert HTML to PDF using wkhtmltopdf
            $cmd = 'wkhtmltopdf --quiet "' . escapeshellarg($temp_html) . '" "' . escapeshellarg($temp_pdf) . '"';
            @exec($cmd, $output, $return_var);
            
            if ($return_var === 0 && file_exists($temp_pdf)) {
                $pdf_content = file_get_contents($temp_pdf);
                @unlink($temp_html);
                @unlink($temp_pdf);
                return $pdf_content;
            } else {
                @unlink($temp_html);
                @unlink($temp_pdf);
            }
        }
        
        return false;
    }
    
    private function createBasicPdf($html) {
        // Create formatted PDF from HTML
        // Extract title, summary, and table data
        
        $title = $this->title;
        $period = $this->period;
        $generated_on = $this->generated_on;
        
        // Generate PDF using simple PDF format
        $pdf = "%PDF-1.4\n";
        $objects = array();
        
        // Build text content
        $pdf_text = '';
        
        // Add title
        $pdf_text .= strtoupper($title) . "\n";
        $pdf_text .= str_repeat("=", 80) . "\n\n";
        
        // Add info
        if ($period) {
            $pdf_text .= "Period: " . $period . "\n";
        }
        if ($generated_on) {
            $pdf_text .= "Generated on: " . $generated_on . "\n";
        }
        $pdf_text .= "\n";
        
        // Add summary
        if (!empty($this->summary_data)) {
            $pdf_text .= "SUMMARY\n";
            $pdf_text .= str_repeat("-", 80) . "\n";
            foreach ($this->summary_data as $label => $value) {
                $pdf_text .= str_pad($label . ": ", 40) . $value . "\n";
            }
            $pdf_text .= "\n";
        }
        
        // Add table
        if (!empty($this->headers) && !empty($this->table_data)) {
            $pdf_text .= "REPORT DATA\n";
            $pdf_text .= str_repeat("-", 80) . "\n";
            
            // Calculate column widths
            $col_count = count($this->headers);
            $col_width = floor(80 / $col_count);
            
            // Header row
            foreach ($this->headers as $header) {
                $pdf_text .= str_pad(substr($header, 0, $col_width - 1), $col_width);
            }
            $pdf_text .= "\n";
            $pdf_text .= str_repeat("-", 80) . "\n";
            
            // Data rows
            foreach ($this->table_data as $row) {
                foreach ($row as $cell) {
                    $cell_str = isset($cell) ? $cell : '';
                    $pdf_text .= str_pad(substr($cell_str, 0, $col_width - 1), $col_width);
                }
                $pdf_text .= "\n";
            }
        }
        
        // Add footer
        $pdf_text .= "\n" . str_repeat("=", 80) . "\n";
        $pdf_text .= "Generated by MTICS Admin System on " . date('F j, Y g:i A') . "\n";
        
        // Create PDF object
        $pdf_content = $this->buildPdfDocument($pdf_text);
        
        return $pdf_content;
    }
    
    private function buildPdfDocument($text) {
        $pdf = "%PDF-1.4\n";
        $objects = array();
        
        // Clean text
        $text = str_replace(array('\r\n', '\r'), '\n', $text);
        
        // Create content stream
        $content_stream = "BT\n";
        $content_stream .= "/F1 10 Tf\n";
        $content_stream .= "50 750 Td\n";
        
        // Split into lines and add to PDF
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $line = str_replace(array('\\', '(', ')'), array('\\\\', '\\(', '\\)'), $line);
            if (!empty($line)) {
                $content_stream .= "(" . $line . ") Tj\n";
            }
            $content_stream .= "0 -12 Td\n";
        }
        
        $content_stream .= "ET\n";
        
        // PDF objects
        $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $objects[3] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Courier >> >> >> >>\nendobj\n";
        $objects[4] = "4 0 obj\n<< /Length " . strlen($content_stream) . " >>\nstream\n" . $content_stream . "endstream\nendobj\n";
        
        // Build PDF
        $pdf_content = $pdf;
        $offsets = array(0 => strlen($pdf_content));
        
        foreach ($objects as $i => $obj) {
            $offsets[$i] = strlen($pdf_content);
            $pdf_content .= $obj;
        }
        
        // Cross-reference table
        $xref_offset = strlen($pdf_content);
        $pdf_content .= "xref\n";
        $pdf_content .= "0 " . (count($objects) + 1) . "\n";
        $pdf_content .= "0000000000 65535 f \n";
        
        foreach ($offsets as $i => $offset) {
            if ($i > 0 && $i <= count($objects)) {
                $pdf_content .= sprintf("%010d 00000 n \n", $offset);
            }
        }
        
        // Trailer
        $pdf_content .= "trailer\n";
        $pdf_content .= "<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf_content .= "startxref\n";
        $pdf_content .= $xref_offset . "\n";
        $pdf_content .= "%%EOF";
        
        return $pdf_content;
    }
}
?>
