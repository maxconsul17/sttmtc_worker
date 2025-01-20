<?php
require 'autoload.php'; // Load Composer's autoloader

use Clegginabox\PDFMerger\PDFMerger;

// Initialize PDFMerger
$pdf = new PDFMerger;

// Add PDF files to merge
$pdf->addPDF('one.pdf', 'all'); // Replace with actual file paths
$pdf->addPDF('two.pdf', 'all');

// Merge and output
$mergedFile = 'merged_output.pdf'; // Output file name
$pdf->merge('file', $mergedFile);

echo "PDFs have been merged into $mergedFile.";
?>
