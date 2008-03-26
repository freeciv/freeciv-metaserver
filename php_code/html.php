<?php

function db2html($orig) {
  // Remove escaping sent by client (needed for example for handling '#')
  // Add html escaping instead.
  return htmlentities(stripslashes($orig));
}

function addneededslashes($orig) {
  // Only add slashes if not automagically added
  if (!get_magic_quotes_gpc()) {
    return addslashes($orig);
  }

  return $orig;
}

?>
