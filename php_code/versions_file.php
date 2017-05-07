<?php
# Copyright (C) 2007 - Paul Zastoupil, Reinier Post, Marko Lindqvist
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.

include_once("php_code/versions.php");

function version_by_tag($tag) {
  global $freeciv_versions;

  if (!array_key_exists($tag, $freeciv_versions)) {
    return "unknown";
  }

  return $freeciv_versions["$tag"];
}

function comment_by_tag($tag) {
  global $version_comments;

  if (!array_key_exists($tag, $version_comments)) {
    return "unknown";
  }

  return $version_comments["$tag"];
}

?>
