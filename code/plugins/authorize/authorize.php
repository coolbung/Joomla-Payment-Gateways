<?php

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

class  plgPaymentAuthorize extends JPlugin
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

	function onGetInfo()
	{
		$obj 		= new stdClass;
		$obj->name 	= $this->params->get('display_name', ucfirst($this->_name));
		$obj->id	= $this->_name;
		return $obj;

	}

	function onGetHTML()
	{
	}
}
