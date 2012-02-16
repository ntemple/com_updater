<?php

function com_install() {

    if (!version_compare("5", PHP_VERSION, "<")) {
      JError::raiseWarning(42, JText::_('com_updater is not compatible with PHP v4.x. Please upgrade to PHP 5.2 or greater.'));
      return false;
    }
}

