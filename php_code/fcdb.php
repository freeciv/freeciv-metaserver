<?php

$fcdb_sel = "MySQL";

$fcdb_default_db = "default";
$fcdb_metaserver_db = "metaserver";
$fcdb_username = "dude";

function fcdb_error_handler($errno, $errstr) {

}

// please use these instead of fcdb_connect wherever possible

function fcdb_default_connect() {
  global $fcdb_default_db, $fcdb_username;

  return fcdb_connect($fcdb_default_db, $fcdb_username);
}

function fcdb_metaserver_connect() {
  global $fcdb_metaserver_db, $fcdb_username;

  return fcdb_connect($fcdb_metaserver_db, $fcdb_username);
}

$dbhost = '';

function fcdb_connect($db, $un) {
  global $fcdb_sel, $fcdb_conn;
  global $dbhost;

  $ok = true;

  set_error_handler("fcdb_error_handler");
  switch ($fcdb_sel) {
    case "PostgreSQL":
      $fcdb_conn = pg_Connect("dbname=$db port=5432 user=$un");
      if (!$fcdb_conn) {
	fcdb_error("I cannot make a connection to the database server.");
      }
      $ok = false;
      break;
    case "MySQL":
      $fcdb_conn = mysql_pconnect($dbhost, $un, '');
      if (!$fcdb_conn) {
	fcdb_error("I cannot make a connection to the database server.");
	$ok = false;
      }
      else
      {
        $ok = mysql_select_db($db, $fcdb_conn);
        if (!$ok) {
	  fcdb_error("I cannot open the database.");
        }
      }
      break;
  }
  restore_error_handler();

  return $ok;
}

function fcdb_exec($stmt) {
  global $fcdb_sel, $fcdb_conn;
  set_error_handler("fcdb_error_handler");
  switch ($fcdb_sel) {
    case "PostgreSQL":
      $res = pg_Exec($fcdb_conn, $stmt);
      if (!$res) {
	fcdb_error("I cannot run a statement: '$stmt'.");
      }
      break;
    case "MySQL":
      $res = mysql_query($stmt, $fcdb_conn);
      if (!$res) {
	fcdb_error("I cannot run a statement: '$stmt'.");
      }
      break;
  }
  restore_error_handler();
  return ($res);
}

function fcdb_query_single_value($stmt) {
  global $fcdb_sel, $fcdb_conn;
  set_error_handler("fcdb_error_handler");
  switch ($fcdb_sel) {
    case "PostgreSQL":
      $res = pg_Exec($fcdb_conn, $stmt);
      if (!$res) {
	fcdb_error("I cannot run a query: '$stmt'.");
      }
      $val = pg_Result($res, 0, 0);
      break;
    case "MySQL":
      $res = mysql_query($stmt, $fcdb_conn);
      if (!$res) {
	fcdb_error("I cannot run a query: '$stmt'.");
      }
      $val = mysql_result($res, 0, 0);
      break;
  }
  restore_error_handler();
  return ($val);
}

function fcdb_num_rows($res) {
  global $fcdb_sel, $fcdb_conn;
  set_error_handler("fcdb_error_handler");
  switch ($fcdb_sel) {
    case "PostgreSQL":
      $rows = pg_NumRows($res);
      break;
    case "MySQL":
      $rows = mysql_num_rows($res);
      break;
  }
  restore_error_handler();
  return ($rows);
}

function fcdb_fetch_array($res, $inx) {
  global $fcdb_sel, $fcdb_conn;
  set_error_handler("fcdb_error_handler");
  switch ($fcdb_sel) {
    case "PostgreSQL":
      $arr = pg_Fetch_Array($res, $inx);
      break;
    case "MySQL":
      $ok = mysql_data_seek($res, $inx);
      if (!$ok) {
	fcdb_error("I couldn't seek to given row.");
      }
      $arr = mysql_fetch_array($res);
      break;
  }
  restore_error_handler();
  return ($arr);
}

function fcdb_mktime($time_str) {
  global $fcdb_sel;
  set_error_handler("fcdb_error_handler");
  switch ($fcdb_sel) {
    case "PostgreSQL":
      $part = split("[- :]", $time_str);
      $time_sec =
	mktime($part[3], $part[4], $part[5], $part[1], $part[2], $part[0]);
      break;
    case "MySQL":
      $ok =
	ereg("([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})",
	      $time_str, $part);
      if ($ok) {
	$time_sec =
	  mktime($part[4], $part[5], $part[6], $part[2], $part[3], $part[1]);
      } else {
	$part = split("[- :]", $time_str);
	$time_sec =
	  mktime($part[3], $part[4], $part[5], $part[1], $part[2], $part[0]);
      }
      break;
  }
  restore_error_handler();
  return ($time_sec);
}

function fcdb_error($what_error) {
  global $webmaster;
  echo "<table border=\"1\" style=\"font-size:xx-small\">\n";
  echo "<tr><th>$what_error</th><tr>\n";
  echo "<tr><td>" . mysql_error() . "</td></tr>";
  echo "<tr><td>";
  echo "Please contact the maintainer";
  if ($webmaster != "") {
    echo ", $webmaster";
  }
  echo ".</td></tr>\n";
  echo "</table></font>\n";
  //exit;  // no, don't!
}

?>
