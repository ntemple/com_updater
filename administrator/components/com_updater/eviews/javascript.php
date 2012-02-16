<?php
// JHTML::_('behavior.modal'); 
?>
<script language="javascript" type="text/javascript">
function submitform(task, package, action, msg) {
  var form = document.adminForm;
 
  form.task.value = task;
  form.package.value = package;
  form.itype.value = action;
  form.submit();
}

function submitbutton(pressbutton) {  
  var form = document.adminForm;

  if(pressbutton == 'updatemanifest') {
    form.task.value = 'updatemanifest';
    form.redirect.value = 'display';
    form.submit();
  }

  if(pressbutton == 'gopro') {
    form.task.value = 'details';
    form.package.value = 'manifest.pro';
    form.itype.value = 7;
    form.submit();
  }
  
  if(pressbutton == 'install') {
    form.task.value = 'install';
    form.submit();
  }


  if(pressbutton == 'legacy') {
    if (confirm("Changing legacy mode may negatively affect some components.\nAre you sure you want to continue?")) {
      form.task.value = 'legacytoggle';
      form.redirect.value = 'display';
      form.submit();
    }    
  }  

  if(pressbutton == 'upgrade') {
  
    var ftp_mode = <?php echo UPDATER_FTP_ENABLED; ?>;
 
 
    $msg  = "Upgrading will overwrite ALL J! core files (including all core hacks such as JACL).\n\n";
    $msg += "  Before Proceeding:\n";
    $msg += "  - BACK UP YOUR SITE! (and be able to restore it)\n";
    $msg += "  - Be sure ALL your files are writeable\n";
    $msg += "    (chmod 0777/ 0666 or owned by websever / nobody)\n\n"; 
    if (ftp_mode) {
      $msg += " ==== WARNING: YOU ARE RUNNING IN FTP MODE ===\n\nFTP mode will probably timeout and may destroy your site.\n\n";
    }
    $msg += "You are sure you want to continue?\n";

    if (confirm($msg)) {
      form.task.value = 'upgrade';
      form.redirect.value = 'display';
      form.submit();
    }
  }

  if(pressbutton == 'noupgrade') {
    alert("Your site is running the latest available version of Joomla!\nNo need to upgrade!");
  }

}
</script>
