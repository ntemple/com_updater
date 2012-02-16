<?php
/* SVN FILE: $Id: admin.updater.php 169 2010-04-26 19:41:53Z ntemple $*/
/**
*
* ISN - Intellispire Network Client for Joomla! 1.5
* Copyright (c) 2008 Nick Temple, Intellispire
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; version 2
* of the License, and no other version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*
* @category     ISN
* @package      Joomla Client
* @author       Nick Temple <nickt@nicktemple.com>
* @license      GNU/GPL 2.0 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
* @copyright    2008-2010 Intellispire/Nick Temple
* @version SVN: $Id: admin.updater.php 169 2010-04-26 19:41:53Z ntemple $
*
*/

// no direct access
defined('_JEXEC') or die('Restricted Access.');

define('UPDATER_VERSION',   31);
define('UPDATER_ADAPTER',   31);
define('UPDATER_ADAPTER_PATH', JPATH_LIBRARIES . '/joomla/installer/adapters/expackage.php');
define('UPDATER_HVERSION',  '1.5.1');
define('UPDATER_REVISON',   '${svn.lastrevision}');   
define('UPDATER_NODE',     'rpc.intellispire.com/network/server7/'); // RELEASE

define('UPDATER_SERVER',    'http://' . UPDATER_NODE . 'soap.php');
define('UPDATER_DISPLAY',   'http://' . UPDATER_NODE . 'details.php');


define('UPDATER_DEBUG', 0);
define('UPDATER_CONFIG', JPATH_COMPONENT_ADMINISTRATOR . '/config/');
define('UPDATER_EVIEWS', JPATH_COMPONENT_ADMINISTRATOR . '/eviews/');
define('UPDATER_LOADING_IFRAME', JURI::base() . 'components/com_updater/images/ajax-loader.html');
define('UPDATER_CHANNELS', 'joomla');

jimport('joomla.client.helper');
updater_writelog(UPDATER_VERSION, 'START');

$FTPOptions = JClientHelper::getCredentials('ftp');
if ($FTPOptions['enabled'] == 1) {
  define('UPDATER_FTP_ENABLED', 1);
  JError::raiseWarning(1999, 'ERROR: Joomla FTP Mode Enabled. FTP mode is not supported - operations will fail.');
} else {
  define('UPDATER_FTP_ENABLED', 0);
}

/*
* Make sure the user is authorized to view this page
* We are the same security as the installer subsystem
*/

$user = & JFactory::getUser();
if (!$user->authorize('com_installer', 'installer')) {
  $mainframe->redirect('index.php', JText::_('ALERTNOTAUTH'));
}

/** @param JRegistry */
$registry =& JFactory::getConfig();

// Load our own config file in scope
// Does this create a new scope?
if (file_exists(UPDATER_CONFIG . 'configuration.php'))
{
  @include_once(UPDATER_CONFIG . 'configuration.php');
  $_object = new ComUpdaterConfig();
  $registry->loadObject($_object, 'com_updater');
} else {
  $registry->setValue('com_updater.joomla_updater', UPDATER_VERSION);
}

global $isnid, $isnid1, $machineid;  
$machineid  = $registry->getValue('com_updater.machineid', null);
$isnid      = $registry->getValue('com_updater.isnid',  null);
if ($isnid) {
  list($n1, $n2, $n, $key) = explode('-', $isnid);
  $isnid1 = "$n1-$n2";
}
// Load CURL
require_once( JPATH_COMPONENT_ADMINISTRATOR . '/lib/curlemu/libcurlemu.inc.php');

JHTML::_('behavior.modal');
JSubMenuHelper::addEntry(JText::_( 'Add or Update Software' ),  'index.php?option=com_updater&task=display', true);
JSubMenuHelper::addEntry(JText::_( 'Diagnostics' ),  'index.php?option=com_updater&task=tools', true);
JSubMenuHelper::addEntry(JText::_( 'Upgrade Check' ),  'index.php?option=com_updater&task=upcheck', true);

// Require the base controller
require_once( JPATH_COMPONENT.DS.'controller.php' );
$controller   = new UpdaterController();
// remove xml warnings for badly formed xml
$olderr = error_reporting(0); // RELEASE
$controller->execute( JRequest::getVar( 'task' ) );
error_reporting($olderr);  // RELEASE
$controller->redirect();
// ==================

function updater_writelog($out, $label = '') {

  if (!UPDATER_DEBUG) return;

  if (! is_scalar($out)) {
    ob_start();
    print_r($out);
    $out = ob_get_clean();
  }
  $time = time();
  $fh = fopen('/tmp/installer-log.txt', "a+");
  fwrite($fh, "== $label $time ==\n");
  fwrite($fh, $out);
  fclose($fh);
}

function copyAdapter() {
  $file = JPATH_COMPONENT_ADMINISTRATOR . '/lib/expackage.php';
  $path = UPDATER_ADAPTER_PATH;
  @copy ($file, $path);
  if (! file_exists($path)) {
    JError::raisewarning(2000, 'ERROR: could not install package adapter. Packages will not install properly. Please manually copy:'
    . "<br>\n$file to <br>\n" . $path);
    return false;
  }
  return true;
}
