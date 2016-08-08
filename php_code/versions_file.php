<?php

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
