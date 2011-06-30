<?php
// Check to ensure this file is included in Joomla!
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');

class PaymentViewGateways extends JView
{
	function display( $tpl = null)
	{


		$dispatcher =& JDispatcher::getInstance();
		JPluginHelper::importPlugin('payment'); 
        $gateways = $dispatcher->trigger('onGetInfo');
        
		$this->assignRef('gateways',	$gateways);

		parent::display($tpl);
	}
}
