<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

require_once dirname(__FILE__) . '/mpdf/mpdf.php';

/**
* Integrating TDPDF to CodeIgniter
*/

class PdfCreator_mpdf extends mPDF{
	
	function __construct(){
		parent::__construct();
	}
}

/* End of file PdfCreator_mpdf.php*/
/* Location: ./application/libraries/PdfCreator_mpdf.php*/