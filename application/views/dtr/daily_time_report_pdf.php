<?php
    set_time_limit(0);
    ini_set('memory_limit', -1);
    ini_set('max_execution_time', 0);
    ini_set("pcre.backtrack_limit", "500000000");

    // Initialize mPDF with settings
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'tempDir' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mpdf',
        'margin_top' => 5,
        'margin_bottom' => 5,
        'margin_right' => 5,
        'margin_left' => 5,
    ]);
    $mpdf->useSubstitutions = false;
    $mpdf->simpleTables = true;
    $mpdf->SetDisplayMode('fullpage');

    // Define styles and settings for the PDF
    $topMargin = "20px";
    $style = "
    <style>
        @page {
            margin-top: {$topMargin};
            odd-header-name: html_Header;
            odd-footer-name: html_Footer;
        }
        th {
            color: white;
        }
        #indvtbl td, #indvtblnt td {
            text-align: center;
        }
    </style>
    ";

    // Start buffering content to minimize memory usage
    ob_start();
    echo $style;

    $reportCount = count($report_list);

    foreach ($report_list as $key => $value) {
        // Add report content
        echo $value['report'];

        // Add a page break if not the last report
        if ($key < $reportCount - 1) {
            echo '<pagebreak>';
        }
    }

    // Collect all content and write to the PDF
    $content = ob_get_clean();
    $mpdf->WriteHTML($content);

    // Save the generated PDF
    $mpdf->Output($path, "F");

?>
