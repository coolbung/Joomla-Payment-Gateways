<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.controller');

class PaymentController extends JController
{
	/**
	 * Method to show a weblinks view
	 *
	 * @access	public
	 * @since	1.5
	 */
	function display()
	{
		parent::display(true);

	}
	
	function gethtml() {
		$gateway = JRequest::getCmd('name');
		echo $gateway;
		jexit();
	}
}
