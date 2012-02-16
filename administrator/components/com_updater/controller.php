<?php
/* SVN FILE: $Id: controller.php 169 2010-04-26 19:41:53Z ntemple $*/
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
* @copyright    2008 Intellispire/Nick Temple
* @version SVN: $Id: controller.php 169 2010-04-26 19:41:53Z ntemple $
*
*/
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

define('PKG_INSTALLABLE', 1);
define('PKG_PREINSTALLED', 2);
define('PKG_UPDATEAVAILABLE', 3);
define('PKG_NOACTION', 4);
define('PKG_NOCOMPAT', 5);
define('PKG_BUY', 6);
define('PKG_SUBSCRIPTION_BUY', 7);
define('PKG_SUBSCRIPTION_ACTIVE', 8);
define('PKG_GOPRO', 9);


jimport('joomla.application.component.controller');
jimport( 'joomla.error.error' );


require_once('updater.model.php');
require_once(JPATH_COMPONENT_ADMINISTRATOR .'/lib/installer/helper.php');

class UpdaterController extends JController
{
  /* @var Manifest */
  var $manifest;

  // Default task
  function display()
  {
    global $mainframe;

    $this->registry =& JFactory::getConfig();
    
    $validated =  $this->registry->getValue('com_updater.validated', 0);

    if (!$validated) {
      $this->setRedirect('index.php?option=com_updater&task=profile');
      return;
    }

    $model = new UpdaterModel();
    $this->forceinstall =   $model->getFlagForceInstall();
    $this->forcecopy    =   $model->getFlagForceCopy();

    // Upgrade process
    // here because we need the model
    $registry = $this->registry;
    $key = 'com_updater.adapter';
    if ($registry->getValue($key, 0) != UPDATER_ADAPTER) {
      copyAdapter(); // Try once
      $registry->setValue($key, UPDATER_ADAPTER);    
      $model->_storeRegistry();
    } else {
      // Make sure the file exists
      if (! file_exists(UPDATER_ADAPTER_PATH)) {
        copyAdapter();
      }
    }

    $this->manifest = $model->getManifest();
    if ($this->manifest->version < 4) {
      $model->retrievemanifest();
      $this->manifest = $model->getManifest(true); 
    }

    $is_pro = $this->manifest->isPro();

    $title =  'Add or Update Software';
    if ($is_pro) {
      $title .= ' - Professional';
    }

    JToolBarHelper::title( JText::_( $title ), 'install.png' );

    $need_update = $this->manifest->needUpdate();
    $latest = $this->manifest->getLatestJoomlaVersion();


    // NLT testing upgrade logic
    // $need_update = true;
    // $latest = '1.5.16';
    //

    if ($is_pro) {
      if ($need_update) {
        $icon    = 'upload';
        $action  = 'upgrade';
        $alt     =  "Upgrade to J!" . $latest;
      } else {
        $icon    = 'default';
        $action  = 'noupgrade';
        $alt     =  "Site Current: " . JVERSION;
      }
    } else {
      $icon    = 'default';
      $action  = 'gopro';
      $alt     = "Upgrade to Pro!";
      if ($need_update) {
        $alt .= " Warning! Upgrade Needed: " . $latest;
      }

    }

    JToolBarHelper::custom( $action , $icon . '.png', $icon . '_f2.png', $alt, false );

    JToolBarHelper::custom( 'updatemanifest', 'apply.png', 'apply_f2.png', 'Refresh Software List', false );

    if ( defined('_JLEGACY')) {  $mode = 'Off';  } else { $mode = 'On'; }
    JToolBarHelper::custom( 'legacy', 'forward.png', 'forward_f2.png', "Turn Legacy Mode $mode", false );
    //    JToolBarHelper::custom( 'install', 'forward.png', 'forward_f2.png', "Install", false );
    JToolBarHelper::preferences('com_updater', '550');
    JToolBarHelper::help('updaterhelp', true);

    include(UPDATER_EVIEWS . 'display.view.default.html.php');
  }

  function profile() {
    $this->registry =& JFactory::getConfig();

    JToolBarHelper::title( JText::_( 'Activate Software Installer' ), 'install.png' );
    JToolBarHelper::preferences('com_updater', '550');
    JToolBarHelper::help('updaterhelp', true);

    $channels  =  'joomla'; 
    $username  =  $this->registry->getValue('com_updater.username', '');
    $validated =  $this->registry->getValue('com_updater.validated', 0);
    $name      =  $this->registry->getValue('com_updater.name', '');
    $email     =  $this->registry->getValue('com_updater.email', '');

    if ($validated) {
      include(UPDATER_EVIEWS . 'profile.view.default.html.php');
    } else {
      include(UPDATER_EVIEWS . 'activate.view.default.html.php');
    }
  }

  function tools() {
    $this->registry =& JFactory::getConfig();
    JToolBarHelper::title( JText::_( 'Diagnostics'), 'install.png' );
    JToolBarHelper::preferences('com_updater', '550');
    JToolBarHelper::help('updaterhelp', true);

    $model = new UpdaterModel();
    $this->manifest    = $model->getManifest();

    $registry =& JFactory::getConfig();
    $isnid      = $registry->getValue('com_updater.isnid',  null);
    list($n1, $n2, $n, $key) = explode('-', $isnid);
    $isnid = "$n1-$n2";

    /* Get phpInfo() */
    $info = phpinfo_array(true);

    /* TEST Permissions */

    $config =& JFactory::getConfig();
    $target =  $config->getValue('config.tmp_path').DS. md5(time()) . '.php';
    file_put_contents($target, 'test');
    $finfo2 = testFile($target);
    unlink($target);


    $finfo1     = testFile('index.php');
    $finfo3     = testFile($config->getValue('config.tmp_path'));
    $finfo4     = testFile(JPATH_SITE);
    $finfo5     = testFile(JPATH_SITE . '/' . "libraries/joomla/version.php");

    include(UPDATER_EVIEWS . 'tools.view.default.html.php');
  }

  function upcheck() {
    $this->registry =& JFactory::getConfig();
    JToolBarHelper::title( JText::_( 'Upgrade Check'), 'install.png' );
    JToolBarHelper::preferences('com_updater', '550');
    JToolBarHelper::help('updaterhelp', true);

    $file_errors = $file_errors = checkFiles();

    include(UPDATER_EVIEWS . 'check.view.default.html.php');
  }



  function details() {    
    $this->registry =& JFactory::getConfig();
    $package = JRequest::getVar('package');

    // NLT Fix Redirect
    if ($package == 'manifest.pro') {
      // special case Pro
      print "redirecting ....";
      die();
    }

    JToolBarHelper::title( JText::_( 'Software Details'), 'install.png' );
    JToolBarHelper::preferences('com_updater', '550');
    JToolBarHelper::help('updaterhelp', true);

    $model = new UpdaterModel();

    $this->manifest    = $model->getManifest();
    $this->package     = $this->manifest->findPackage($package);

    include(UPDATER_EVIEWS . 'details.view.default.html.php');
  }


  # ==================

  function saveprofile() {
    $registry =& JFactory::getConfig();
    $this->registry = $registry;
    $model = new UpdaterModel();

    $channels  = 'joomla'; // trim(JRequest::getString('channels'));
    if (strstr($channels, 'joomla') === false) {
      return $this->setRedirect('index.php?option=com_updater&task=profile',
      'Error: Invalid Channel List.',
      'error'
      );
    }

    $registry->setValue('com_updater.channels', $channels);
    $model->_storeRegistry();
    $model->retrievemanifest();


    $this->setRedirect('index.php?option=com_updater&task=profile',
    'Profile Saved',
    'message');
  }

  function activate() {
    $registry =& JFactory::getConfig();
    $this->registry = $registry;
    $model = new UpdaterModel();

    $email  = trim(JRequest::getString('email'));
    $isnid  = trim(JRequest::getString('isnid'));

    if (! validate_email($email)) {
      return $this->setRedirect('index.php?option=com_updater&task=profile',
      'Error: Invalid Email. Please retry.',
      'error'
      );
    }

    $success = $model->activate($email, $isnid);
    if ($success)
      $succes = $model->retrievemanifest();

    if ($success) {
      $this->setRedirect('index.php?option=com_updater&task=display',
      'Activation Confirmed',
      'message');
    } else {
      $this->setRedirect('index.php?option=com_updater&task=profile',
      'Error activating your account. Please verify your network id and try again.',
      'error');
    }

  }

  function register() {
    $model = new UpdaterModel();

    $name   = trim(JRequest::getString('name'));
    $email  = trim(JRequest::getString('email'));

    if (!$name)
      return $this->setRedirect('index.php?option=com_updater&task=profile',
      'Error: Missing Name. Please retry.',
      'error'
      );

    if (!validate_email($email))
      return $this->setRedirect('index.php?option=com_updater&task=profile',
      'Error: Invalid Email. Please retry.',
      'error'
      );

    $msg = $model->register($name, $email);

    $type = 'message';
    if (strstr($msg, 'Err')) {
      $type = 'error';
    }

    $this->setRedirect('index.php?option=com_updater&task=profile', $msg, $type);
  }


  # =====

  function gopro() {
    $this->setRedirect('index.php?option=com_updater&task=details&package=manifest.pro', '', '');
    return;
  }

  function upgrade() {
    $model = new UpdaterModel();
    $this->manifest = $model->getManifest();
    $this->forceinstall =   $model->getFlagForceInstall();

    $this->forceinstall = true; // We're not checking today/.

    if (! $this->forceinstall) {
      $file_errors = checkFiles();
      $errors = count($file_errors);
      if ($errors > 0) {
        $this->setRedirect('index.php?option=com_updater&task=display', 
        "We have detected $errors files that are not writeable in your Jooml! install - check 'Diagnostics' for details. Joomla! Upgrade may fail and will not continue.  
        To get rid of this message and continue, fix your website permissions or
        enable 'Force Install' in the parameters menu.", 'error');    
        return;        
      }
    }

    $url = $model->retrievedownloadlink('joomla.jupgrade');
    $p_file = isJInstallerHelper::downloadPackage($url);

    $config =& JFactory::getConfig();
    $tmp_dest   = $config->getValue('config.tmp_path');                    

    // Alternate: extract then copy
    $success = isJInstallerHelper::extract("$tmp_dest/$p_file", "/", true);

    // Direct extract *should* work better
    //$success = isJInstallerHelper::archive_extract("$tmp_dest/$p_file", JPATH_ROOT); // Direct extraction

    if ($success) {
      $this->setRedirect('index.php?option=com_updater&task=display', 'Joomla! Upgrade Success');    
    }  else {
      $this->setRedirect('index.php?option=com_updater&task=display', 'Joomla! Upgrade Failed <i>Please Check Your Sites Permissions!</i>', 'error');          
    }
  }

  function install() {
    // also check "packages"
    $package = JRequest::getVar('package');
    if ($package) {
      return $this->genericInstall($package, 'Package Install ');
    }

    /*
    * Doesn't work due to com_install re-declaration problems with some packages.
    * Need to start another process. Maybe 1.5.1?
    */
    /*
    $configModel = new UpdaterModel();
    $this->manifest = $configModel->getManifest();

    $packages = JRequest::getVar('packages');
    $details = '';
    foreach ($packages as $package) {
    try {
    $url = $configModel->retrievedownloadlink($package);
    $details .= coreInstall($url);        
    } catch (Exception $e) {
    // do nothing
    }      
    }    
    unlink(SOFTWAREINSTALLEDSTACHE);
    include(UPDATER_EVIEWS . 'install.view.default.html.php');
    */    
  }

  // No more update function

  function genericInstall($package, $message) {

    $configModel = new UpdaterModel();
    $this->manifest = $configModel->getManifest();

    $downloadOnly = $configModel->getFlagDownloadOnly();

    $e = null;
    $success = true;

    try {
      $url = $configModel->retrievedownloadlink($package);
      if($downloadOnly) {
        $message .= ' (Download Only) ';
        $p_file = isJInstallerHelper::downloadPackage($url);
        if (!$p_file)  $success = false;
      } else {
        $details = coreInstall($url);
        if (!$details) $success = false;
      }
    } catch (Exception $e) {
      $success = false;
    }

    if (!$success) {
      $message .= ' Failed.';
      if ($e) $message .= ': ' .  $e->getMessage();
      $this->setRedirect('index.php?option=com_updater&task=display', $message, 'error');    
      return;
    }

    $message .= ' Success';
    unlink(SOFTWAREINSTALLEDSTACHE); // Force refresh of installed software       
    //    $this->setRedirect('index.php?option=com_updater&task=display', $message, 'success');    
    include(UPDATER_EVIEWS . 'install.view.default.html.php');

  }

  function updatemanifest() {
    $task = jRequest::getVar('redirect', 'profile');
    $configModel = new UpdaterModel();

    if ($configModel->retrievemanifest() ) {
      unlink(SOFTWAREINSTALLEDSTACHE); // Force refresh of installed software       
      $this->setRedirect('index.php?option=com_updater&task=' . $task,
      'Package list updated.',
      'message'
      );
    } else {
      $this->setRedirect('index.php?option=com_updater&task=' . $task,
      'Warning: could not download package list.',
      'error'
      );
    }
    return;
  }


  function legacytoggle() {

    if ( defined('_JLEGACY')) {
      $mode = 0;
    } else {
      $mode = 1;
    }
    $db =& JFactory::getDBO();
    $db->setQuery("update #__plugins set published=$mode where element='legacy'");
    $db->query();

    $task = jRequest::getVar('redirect', 'display');
    $this->setRedirect('index.php?option=com_updater&task=' . $task,
    'Legacy Mode Enabled.',
    'message'
    );
    return;
  }


  /**
  * This is called after a user thinks they've made a purchase
  */

  function pinstall() {
    $package = JRequest::getVar('package');
    $configModel = new UpdaterModel();

    $success = $configModel->retrievemanifest();
    if ($success) {
      $model = new UpdaterModel();
      $this->manifest = $model->getManifest();
      if ($this->manifest->hasBeenPaidFor($package)) {
        return $this->install();
      }
    }
    // If we get here, then there was a failure. Most likely, they never purchased.

    $this->setRedirect('index.php?option=com_updater&task=details&package=' . $package,
    'Could not verify payment. Please try again, or <a href="http://www.intellispire.com/support/"
    target=isn>click here for support</a>',
    'error'
    );

  }

}
# =====

/**
* use the core installer to download and install a single package.
*
* @param mixed $url
*/

function coreInstall($url) {
  jimport( 'joomla.application.component.model' );
  jimport( 'joomla.installer.installer' );
  jimport( 'joomla.installer.helper');

  updater_writelog('+Package coreInstell: ' . $url);

  // Warning: JinstallerHelper requires file_get_contents to be available
  // Here we use our own function
  $p_file = isJInstallerHelper::downloadPackage($url);
  if (!$p_file) throw new Exception("Can't download file. (1)");

  $config =& JFactory::getConfig();
  $tmp_dest   = $config->getValue('config.tmp_path');                
  $package = JInstallerHelper::unpack($tmp_dest.DS.$p_file);

  if (!$package) throw new Exception ("Can't extract package. Is package valid?");


  $installer =& JInstaller::getInstance();
  $success = $installer->install($package['dir']);

  if (!is_file($package['packagefile'])) {
    $package['packagefile'] = $config->getValue('config.tmp_path').DS.$package['packagefile'];
  }
  JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

  updater_writelog('-Package coreInstell: ' . $url);

  if ($success)
    return $installer->message;
  else
    return false;
}



