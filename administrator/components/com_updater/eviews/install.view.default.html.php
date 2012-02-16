<?php
    defined('_JEXEC') or die('Restricted access');
if ($message) {    
    ?>
<dl id="system-message">
<dd class="success message fade">
  <ul>
    <li><?= $message ?></li>
  </ul>

</dd>
</dl>
<?php 
} 
print $details;
?>

<hr>
<div align="center"><input type="button" value = "Return to Software List" onClick="document.location='index.php?option=com_updater&task=display;'"></div>
