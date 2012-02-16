<?php
  /* SVN FILE: $Id: action.partial.html.php 162 2010-04-07 04:22:50Z ntemple $*/
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
  * @version SVN: $Id: action.partial.html.php 162 2010-04-07 04:22:50Z ntemple $
  *
  */

  defined('_JEXEC') or die('Restricted access');
  // Assumes isnid1 (username) is available
  global $isnid1, $machineid;
  
  // Assumes $pkg instance of softwarePackage
  // Assumes $ext available for version
  $ext = installedExtensions::getInstance();

  // Assumes $isPro is true if this is "pro"
  $jname          = $pkg->jname;
  $myversion      = $pkg->version;
  $currentversion = $ext->getVersion($jname);

  $action = PKG_INSTALLABLE; // $this->_is_updateable($package);
  if ($currentversion && version_compare($myversion, $currentversion, '>')) $action = PKG_UPDATEAVAILABLE; 
  if ($currentversion && ($myversion == $currentversion)) $action = PKG_NOACTION;
  if ( ! defined('_JLEGACY') && $pkg->requires('j15legacy') ) $action = PKG_NOCOMPAT;

  //if ($currentversion && version_compare($myversion, $currentversion, '=')) $action = PKG_NOACTION;

  if (!$pkg->access) {
    if ($pkg->credits == 0) {
      $action = PKG_GOPRO; 
    } else {
      $action = PKG_BUY;
    }    
  }
  $noaction = 'null';
  $href= UPDATER_LOADING_IFRAME;

  
  switch ($action) {
    case PKG_UPDATEAVAILABLE:  $task   = 'install';   $button  = 'Update Now';     
    if ($pkg->updates == 'none')  { $task = 'null'; $button  = 'Uninstall to Update'; }
    break;
    case PKG_INSTALLABLE:      $task   = 'install';   $button =  'Install';    break;
    case PKG_PREINSTALLED:     $task   = 'install';   $button =  'Re-Install'; break;
    case PKG_NOACTION:         $task   = 'install';   $button =  'Re-Install';  break;
    case PKG_NOCOMPAT:         $task   = 'null';      $button =  'Turn Legacy On'; break;
    case PKG_GOPRO:            $task   = 'gopro';     $button =  'Go Pro!'; $sku = 'manifest.pro'; break;
    case PKG_BUY:              $task   = 'buy';       $button =  'Buy Now'; $sku = $package; break;
  }
?>
<?php 

  switch ($task) {
    case 'null'    : echo $button; break;
    case 'details' :
    ?>
    <input type="button" value="<?php echo $button ?>"  onclick='submitform(<?php echo '"' . $task .'","' . $package . '","' . $action . '"'?>);'>
    <?php   break; // details
    case 'gopro' : $sku = 'manifest.pro';
    case 'buy'   : $href = "http://www.intellispire.com/network/order/order.php?sku=$sku&ref=$package&isnid=$isnid1&mid=$machineid";
    ?>            
    <a class="modal"
      href="<?php echo $href ?>"
      rel="{handler: 'iframe', size: {x: 620, y: 600}}">
      <input type="button" value="<?php echo $button ?>" >
    </a>
    <?php  
    break; // gopro    
    default:      
    ?>            
    <a class="modal"
      href="<?php echo $href ?>"
      rel="{handler: 'iframe', size: {x: 570, y: 200}}"
      onclick='submitform(<?php echo '"' . $task .'","' . $package . '","' . $action . '","' . $pkg->getConfirmation() . '"'?>);'>
      <input type="button" value="<?php echo $button ?>" >
    </a>
    <?php  
    break; // default
} // switch task

