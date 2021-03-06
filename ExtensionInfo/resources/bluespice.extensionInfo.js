/**
 * ExtensionInfo extension
 *
 * @author     Markus Glaser <glaser@hallowelt.com>

 * @package    Bluespice_Extensions
 * @subpackage ExtensionInfo
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 */

//Hint: http://dev.sencha.com/deploy/dev/examples/grid/grouping.html

Ext.onReady( function(){
	Ext.create( 'BS.ExtensionInfo.Panel', {
		renderTo: 'bs-extensioninfo-grid'
	} );
} );