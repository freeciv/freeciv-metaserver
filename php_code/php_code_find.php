<?php
# Copyright (C) 2007 - Paul Zastoupil, Reinier Post, Marko Lindqvist
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.

// Return path to PHP code file.
// (Search for "local" file; fallback to "default" file.)

  // includes for support routines
  //include_once("php_code/fallback_find_file.php");
  include_once($metaserver_root . '/php_code/fallback_find_file.php');

function php_code_find($name, $reldir = ".") {

  $file = dirname($_SERVER['SCRIPT_FILENAME']) . "/$reldir/php_code/$name";
  if (my_file_exists($file)) {
    return $file;
  }

  $file = dirname(__FILE__) . "/$name";
  if (my_file_exists($file)) {
    return $file;
  }

  return $_SERVER[DOCUMENT_ROOT]."/php_code/$name";
}

?>
