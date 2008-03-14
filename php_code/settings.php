<?

if (file_exists("php_code/local.php")) {
  $localsettings = fopen('php_code/local.php', 'r');
}

if ($localsettings != NULL) {
  include_once("php_code/local.php");
}

if ($metaserver_root == NULL) {
  $metaserver_root = $_SERVER[DOCUMENT_ROOT];
}

if ($version_file == NULL) {
  $versions_file = $metaserver_root . '/versions';
}

if ($fddb_default_db == NULL) {
  $fcdb_default_db = "default";
}

if ($fcdb_metaserver_db == NULL) {
  $fcdb_metaserver_db = "metaserver";
}

if ($fcdb_username == NULL) {
  $fcdb_username = "dude";
}

if ($fcdb_pw == NULL) {
  $fcdb_pw = '';
}

// Check configuration
if (! file_exists($metaserver_root . "/php_code/php_code_find.php")) {
  if ($localsettings == NULL) {
    $lspart = "local.php needed?";
  } else {
    $lspart = "error in local.php?";
  }

  $error_msg = "<table border=\"1\" style=\"font-size:xx-small\">\n" .
               "<tr><th>Metaserver installation problem.</th><tr>\n" .
               "<tr><td>" . $lspart . "</td></tr>" . 
               "<tr><td>" .
               "Please contact the maintainer" . $wmpart .
               ".</td></tr>\n</table></font>\n";
}

?>
