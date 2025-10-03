<?php

/**
 * Enhanced PDF Generator for IoT Farm Monitoring System
 * 
 * A professional PDF generation class that creates well-formatted PDF files
 * with proper styling, layout, and presentation
 */

class SimplePDF
{
    private $content = [];
    private $title = '';
    private $subtitle = '';
    private $author = 'IoT Farm Monitoring System';
    private $currentY = 750;
    private $pageWidth = 612;
    private $pageHeight = 792;
    private $margin = 30;

    public function __construct($title = 'Report', $subtitle = '')
    {
        $this->title = $title;
        $this->subtitle = $subtitle;
    }

    /**
     * Add structured content to the PDF
     */
    public function addContent($content)
    {
        $this->content[] = $content;
    }

    /**
     * Add a section header
     */
    public function addHeader($text, $level = 1)
    {
        $this->content[] = [
            'type' => 'header',
            'text' => $text,
            'level' => $level
        ];
    }

    /**
     * Add a data table
     */
    public function addTable($headers, $rows)
    {
        $this->content[] = [
            'type' => 'table',
            'headers' => $headers,
            'rows' => $rows
        ];
    }

    /**
     * Add regular text
     */
    public function addText($text)
    {
        $this->content[] = [
            'type' => 'text',
            'text' => $text
        ];
    }

    /**
     * Add summary statistics
     */
    public function addSummary($stats)
    {
        $this->content[] = [
            'type' => 'summary',
            'stats' => $stats
        ];
    }

    /**
     * Generate and output PDF
     */
    public function output($filename = 'report.pdf')
    {
        // Set PDF headers
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        // Generate enhanced PDF structure
        $pdf = $this->generateEnhancedPDF();
        echo $pdf;
    }

    /**
     * Generate enhanced PDF with professional formatting
     */
    private function generateEnhancedPDF()
    {
        $date = date('F j, Y \a\t g:i A');

        // Start PDF structure
        $pdf = "%PDF-1.4\n";

        // Catalog object
        $pdf .= "1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n\n";

        // Pages object
        $pdf .= "2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n\n";

        // Page object
        $pdf .= "3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 {$this->pageWidth} {$this->pageHeight}]\n/Contents 4 0 R\n/Resources <<\n/Font <<\n/F1 5 0 R\n/F2 6 0 R\n/F3 7 0 R\n>>\n>>\n>>\nendobj\n\n";

        // Generate content stream
        $streamContent = $this->generateContentStream($date);

        // Content stream object
        $pdf .= "4 0 obj\n<<\n/Length " . strlen($streamContent) . "\n>>\nstream\n" . $streamContent . "endstream\nendobj\n\n";

        // Font objects
        $pdf .= "5 0 obj\n<<\n/Type /Font\n/Subtype /Type1\n/BaseFont /Helvetica\n>>\nendobj\n\n";
        $pdf .= "6 0 obj\n<<\n/Type /Font\n/Subtype /Type1\n/BaseFont /Helvetica-Bold\n>>\nendobj\n\n";
        $pdf .= "7 0 obj\n<<\n/Type /Font\n/Subtype /Type1\n/BaseFont /Helvetica-Oblique\n>>\nendobj\n\n";

        // Cross-reference table
        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 8\n";
        $pdf .= "0000000000 65535 f \n";
        $pdf .= "0000000009 00000 n \n";
        $pdf .= "0000000058 00000 n \n";
        $pdf .= "0000000115 00000 n \n";
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "4 0 obj"));
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "5 0 obj"));
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "6 0 obj"));
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "7 0 obj"));

        // Trailer
        $pdf .= "trailer\n<<\n/Size 8\n/Root 1 0 R\n>>\nstartxref\n{$xrefPos}\n%%EOF\n";

        return $pdf;
    }

    /**
     * Generate professional content stream
     */
    private function generateContentStream($date)
    {
        $stream = "";
        $this->currentY = $this->pageHeight - $this->margin;

        // Header section with company branding
        $stream .= $this->drawHeader();

        // Title and subtitle
        $stream .= $this->drawTitle();

        // Date and metadata
        $stream .= $this->drawMetadata($date);

        // Content sections
        foreach ($this->content as $item) {
            if (is_string($item)) {
                $stream .= $this->drawText($item);
            } elseif (is_array($item)) {
                switch ($item['type']) {
                    case 'header':
                        $stream .= $this->drawSectionHeader($item['text'], $item['level']);
                        break;
                    case 'table':
                        $stream .= $this->drawTable($item['headers'], $item['rows']);
                        break;
                    case 'text':
                        $stream .= $this->drawText($item['text']);
                        break;
                    case 'summary':
                        $stream .= $this->drawSummary($item['stats']);
                        break;
                }
            }
        }

        // Footer
        $stream .= $this->drawFooter();

        return $stream;
    }

    /**
     * Draw professional header with branding
     */
    private function drawHeader()
    {
        $stream = "q\n"; // Save graphics state

        // Header background
        $stream .= "0.95 0.95 0.95 rg\n"; // Light gray background
        $stream .= "{$this->margin} " . ($this->pageHeight - 80) . " " . ($this->pageWidth - 2 * $this->margin) . " 60 re f\n";

        // Company logo area (placeholder)
        $stream .= "0.2 0.4 0.8 rg\n"; // Blue color
        $stream .= ($this->margin + 10) . " " . ($this->pageHeight - 70) . " 40 40 re f\n";

        // Company name
        $stream .= "BT\n";
        $stream .= "/F2 12 Tf\n"; // Bold font
        $stream .= "0 0 0 rg\n"; // Black text
        $stream .= ($this->margin + 60) . " " . ($this->pageHeight - 45) . " Td\n";
        $stream .= "(IoT Farm Monitoring System) Tj\n";
        $stream .= "ET\n";

        $stream .= "Q\n"; // Restore graphics state

        $this->currentY -= 100;
        return $stream;
    }

    /**
     * Draw title section
     */
    private function drawTitle()
    {
        $stream = "BT\n";
        $stream .= "/F2 14 Tf\n"; // Bold, larger font
        $stream .= "0.1 0.1 0.1 rg\n"; // Dark gray
        $stream .= $this->margin . " {$this->currentY} Td\n";
        $stream .= "(" . $this->escapeText($this->title) . ") Tj\n";
        $stream .= "ET\n";

        $this->currentY -= 20;

        if ($this->subtitle) {
            $stream .= "BT\n";
            $stream .= "/F3 10 Tf\n"; // Italic font
            $stream .= "0.3 0.3 0.3 rg\n"; // Medium gray
            $stream .= $this->margin . " {$this->currentY} Td\n";
            $stream .= "(" . $this->escapeText($this->subtitle) . ") Tj\n";
            $stream .= "ET\n";

            $this->currentY -= 15;
        }

        // Underline
        $stream .= "q\n";
        $stream .= "0.2 0.4 0.8 RG\n"; // Blue line
        $stream .= "2 w\n"; // Line width
        $stream .= $this->margin . " {$this->currentY} m\n";
        $stream .= ($this->pageWidth - $this->margin) . " {$this->currentY} l S\n";
        $stream .= "Q\n";

        $this->currentY -= 20;
        return $stream;
    }

    /**
     * Draw metadata section
     */
    private function drawMetadata($date)
    {
        $stream = "BT\n";
        $stream .= "/F1 10 Tf\n";
        $stream .= "0.5 0.5 0.5 rg\n"; // Gray text
        $stream .= $this->margin . " {$this->currentY} Td\n";
        $stream .= "(Generated: {$date}) Tj\n";
        $stream .= "ET\n";

        $this->currentY -= 20;
        return $stream;
    }

    /**
     * Draw section header
     */
    private function drawSectionHeader($text, $level = 1)
    {
        $fontSize = $level == 1 ? 12 : 10;
        $font = $level == 1 ? "/F2" : "/F2"; // Bold for both levels

        $this->currentY -= 10; // Extra space before header

        $stream = "BT\n";
        $stream .= "{$font} {$fontSize} Tf\n";
        $stream .= "0.1 0.1 0.1 rg\n";
        $stream .= $this->margin . " {$this->currentY} Td\n";
        $stream .= "(" . $this->escapeText($text) . ") Tj\n";
        $stream .= "ET\n";

        $this->currentY -= 15;
        return $stream;
    }

    /**
     * Draw regular text
     */
    private function drawText($text)
    {
        $lines = $this->wrapText($text, 85);
        $stream = "";

        foreach ($lines as $line) {
            $stream .= "BT\n";
            $stream .= "/F1 9 Tf\n";
            $stream .= "0.2 0.2 0.2 rg\n";
            $stream .= $this->margin . " {$this->currentY} Td\n";
            $stream .= "(" . $this->escapeText($line) . ") Tj\n";
            $stream .= "ET\n";

            $this->currentY -= 12;
        }

        $this->currentY -= 3; // Extra space after text
        return $stream;
    }

    /**
     * Draw summary statistics box
     */
    private function drawSummary($stats)
    {
        $stream = "q\n";

        // Background box
        $stream .= "0.95 0.98 1.0 rg\n"; // Light blue background
        $boxHeight = count($stats) * 12 + 15;
        $stream .= $this->margin . " " . ($this->currentY - $boxHeight) . " " . ($this->pageWidth - 2 * $this->margin) . " {$boxHeight} re f\n";

        // Border
        $stream .= "0.7 0.8 0.9 RG\n"; // Blue border
        $stream .= "1 w\n";
        $stream .= $this->margin . " " . ($this->currentY - $boxHeight) . " " . ($this->pageWidth - 2 * $this->margin) . " {$boxHeight} re S\n";

        $stream .= "Q\n";

        // Summary title
        $this->currentY -= 12;
        $stream .= "BT\n";
        $stream .= "/F2 12 Tf\n";
        $stream .= "0.1 0.1 0.1 rg\n";
        $stream .= ($this->margin + 10) . " {$this->currentY} Td\n";
        $stream .= "(Summary Statistics) Tj\n";
        $stream .= "ET\n";

        // Statistics
        foreach ($stats as $label => $value) {
            $this->currentY -= 12;
            $stream .= "BT\n";
            $stream .= "/F1 10 Tf\n";
            $stream .= "0.3 0.3 0.3 rg\n";
            $stream .= ($this->margin + 20) . " {$this->currentY} Td\n";
            $stream .= "(" . $this->escapeText($label . ": " . $value) . ") Tj\n";
            $stream .= "ET\n";
        }

        $this->currentY -= 15;
        return $stream;
    }

    /**
     * Draw data table
     */
    private function drawTable($headers, $rows)
    {
        $colWidth = ($this->pageWidth - 2 * $this->margin) / count($headers);
        $rowHeight = 16;

        $stream = "q\n";

        // Table header background
        $stream .= "0.9 0.9 0.9 rg\n";
        $stream .= $this->margin . " " . ($this->currentY - $rowHeight) . " " . ($this->pageWidth - 2 * $this->margin) . " {$rowHeight} re f\n";

        // Header text
        $stream .= "BT\n";
        $stream .= "/F2 8 Tf\n";
        $stream .= "0 0 0 rg\n";

        foreach ($headers as $i => $header) {
            $x = $this->margin + ($i * $colWidth) + 5;
            $stream .= "{$x} " . ($this->currentY - 12) . " Td\n";
            $stream .= "(" . $this->escapeText(substr($header, 0, 12)) . ") Tj\n";
            if ($i < count($headers) - 1) {
                $stream .= (-$x + $this->margin + (($i + 1) * $colWidth) + 5) . " 0 Td\n";
            }
        }
        $stream .= "ET\n";

        $this->currentY -= $rowHeight;

        // Table rows
        foreach ($rows as $rowIndex => $row) {
            // Alternate row colors
            if ($rowIndex % 2 == 0) {
                $stream .= "0.98 0.98 0.98 rg\n";
                $stream .= $this->margin . " " . ($this->currentY - $rowHeight) . " " . ($this->pageWidth - 2 * $this->margin) . " {$rowHeight} re f\n";
            }

            $stream .= "BT\n";
            $stream .= "/F1 9 Tf\n";
            $stream .= "0.2 0.2 0.2 rg\n";

            foreach ($row as $i => $cell) {
                $x = $this->margin + ($i * $colWidth) + 5;
                $stream .= "{$x} " . ($this->currentY - 10) . " Td\n";
                $stream .= "(" . $this->escapeText(substr($cell, 0, 15)) . ") Tj\n";
                if ($i < count($row) - 1) {
                    $stream .= (-$x + $this->margin + (($i + 1) * $colWidth) + 5) . " 0 Td\n";
                }
            }
            $stream .= "ET\n";

            $this->currentY -= $rowHeight;

            // Stop if we're running out of space
            if ($this->currentY < 100) break;
        }

        // Table border
        $stream .= "0.7 0.7 0.7 RG\n";
        $stream .= "0.5 w\n";
        $tableHeight = (count($rows) + 1) * $rowHeight;
        $stream .= $this->margin . " {$this->currentY} " . ($this->pageWidth - 2 * $this->margin) . " {$tableHeight} re S\n";

        $stream .= "Q\n";

        $this->currentY -= 20;
        return $stream;
    }

    /**
     * Draw footer
     */
    private function drawFooter()
    {
        $stream = "BT\n";
        $stream .= "/F3 8 Tf\n";
        $stream .= "0.6 0.6 0.6 rg\n";
        $stream .= $this->margin . " 30 Td\n";
        $stream .= "(This report was generated by the IoT Farm Monitoring System) Tj\n";
        $stream .= "0 -12 Td\n";
        $stream .= "(For support and questions, contact: admin@farmmonitoring.com) Tj\n";
        $stream .= "ET\n";

        return $stream;
    }

    /**
     * Escape text for PDF
     */
    private function escapeText($text)
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    /**
     * Wrap text to specified width
     */
    private function wrapText($text, $width)
    {
        return explode("\n", wordwrap($text, $width, "\n", true));
    }
}
