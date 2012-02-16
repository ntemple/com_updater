<?php
/* SVN FILE: $Id: intellispireNetworkClient.class.php 162 2010-04-07 04:22:50Z ntemple $*/
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
 * @version    SVN: $Id: intellispireNetworkClient.class.php 162 2010-04-07 04:22:50Z ntemple $
 * @since      File available since Release 1.0
 *
 */

require_once('nusoap/nusoap.php');
require_once('utilSignature.class.php');
require_once('isnid.class.php');
require_once('utils.inc.php');
require_once('manifest.class.php');

define('LIBARY_UPDATER_VERSION', 9);

class intellispireNetworkClient {

  var $repository;
  var $channels;
  var $machineid;

  /** @var isnid */
  var $isnid;
  var $_response;
  var $_soapclient;

  function getUsername() {
    return $this->isnid->getUserName();
  }

  function getSecret() {
    return $this->isnid->getSecret();
  }

  function getMachineId() {
    return $this->machineid;
  }

  function getRepository() {
    return $this->repository;
  }

  function getChannels() {
    return $this->channels;
  }

  /* constructor */
  function intellispireNetworkClient($repository, $channels, $isnid = null, $machineid = null) {
    $this->repository = $repository;
    $this->isnid = new isnid($isnid);
    $this->channels = $channels;

    if ($machineid) {
      $this->machineid = $machineid;
    } else {
      $this->machineid = uuid();
    }
  }

  function _call_remote($method, $params = '') {

    $registration = array (
      'username'   => $this->getUsername(),
      'machineid'  => $this->getMachineId(),
      'timestamp'  => time(),
      'version'    => UPDATER_VERSION,
      'channels'   => $this->getChannels(),
      'client'     => $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']
    );

    $registration['signature'] = utilSignature::sign($registration, $this->getSecret());

    $params['registration'] = $registration;
    $service = $this->getRepository();

    // Lots of warnings from NuSoap we don't want to see
    ob_start();
    $soapclient = new nusoap_client($service . '?wsdl', "wsdl");
    $response = $soapclient->call($method, $params);


    // Save to interogate if necessary
    $this->_response = $response;
    $this->_soapclient = $soapclient;
    ob_end_clean(); // end error hiding

    if ($error = $soapclient->getError()) {
      throw new Exception($error, 500);
    }

    /* decode payload if there is one */
    if (isset($response['payload'])) {
       if (isset($response['gzip']) && $response['gzip'] == 'gzdecode') {
          // Pure PHP function to decode files
          $response['payload'] = gzdecode(base64_decode($response['payload']));
       }

       if (md5($response['payload'] . strlen($response['payload'])) != $response['pmd5']) {
          throw new Exception("bad checksum", 500);
       }
    }

    return $response;
 }

 /*
 *  Remote calls
 */

  function retrievedownloadlink($package, $version = 0) {    
    $result = $this->_call_remote('retrievedownloadlink', array('package' => $package, 'current_version' => $version) );
    return $result['link'];
  }

  function retrievemanifest($subpackage = 'joomla.*', $version = 0) {
    $result = $this->_call_remote('retrievemanifest', array('subpackage' => $subpackage, 'version' => $version) );
    return $result;
  }

  function register($name, $email) {

     $result = $this->_call_remote('register', array(
       'name'     => $name,
       'email'    => $email,
       'remoteip' => $_SERVER['REMOTE_ADDR'],
       )
     );

     return $result['title'];
  }


  function activate($email, $software, $phpversion) {

     $result = $this->_call_remote('activate', array(
       'email'       => $email,
       'software'   => $software,
       'phpversion'  => $phpversion,
       )
     );

     if($result['code'] == '200') {
         return true;
     } else {
       return false;
     }

  }

}

