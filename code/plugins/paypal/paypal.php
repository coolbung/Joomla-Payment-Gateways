<?php

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

class  plgPaymentPaypal extends JPlugin
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
		$vars->business = $this->params->get('paypal_email', 'tekdiweb@gmail.com');
		
		$file = $this->buildLayout($vars);
		$html = $file;
		
		return $html;
	}
	
	
}
