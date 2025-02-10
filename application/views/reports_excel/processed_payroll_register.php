<?php
    //NICOLE Q
    // $this->load->library('lib_includer');
    $this->lib_includer->load("excel/Writer");

    // $result = $this->reports->allEmpByUsedLeave();   

    // echo "<pre>"; var_dump($result); die;
    $CI =& get_instance();
    // $CI->load->model('schedule');
    $xls = New Spreadsheet_Excel_Writer($path);
    
 
    /** Fonts Format */
    $normal =& $xls->addFormat(array('Size' => 10));
    $normal->setAlign("center");
    $normal->setLocked();

    $normalcenter =& $xls->addFormat(array('Size' => 10));
    $normalcenter->setAlign("center");
    $normalcenter->setFontFamily('Book Antiqua');
    $normalcenter->setLocked();

    $normalunderlined =& $xls->addFormat(array('Size' => 10));
    $normalunderlined->setBottom(1);
    $normalunderlined->setLocked();
    
    $tardycenter =& $xls->addFormat(array('Size' => 10));
    $tardycenter->setAlign("center");
    $tardycenter->setColor("red");
    $tardycenter->setLocked();
    
    $failcenter =& $xls->addFormat(array('Size' => 10));
    $failcenter->setAlign("center");
    $failcenter->setBgColor("yellow");
    $failcenter->setFgColor("yellow");
    $failcenter->setLocked();
    
    $halfcenter =& $xls->addFormat(array('Size' => 10));
    $halfcenter->setAlign("center");
    $failcenter->setBgColor("yellow");
    $halfcenter->setColor("red");
    $halfcenter->setLocked();        
    
    $tits =& $xls->addFormat(array('Size' => 10));
    $tits->setBold();
    $tits->setAlign("center");
    $tits->setLocked();
    
    $titsnormal =& $xls->addFormat(array('Size' => 10));
    $titsnormal->setAlign("center");
    $titsnormal->setLocked();
    
    $coltitle =& $xls->addFormat(array('Size' => 10));
    $coltitle->setBorder(2);
    $coltitle->setBold();
    $coltitle->setAlign("center");
    $coltitle->setVAlign('vcenter');
    $coltitle->setFgColor('green');
    $coltitle->setColor('white');
    $coltitle->setLocked();

    $colnumber =& $xls->addFormat(array('Size' => 8));
    $colnumber->setNumFormat("#,##0.00");
    $colnumber->setBorder(1);
    $colnumber->setAlign("center");
 
    $messbord =& $xls->addFormat(array('Size' => 8));
    $messbord->setBorder(1);
    $messbord->setAlign("center");
    $messbord->setLocked();
    
    $messbordpink =& $xls->addFormat(array('Size' => 8));
    $messbordpink->setBorder(2);
    $messbordpink->setBgColor(12);
    $messbordpink->setFgColor(12);
    $messbordpink->setAlign("center");
    $messbordpink->setLocked();

    $justify_left =& $xls->addFormat(array('Size' => 10));
    $justify_left->setVAlign('vjustify');
    $justify_left->setVAlign('vcenter');
    $justify_left->setAlign('left');
    $justify_left->setColor('black');
    $justify_left->setSize(12);
    $justify_left->setLocked();

    $justify_center_gray =& $xls->addFormat(array('Size' => 10));
    // $justify_center_gray->setFgColor('gray');
    $justify_center_gray->setVAlign('vjustify');
    $justify_center_gray->setVAlign('vcenter');
    $justify_center_gray->setAlign('center');
    $justify_center_gray->setColor('black');
    $justify_center_gray->setLocked();

    $format_justify_center =& $xls->addFormat(array('Size' => 12));
    $format_justify_center->setFgColor('green');
    $format_justify_center->setVAlign('vjustify');
    $format_justify_center->setVAlign('vcenter');
    $format_justify_center->setAlign('center');
    $format_justify_center->setColor('white');
    $format_justify_center->setBorder(2);
    $format_justify_center->setLocked();

    $big =& $xls->addFormat(array('Size' => 12));
    $big->setLocked();
    
    $bigbold =& $xls->addFormat(array('Size' => 11));
    $bigbold->setBold();
    $bigbold->setLocked();

    $bigboldcenter =& $xls->addFormat(array('Size' => 12));
    $bigboldcenter->setBold();
    $bigboldcenter->setAlign("center");
    $bigboldcenter->setLocked();
    $bigboldcenter->setFontFamily('Book Antiqua');
    
    $bold =& $xls->addFormat(array('Size' => 8));
    $bold->setFontFamily('Book Antiqua');
    $bold->setBold();
    $bold->setLocked();
    
    $boldcenter =& $xls->addFormat(array('Size' => 8));
    $boldcenter->setAlign("center");
    $boldcenter->setFontFamily('Book Antiqua');
    $boldcenter->setBold();
    $boldcenter->setLocked();
    
    $amount =& $xls->addFormat(array('Size' => 8));
    $amount->setNumFormat("#,##0.00");
    $amount->setLocked();
    
    $amountbold =& $xls->addFormat(array('Size' => 8));
    $amountbold->setNumFormat("#,##0.00_);\(#,##0.00\)");
    $amountbold->setAlign("center");
    $amountbold->setBold();
    $amountbold->setLocked();
    
    $number =& $xls->addFormat(array('Size' => 8));
    $number->setNumFormat("#,##0");
    $number->setLocked();
    
    $numberbold =& $xls->addFormat(array('Size' => 8));
    $numberbold->setNumFormat("#,##0");
    $numberbold->setBold();
    $numberbold->setLocked();
    
    $dateform =& $xls->addFormat(array('Size' => 8));
    $dateform->setNumFormat("D-MMM-YYYY");
    $dateform->setLocked();
    
    $timeform =& $xls->addFormat(array('Size' => 8));
    $timeform->setNumFormat("h:mm:ss AM/PM");
    $timeform->setLocked();
    /* END */

    $max_col = 19;
    $numfield = $max_col - 1;

    if($numfield < 2) {
        $numfield = 1;
        $offset = 0;
        $hr = 10;   
    }else{
        $offset = intval(($numfield - 2) / 2);
        $hr = 0;
    }

    $sheet = &$xls->addWorksheet("Sheet 1");

    $sheet->setColumn(0, 0, 15); 
    $sheet->setColumn(1, 1, 50);

    $sheet->setColumn(2, 2, 15);
    $sheet->setColumn(3, 3, 15);
    $sheet->setColumn(4, 4, 15);
    $sheet->setColumn(5, 5, 15);
    $sheet->setColumn(6, 6, 15);
    $sheet->setColumn(7, 7, 15);
    $sheet->setColumn(8, 8, 15);

    $sheet->setColumn(9, 9, 15);
    $sheet->setColumn(10, 10, 15);
    $sheet->setColumn(11, 11, 15);
    $sheet->setColumn(12, 12, 15);
    $sheet->setColumn(13, 13, 15);
    $sheet->setColumn(14, 14, 15);

    $sheet->setColumn(15, 15, 15);
    $sheet->setColumn(16, 16, 15);
    $sheet->setColumn(17, 17, 25);
    $sheet->setColumn(18, 18, 10);

    // MERGE COLUMN

    $sheet->setMerge(0, 0, 0, $numfield);
    $sheet->setMerge(1, 0, 1, $numfield);
    $sheet->setMerge(2, 0, 2, $numfield);
    $sheet->setMerge(3, 0, 3, $numfield);
    $sheet->setMerge(4, 0, 4, $numfield);
    $sheet->setMerge(5, 0, 5, $numfield);

    $c = 0;$r = 0;
    $bitmap = "images/school_logo.bmp";
    
    $sheet->insertBitmap( $r , $c + $offset - 2 , $bitmap , 0 , 8 , .25 ,.20 );
    $r++;$c++;
    $sheet->write(1,0,"St. Therese - MTC Colleges",$boldcenter);
    $r++;
    $sheet->write(2,0," ",$boldcenter);
    $r++;
    $sheet->write(3,0,"Payroll Register Report",$bigboldcenter);
    $r++;
    $DATERANGE = "DATE: " .$this->extensions->getDTRCutoffConfig($sdate, $edate);
    $sheet->write(4,0,$DATERANGE,$normalcenter);

    $r = 7;
    $c = 0;

    // First header
    $display_fields = array(
        array('Information', 2, 1, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields); $c+=2;
    $display_fields = array(
        array('Earnings', 7, 1, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields); $c+=7;
    $display_fields = array(
        array('Deductions', 6, 1, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields); $c+=6;
    $display_fields = array(
        array('Net Pay', 1, 3, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields); $c++;
    $display_fields = array(
        array('Finalized By', 1, 3, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields); $c++;
    $display_fields = array(
        array('Finalized Date', 1, 3, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields); $c++;
    $display_fields = array(
        array('Hold', 1, 3, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields);

    // 2nd header

    $r++;
    $c = 0;

    $display_fields = array(
        array('Employee ID', 1, 2, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields);$c++;
    $display_fields = array(
        array('Full Name', 1, 2, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields);$c++;
    $display_fields = array(
        array('Regular Pay', 1, 2, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields);$c++;
    $display_fields = array(
        array('Tardy', 1, 2, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields);$c++;
    $display_fields = array(
        array('Absent', 1, 2, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields);$c++;
    $display_fields = array(
        array('Net Basic Pay', 1, 2, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields);$c++;
    $display_fields = array(
        array('Overtime', 1, 2, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields);$c++;
    $display_fields = array(
        array('Longevity', 1, 2, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields);$c++;
    $display_fields = array(
        array('Gross Salary', 1, 2, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields);$c++;
    $display_fields = array(
        array('WHT', 1, 2, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields);$c++;
    $display_fields = array(
        array('PAGIBIG', 1, 2, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields);$c++;
    $display_fields = array(
        array('PHILHEALTH', 1, 2, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields);$c++;
    $display_fields = array(
        array('SSS', 1, 2, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields);$c++;
    $display_fields = array(
        array('Cash Advance', 1, 2, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields);$c++;
    $display_fields = array(
        array('Facility Loan', 1, 2, $coltitle),
    );
    display_fields($sheet,$r,$c,$display_fields);$c++;

    //Data
    $c = 0;
    $r+=2;

    foreach ($emplist as $empid => $detail) {
        $sheet->write($r,$c,$empid,$normalcenter);$c++;
        $sheet->write($r,$c,$detail['fullname'],$normalcenter);$c++;
        $sheet->write($r,$c,number_format(isset($detail['salary']) ?  $detail['salary'] : 0, 2),$normalcenter);$c++;
        $sheet->write($r,$c,number_format(isset($detail['tardy']) ?  $detail['tardy'] : 0, 2),$normalcenter);$c++;
        $sheet->write($r,$c,number_format(isset($detail['absents']) ?  $detail['absents'] : 0, 2),$normalcenter);$c++;
        $sheet->write($r,$c,number_format(isset($detail['netbasicpay']) ?  $detail['netbasicpay'] : 0, 2),$normalcenter);$c++;
        $sheet->write($r,$c,number_format(isset($detail['overtime']) ?  $detail['overtime'] : 0, 2),$normalcenter);$c++;
        $sheet->write($r,$c,number_format(isset($detail['income'][1]) ?  $detail['income'][1] : 0, 2),$normalcenter);$c++;
        $sheet->write($r,$c,number_format(isset($detail['grosspay']) ?  $detail['grosspay'] : 0, 2),$normalcenter);$c++;
        $sheet->write($r,$c,number_format(isset($detail['whtax']) ?  $detail['whtax'] : 0, 2),$normalcenter);$c++;
        $sheet->write($r,$c,number_format(isset($detail['fixeddeduc']['PAGIBIG']) ?  $detail['fixeddeduc']['PAGIBIG'] : 0, 2),$normalcenter);$c++;
        $sheet->write($r,$c,number_format(isset($detail['fixeddeduc']['PHILHEALTH']) ?  $detail['fixeddeduc']['PHILHEALTH'] : 0, 2),$normalcenter);$c++;
        $sheet->write($r,$c,number_format(isset($detail['fixeddeduc']['SSS']) ?  $detail['fixeddeduc']['SSS'] : 0, 2),$normalcenter);$c++;
        $sheet->write($r,$c,number_format(isset($detail['deduction'][1]) ?  $detail['deduction'][1] : 0, 2),$normalcenter);$c++;
        $sheet->write($r,$c,number_format(isset($detail['loan'][1]) ?  $detail['loan'][1] : 0, 2),$normalcenter);$c++;
        $sheet->write($r,$c,number_format(isset($detail['netpay']) ?  $detail['netpay'] : 0, 2),$normalcenter);$c++;
        $sheet->write($r,$c,isset($detail['finalized_by']) ? $detail['finalized_by'] : '',$normalcenter);$c++;
        $sheet->write($r,$c,isset($detail['finalized_date']) ? date('F d, Y', strtotime($detail['finalized_date'])) : '',$normalcenter);$c++;
        $sheet->write($r,$c,($detail['isHold'] ? 'Yes' : 'No'),$normalcenter);$c++;
        $r++;
        $c=0;
    }

    $total = $total;

    $sheet->write($r,$c,'',$boldcenter);$c++;
    $sheet->write($r,$c,'Grand Total',$amountbold);$c++;
    $sheet->write($r,$c,number_format(isset($total['salary']) ?  $total['salary'] : 0, 2),$amountbold);$c++;
    $sheet->write($r,$c,number_format(isset($total['tardy']) ?  $total['tardy'] : 0, 2),$amountbold);$c++;
    $sheet->write($r,$c,number_format(isset($total['absents']) ?  $total['absents'] : 0, 2),$amountbold);$c++;
    $sheet->write($r,$c,number_format(isset($total['netbasicpay']) ?  $total['netbasicpay'] : 0, 2),$amountbold);$c++;
    $sheet->write($r,$c,number_format(isset($total['overtime']) ?  $total['overtime'] : 0, 2),$amountbold);$c++;
    $sheet->write($r,$c,number_format(isset($total['income'][1]) ?  $total['income'][1] : 0, 2),$amountbold);$c++;
    $sheet->write($r,$c,number_format(isset($total['grosspay']) ?  $total['grosspay'] : 0, 2),$amountbold);$c++;
    $sheet->write($r,$c,number_format(isset($total['whtax']) ?  $total['whtax'] : 0, 2),$amountbold);$c++;
    $sheet->write($r,$c,number_format(isset($total['fixeddeduc']['PAGIBIG']) ?  $total['fixeddeduc']['PAGIBIG'] : 0, 2),$amountbold);$c++;
    $sheet->write($r,$c,number_format(isset($total['fixeddeduc']['PHILHEALTH']) ?  $total['fixeddeduc']['PHILHEALTH'] : 0, 2),$amountbold);$c++;
    $sheet->write($r,$c,number_format(isset($total['fixeddeduc']['SSS']) ?  $total['fixeddeduc']['SSS'] : 0, 2),$amountbold);$c++;
    $sheet->write($r,$c,number_format(isset($total['deduction'][1]) ?  $total['deduction'][1] : 0, 2),$amountbold);$c++;
    $sheet->write($r,$c,number_format(isset($total['loan'][1]) ?  $total['loan'][1] : 0, 2),$amountbold);$c++;
    $sheet->write($r,$c,number_format(isset($total['netpay']) ?  $total['netpay'] : 0, 2),$amountbold);$c++;
    $sheet->write($r,$c,'',$normalcenter);$c++;
    $sheet->write($r,$c,'',$normalcenter);$c++;
    $sheet->write($r,$c,'',$normalcenter);$c++;
    $r++;
    $c=0;

    $xls->send('test.xls');
    $xls->close();
    
    function display_fields(&$sheet,$r,$c,$fields,$default_style=''){ 
        global $default_style;   
        foreach($fields as $colinfo){ 
            list($caption,$col_span,$row_span,$style) = $colinfo;  
            if($col_span > 1 || $row_span > 1) {
                $sheet->setMerge( $r, $c, ($row_span>1?(($r-1) + $row_span):$r), ($col_span>1?(($c-1) + $col_span):$c) );  
                for($new_col=0;$new_col<=($col_span-1);$new_col++){
                    $sheet->write($r,($c+$new_col),'',($style?$style:$default_style));
                    if($row_span>1){
                        for($new_row=0;$new_row<=($row_span-1);$new_row++){
                            $sheet->write(($r+$new_row),($c+$new_col),'',($style?$style:$default_style));
                        }
                    }
                }
            } 
            $sheet->write($r,$c,$caption,($style?$style:$default_style));
            $c += $col_span;
        }
    }
   
?>