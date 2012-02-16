<?php
  defined('_JEXEC') or die();  
  global $isnid;
  global $mid;
    
  $url = "http://www.intellispire.com/network/order/banner.php?zone=$zoneid&sku=$sku&ref=$package&isnid=$isnid1&mid=$machineid";
  $remote_data = isJInstallerHelper::url_retrieve_curl($url);
  if ($remote_data) print $remote_data;
  