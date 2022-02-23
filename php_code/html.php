<?php
# Copyright (C) 2008 - Paul Zastoupil, Reinier Post, Mike Kaufman,
#   Vasco Costa, Marko Lindqvist
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.

function db2html($orig) {
  // Remove escaping sent by client (needed for example for handling '#')
  // Add html escaping instead.
  return htmlentities(stripslashes($orig), ENT_COMPAT, "UTF-8");
}

function addneededslashes_php($orig) {
  return addslashes($orig);
}

?>
