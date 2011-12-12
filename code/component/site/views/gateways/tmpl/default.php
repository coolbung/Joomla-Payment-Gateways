<?php
// Check to ensure this file is included in Joomla!
defined( '_JEXEC' ) or die( 'Restricted access' );
JHTML::_( 'behavior.mootools' );

/* We're converting the payment gateways array into a dropdown. 
 * However you could use a radio, images or whatever you wish
 * The 'id' variable of the object will hold the internal name that is used for further actions (eg: paypal, authorize etc)
 */

$select[0]->id 		= 0;
$select[0]->name 	= JText::_('-Select Payment Gateway-');
$gateways = array_merge($select, $this->gateways);

$list = JHTML::_('select.genericlist', $gateways, 'gateways', 'class="inputbox" id="gateways"', 'id', 'name');
$url = 'index.php?option=com_searchengines&task=gethtml&name=';
$ajax = <<<EOT
window.addEvent( 'domready', function() {
 
	$('gateways').addEvent( 'change', function() {
 
		$('html-container').empty().setHTML('Loading...');
 
		var url = '{$url}' + $('gateways').value;
		var a = new Ajax( url, {
			method: 'get',
			onComplete: function( response ) {
				$('html-container').removeClass('ajax-loading').setHTML( response );
			}
		}).request();
	});
});
EOT;
$doc = JFactory::getDocument();
$doc->addScriptDeclaration($ajax);
?>
<div class="componentheading">Payment Gateway Plugins Demo</div>

<?php echo $list; ?>
<p></p>
<div id="html-container"></div>
