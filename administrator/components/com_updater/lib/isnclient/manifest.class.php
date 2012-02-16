<?php
/* SVN FILE: $Id: manifest.class.php 162 2010-04-07 04:22:50Z ntemple $*/
/**
*
* ISN - Intellispire Network Client Toolkit
* Copyright (c) 2008 Nick Temple, Intellispire
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
* version 2.1 of the License. (and no other version)
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
* Lesser General Public License for more details.
*
* You should have received a copy of the GNU Lesser General Public
* License along with this library; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*
* @category   ISN
* @package    Client
* @author     Nick Temple <Nick.Temple@intellispire.com>
* @copyright  2008 Intellispire
* @license    LGPL 2.1
* @version    SVN: $Id: manifest.class.php 162 2010-04-07 04:22:50Z ntemple $
* @since      File available since Release 1.0
*
*/

require_once('spyc.php');

class softwareChannel {

  var $name;
  var $channel;
  var $access;
  var $ordre;
  var $packages;

  function __construct($manifest, $ordre) {
    $this->name     = $manifest['name'];
    $this->channel  = $manifest['channel'];
    $this->ordre    = $ordre;
    $this->access   = 0;

    $count = 0;
    foreach ($manifest['packages'] as $package) {
      $this->packages[] = new softwarePackage($this, $package, $count++);
    }    
  }

  function allowAccess($packagename) {
    if ($packagename == $this->channel) {
      $this->access = 1; 
      $packagename = ''; // global allow for children
    } 

    foreach ($this->packages as $package) {
      // Handle children 
      $package->allowAccess($packagename);
    }
  }

  function findPackage($packagename) {
    foreach ($this->packages as $package) {
      if ($package->isPackage($packagename))
        return $package;
    }      
  }

  public function __get( /*string*/ $name = null ) {
    if (isset($this->manifest[$name])) {
      return $this->manifest[$name];
    } else {
      return null;
    }
  }

}

class softwarePackage {
  var $access;
  var $ordre;

  function __construct($channel, $manifest, $ordre) {
    $this->manifest = $manifest;    
    $this->ordre  = $ordre;
    $this->channel = $channel;
    $this->access   = 0;
    $this->version = $this->version;
       
  }

  // allow access if the sku matches. '' matches any
  function allowAccess($packagename = '') {
    if ($packagename == $this->sku || $packagename == '') {
      $this->access = 1;
    }
  }

  function isPackage($packagename) {
    if ($this->sku == $packagename) 
      return true;
    else
      return false;
  }

  public function __get( /*string*/ $name = null ) {
    if (isset($this->manifest[$name])) {
      return $this->manifest[$name];
    } else {
      return null;
    }
  }
  
  public function requires($sw) {
    if (strpos($this->compatibility, $sw) === false) {
      return false;
    } else {
      return true;
    }
  }

  function getStabilityHTML() {

    $level = $this->stability_level;
    $msg   =  $this->stability_msg;

    switch ($level) {
      case 'Excellent':  $title = 'Well supported, no known issues.'; $color = 'green'; break;
      case 'Fair':       $title = 'Some problems are known.'; $color = 'black'; break;
      case 'Poor':       $title = 'Some problems may make this component unuseable.'; $color = 'red';  break;
      case 'Good':       $title = 'Well supported, no known issues.'; $color = 'green';  break;
      default:           $title = 'No major issues.'; $color = 'black'; break;
    }
    if (!$msg) $msg = $title;
    return("<div title='$msg' style='color: $color;'>$level</div>"); 
  }

  function getConfirmation() {
    return '';
  }

  function remote() {
    if (isset($this->remote_data)) return;

    $url = UPDATER_DISPLAY . '?package=' . urlencode($this->sku);
    $remote_data = isJInstallerHelper::url_retrieve_curl($url);
    $remote_data = Spyc::YAMLLoad($remote_data);

//    print "<pre>\n";
//    print "$url\n";
//    print_r($remote_data);
//    print "</pre>\n";
    
    $this->set($remote_data, 'description');    
    $this->set($remote_data, 'link_home');
    $this->set($remote_data, 'link_extdir');
    $this->set($remote_data, 'link_support');

    $this->remote_data = $remote_data;
  }
  function set($a, $var) {
    if (isset($a[$var])) 
      $this->$var = $a[$var];
    else
      $this->$var = '';      
  }


  function getPrice() {
    return $this->credits;
  }

  /**
  * check to see if we can update the installed version
  *
  * @param mixed $package
  * @param mixed $installed_version
  * @return boolean
  */
  function getUpdateVersion($package, $installed_version) {
    if (isset($this->manifest['software']['joomla'][$package]['updates'])) {
      $updates = $this->manifest['software']['joomla'][$package]['updates'];
      $updates = explode(',', $updates); // new for 1.2
      if (in_array($installed_version, $updates)) {
        return true;
      }
    }
    return false;
  }

//  function hasBeenPaidFor($package) {
//    if ($this->getPrice($package) == 0) {
//      return true;
//    } else {
//      return false;
//    }
//  }
}



class Manifest {

  static $location;
  var $manifest;
  var $channels;

  /*
  For version 2.0 of the manifest 
  [filetype] => softwaremanifest
  [version] => 2.0.0
  [iversion] => 4
  [support] => Array ()
  (
  [NONE] => This is free software. Support is provided by the author or through an optional Intellispire support contract.
  [COM] => This is fully supported commercial software. Support is provided by the author.
  [IS] => This is fully supported Intellispire software.
  )

  [jlatest] => 1.5.15
  [product] => updater
  [packages] => Array
  (
  [0] => manifest.pro
  [1] => joomla.aweberpak
  [2] => manifest.joomla
  [3] => manifest.lang
  )

  [channels] => Array()
  */

  public function __get( /*string*/ $name = null ) {
    if (isset($this->manifest[$name])) {
      return $this->manifest[$name];
    } else {
      return null;
    }
  }

  /**
  * @desc Load the manifest from disk.
  */
  function loadManifest($location, $force = false) {
    if (! file_exists($location)) {
      return null;
    }    

    if ($location == $this->location && $force == false) {
      return $this->manifest;
    }
    $this->manifest =  Spyc::YAMLLoad($location);
    $this->location = $location;
    $this->channels = $this->getChannels(); // could be a delay load    
    $this->setAccess();
    return $this;
  }

  private function getChannels() {

    $channels = array();
    $count = 0;
    foreach ($this->manifest['channels'] as $channel) {
      if (in_array($channel['channel'], $this->packages)) {
        $access = 1;
      } else {
        $access = 0;
      }
      $channels[] = new softwareChannel($channel, $count++);     
    }
    unset ($this->manifest['channels']);

    return $channels;    
  }

  private function setAccess() {
    // Iterate over all, and set our access level
    // 0: Can view, not download (default)
    // 1: Can view and download    
    foreach ($this->packages as $package) {
      foreach ($this->channels as $channel) {
        $channel->allowAccess($package);
      }    
    }
  }

  function isPro() {
   
    foreach($this->packages as $package) {
      if ($package == 'manifest.pro') return true;
    }
    return false;
  }

  function getLatestJoomlaVersion() {   return $this->jlatest;   }
  function getVersion() {   return $this->iversion;   }

  function needUpdate() {
    $r = version_compare(JVERSION, $this->jlatest);

    if ($r < 0) return true;
    return false;
  }

  //
  //  function countItems() {
  //    return 0;
  //    return count( $this->manifest['software']['joomla'] );
  //  }

  //  /**
  //  * @desc Get an array of items.
  //  * TODO: make more generic
  //  * TODO: allow sorting / filtering on the list
  //  */
  //  function getItems($limitstart = 0, $limit = 1000) {
  //    return 0;
  //    
  //    $allitems = $this->manifest['software']['joomla'];
  //    if ($limit == 0) return $allitems;

  //    return array_slice($allitems, $limitstart, $limit, true);
  //  }

  //
  //  function getMessage() {
  //    if (isset($this->manifest['message'])) {
  //      return $this->manifest['message'];
  //    }
  //    return '';
  //  }

  function findPackage($sku) {
    foreach ($this->channels as $channel) {
      $package = $channel->findPackage($sku);
      if ($package) return $package;
    } 
    return false;            
  }

  function useCoreInstaller($sku) {
    $package = $this->findPackage($sku);
    if (!$package) return null;
    return $package->usecore;
  }

  function getSerial($sku) {
    $package = $this->findPackage($sku);
    if (!$package) return null;
    return $package->iversion;
  }

  

  //  /* find an item by name */
  //  function getItem($sku) {
  //     $package = $this->findPackage($sku);
  //    if (!package) return false;
  //    return  $package;
  //  }

  //  function getSupportTypes() {
  //    return  $this->manifest['support'];
  //  }

  //  function getSerial($package) {
  //    
  //    if (isset($this->manifest['software']['joomla'][$package]['iversion'])) {
  //      $iversion = $this->manifest['software']['joomla'][$package]['iversion'];
  //      return $iversion;
  //    } else {
  //      return 0;
  //    }
  //    
  //  }

  //  function XgetPlatforms($package) {
  //    return explode(',', $this->manifest['software']['joomla'][$package]['compatibility']); // new
  //  }

}


