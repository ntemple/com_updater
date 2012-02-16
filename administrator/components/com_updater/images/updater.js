function submitform(task, package, action) {
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
  
  if(pressbutton == 'legacy') {
    if (confirm("Changing legacy mode may negatively affect some components.\nAre you sure you want to continue?")) {
      form.task.value = 'legacytoggle';
      form.redirect.value = 'display';
      form.submit();
    }
  }  
}
