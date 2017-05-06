<?php
# Copyright (C) 2008 - Paul Zastoupil, Reinier Post, Mike Kaufman,
#   Vasco Costa, Marko Lindqvist
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.

include_once("php_code/flagspecs.php");

function img_html($pic, $alt, $extra, $width) {
  global $pic_paths;
  $paths = array_keys($pic_paths);
  $pic_found = NULL;

  foreach ($paths as $filepath) {
    $pic_file = $filepath . $pic;

    if ($pic_found == NULL && file_exists($pic_file)) {
      $pic_found = $filepath;
      break;
    }
  }

  if ($pic_found != NULL) {
    echo "<IMG SRC=\"/" . $pic_paths[$pic_found] . $pic . "\"";
    if ($width != NULL) {
      echo " WIDTH=\"$width\"";
    }
    if ($alt != NULL) {
      echo " ALT=\"$alt\"";
    }
    if ($extra != NULL) {
      echo " " . "$extra";
    }
    echo ">";
  } else if ($alt != NULL) {
    echo " $alt ";
  }
}

function flag_html($tag) {
  global $flags;

  if (!array_key_exists($tag, $flags)) {
    $pic = "unknown.png";
  } else {
    $pic = $flags["$tag"];
  }

  img_html("flags/" . $pic, NULL, NULL, NULL);
}

?>
