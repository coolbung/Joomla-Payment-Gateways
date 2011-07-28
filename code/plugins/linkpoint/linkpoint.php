<?php

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
$lang = & JFactory::getLanguage();
$lang->load('plg_payment_linkpoint', JPATH_ADMINISTRATOR);
jimport( 'joomla.plugin.plugin' );

class plgPaymentLinkpoint extends JPlugin
{

	var $_cache = null;

	function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);

		//Set the language in the class
		$config =& JFactory::getConfig();
		$options = array(
			'cachebase' 	=> JPATH_CACHE,
			'defaultgroup' 	=> 'page',
			'lifetime' 		=> $this->params->get('cachetime', 15) * 60,
			'browsercache'	=> $this->params->get('browsercache', false),
			'caching'		=> false,
			'language'		=> $config->getValue('config.language', 'en-GB')
		);		
		jimport('joomla.cache.cache');
		$this->_cache =& JCache::getInstance( 'page', $options );
	}

	function buildLayoutPath($layout) 
	{		
		$app = JFactory::getApplication();
		$core_file 	= dirname(__FILE__).DS.$this->_name.DS.'tmpl'.DS.'form.php';
		$override	= JPATH_BASE.DS.'templates'.DS.$app->getTemplate().DS.'html'.DS.'plugins'.DS.$this->_type.DS.$this->_name.DS.$layout.'.php';
		
		return (JFile::exists($override)) ? $override : $core_file;
	}
	
	//Builds the layout to be shown, along with hidden fields.
	function buildLayout($vars, $layout = 'default' )
	{
		// Load the layout & push variables
		ob_start();
        $layout = $this->buildLayoutPath($layout);
        include($layout);
        $html = ob_get_contents(); 
     	ob_end_clean();
        
		return $html;
	}
	
	function onGetHTML($vars)
	{
		$buildadsession =& JFactory::getSession();
		$vars->action_url = $this->buildPaypalUrl();
		$vars->business = $this->params->get('payer_id', 'tekdiweb@gmail.com');
		
		$buildadsession->set('tid',$tid);
		echo $html = $this->buildLayout('form');
	}
	
	function onGetAuthorise()
	{			
		session_start();
		include"linkpoint/lphp.php";
	
		$user 		= JFactory::getUser();	
		$data 		= JRequest::get('post');
		$session 	= JFactory::getSession();
		$sid 		= $session->get('sticketid');
		$db 		= & JFactory::getDBO();	
				
		$testmode 		= $this->params->get( 'testmode', '1' );
		$pemfilepath 	= $this->params->get( 'pem_filepath');
		$store_id 		= $this->params->get( 'store_id');
		$port		 	= $this->params->get( 'port', '1129');
		
		//print_r($data); die('here');	
		
		if($testmode == 1)
			$host = "staging.linkpt.net"; // Test-Sandbox Mode
		else
			$host = "secure.linkpt.net";  // Live Mode	
				
		// Generating Reference Id
		$chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$length = 10;
		$max_i = strlen($chars)-1;
		$value = '';
		for ($i=0;$i<$length;$i++)
		{
			$value .= $chars{mt_rand(0,$max_i)};
		}
		$orderid = $value;
		
		$mylphp = new lphp;

		$myorder["host"]       	= $host;
		$myorder["port"]       	= $port;
		$myorder["keyfile"] 	= $pemfilepath;
		$myorder["configfile"] 	= $store_id;       

		$myorder["ordertype"]         = "SALE";
		$myorder["result"]            = "GOOD";  # For test transactions, set to GOOD, DECLINE, or DUPLICATE
		$myorder["transactionorigin"] = "MOTO";  # For credit card retail txns, set to RETAIL, for Mail order/telephone order, set to MOTO, for e-commerce, leave out or set to ECI

		$myorder["oid"]               = $orderid;  # Order ID number must be unique. If not set, gateway will assign one.

		// Transaction Details
		$myorder["subtotal"]    = '100';
		$myorder["shipping"]    = '100';
		$myorder["chargetotal"] = '200';

		# CARD INFO
		$myorder["cardnumber"]   = $data['creditcard_number']; 
		$myorder["cardexpmonth"] = str_pad($data['expire_month'], 2, "0", STR_PAD_LEFT);
		$myorder["cardexpyear"]  = substr($data['expire_year'], 2);
		$myorder["cvmvalue"]     = $data['creditcard_code'];

		// Get the ticket details
		$query = " SELECT events.*, eventdetails.ticket_price,eventdetails.time_zone,ticket.id,ticket.user_id,ticket.name 
					FROM #__ticket_sales AS ticket  
					LEFT JOIN #__event_details AS eventdetails ON ticket.event_details_id = eventdetails.id 
					LEFT JOIN #__eventlist_events AS events ON events.id = eventdetails.event_id 
					WHERE ticket.id = $sid ";
		$db->setQuery($query);
		$ticketdetails = $db->loadobject();


		# BILLING INFO
		/*$myorder["name"]     = "Deepak Patil";
		$myorder["address1"] = "Pune";
		$myorder["city"]     = "pune";
		$myorder["state"]    = "Maharashtra";
		$myorder["country"]  = "India";
		$myorder["phone"]    = "123456";
		$myorder["fax"]      = "1234";
		$myorder["email"]    = "test@test.com";
		$myorder["zip"]      = "411051";

		# SHIPPING INFO
		$myorder["sname"]     = "Test Patil";
		$myorder["saddress1"] = "Test Address";
		$myorder["scity"]     = "Test City";
		$myorder["sstate"]    = "Test State";
		$myorder["szip"]      = "Test Zip"; 
		$myorder["scountry"]  = "Test country";*/


		//$myorder["debugging"] = "true";  # for development only - not intended for production use


	  	# Send transaction. Use one of two possible methods  #
		//$result = $mylphp->process($myorder);       # use shared library model	
	
		$result = $mylphp->curl_process($myorder);  # use curl methods

		//print_r($result); die('here');
	
	/*	if ($result["r_approved"] != "APPROVED")	// transaction failed, print the reason
		{
			print "Status: $result[r_approved]\n";
			print "Error: $result[r_error]\n";
		} else {
			// success
			print "Status: $result[r_approved]\n";
			print "Code: $result[r_code]\n";
			print "OID: $result[r_ordernum]\n\n";
		}
		
		return $result["r_approved"];	*/
		$log = &JLog::getInstance('paypal.log');
    	$log->addEntry(array('comment' => print_r($result, true)));
    	
    	if($testmode == 1)
    		$transid = $orderid;
    	else
    		$transid = $result['r_ref'];
    					
		$data = array('transaction_id'=>$transid,
						'payee_id'=>$user->email,
						'status'=>$result["r_approved"],
						'ticket_price'=>$ticketdetails->ticket_price
						);
		return $data;					
	}
	

	function buildPaypalUrl($secure = true)
	{
		$url = $this->params->get('sandbox') ? 'www.sandbox.paypal.com' : 'www.paypal.com';
				
		if ($secure) {
			$url = 'https://' . $url . '/cgi-bin/webscr';
		}
		
		return $url;
	}
	
	function onGetInfo()
	{
		$obj 		= new stdClass;
		$obj->name 	= $this->params->get('display_name', ucfirst($this->_name));
		$obj->id	= $this->_name;
		return $obj;
	}

	
}
