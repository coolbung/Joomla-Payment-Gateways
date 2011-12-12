<?php

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

class  plgPaymentPaypal extends JPlugin
{

	var $_cache = null;
	var $last_error;                 // holds the last error encountered
   
	var $ipn_log;                    // bool: log IPN results to text file?
	   
	var $ipn_log_file;               // filename of the IPN log
	var $ipn_response;               // holds the IPN response from paypal   
	var $ipn_data = array();         // array contains the POST values for IPN
	   
	var $fields = array();           // array holds the fields to submit to paypal

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

	/* Internal use functions */
	
	function buildLayoutPath($layout) {
		
		$app = JFactory::getApplication();
	
		$core_file 	= dirname(__FILE__).DS.$this->_name.DS.'tmpl'.DS.'default.php';
		$override		= JPATH_BASE.DS.'templates'.DS.$app->getTemplate().DS.'html'.DS.'plugins'.DS.$this->_type.DS.$this->_name.DS.$layout.'.php';
		
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
	
	//gets the paypal URL
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

	function onGetHTML($vars)
	{

		$vars->action_url = $this->buildPaypalUrl();
		
		$vars->business = $this->params->get('payer_id', 'tekdiweb@gmail.com');
		//$vars->business =  'tekdiweb@gmail.com';
		
		$file = $this->buildLayout($vars);
		$html = $file;
		
		return $html;
	}
	
	function _validateIPN( $data)
	{
	 // parse the paypal URL
     $url=$this->buildPaypalUrl();	      
     $this->paypal_url= $url;
      $url_parsed=parse_url($url);        

      // generate the post string from the _POST vars aswell as load the
      // _POST vars into an arry so we can play with them from the calling
      // script.
      $post_string = '';    
      foreach ($data as $field=>$value) { 
         $this->ipn_data["$field"] = $value;
         $post_string .= $field.'='.urlencode(stripslashes($value)).'&'; 
      }
      $post_string.="cmd=_notify-validate"; // append ipn command

      // open the connection to paypal
      $fp = fsockopen($url_parsed[host],"80",$err_num,$err_str,30); 
     // $fp = fsockopen ($this->paypal_url, 80, $errno, $errstr, 30);
  
      if(!$fp) {
          
         // could not open the connection.  If loggin is on, the error message
         // will be in the log.
         $this->last_error = "fsockopen error no. $errnum: $errstr";
         $this->log_ipn_results(false);       
         return false;
         
      } else { 
 
         // Post the data back to paypal
         fputs($fp, "POST $url_parsed[path] HTTP/1.1\r\n"); 
         fputs($fp, "Host: $url_parsed[host]\r\n"); 
         fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n"); 
         fputs($fp, "Content-length: ".strlen($post_string)."\r\n"); 
         fputs($fp, "Connection: close\r\n\r\n"); 
         fputs($fp, $post_string . "\r\n\r\n"); 

         // loop through the response from the server and append to variable
         while(!feof($fp)) { 
            $this->ipn_response .= fgets($fp, 1024); 
         } 

         fclose($fp); // close connection

      }
      
      if (eregi("VERIFIED",$this->ipn_response)) {
  
         // Valid IPN transaction.
         $this->log_ipn_results(true);
         return true;       
         
      } else {
  
         // Invalid IPN transaction.  Check the log for details.
         $this->last_error = 'IPN Validation Failed.';
         $this->log_ipn_results(false);   
         return false;
         
      }
	
	}
	
	function onGetAuthorise($data) {
		$verify = $this->_validateIPN($data);
		
		if (!$verify) { return false; }
		
		if ($verify) {
			return $data['payment_status'];
		}
	}
	
	function log_ipn_results($success) {
       
      if (!$this->ipn_log) return;  // is logging turned off?
      
      // Timestamp
      $text = '['.date('m/d/Y g:i A').'] - '; 
      
      // Success or failure being logged?
      if ($success) $text .= "SUCCESS!\n";
      else $text .= 'FAIL: '.$this->last_error."\n";
      
      // Log the POST variables
      $text .= "IPN POST Vars from Paypal:\n";
      foreach ($this->ipn_data as $key=>$value) {
         $text .= "$key=$value, ";
      }
 
      // Log the response from the paypal server
      $text .= "\nIPN Response from Paypal Server:\n ".$this->ipn_response;
      
      // Write to log
      $fp=fopen($this->ipn_log_file,'a');
      fwrite($fp, $text . "\n\n"); 

      fclose($fp);  // close file
   }
	
	function getFormattedTransactionDetails( $data )
    {
        $separator = "\n";
        $formatted = array();

        foreach ($data as $key => $value) 
        {
            if ($key != 'view' && $key != 'layout') 
            {
                $formatted[] = $key . ' = ' . $value;
            }
        }
        
        return count($formatted) ? implode("\n", $formatted) : '';  
    }
	
	
	
}
