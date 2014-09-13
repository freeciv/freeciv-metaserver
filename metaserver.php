<?php

/* do we want debug output to stderr?
 * This is very heavy so never leave it on in production
 */
$debug=0;

$included_fallback_find_file_php = false;
$config_problem = false;

// include the php-code finder
ini_set("include_path", ini_get("include_path") . ":" . $_SERVER["DOCUMENT_ROOT"]);

include_once("php_code/settings.php");

if ($error_msg != NULL) {
  $config_problem = true;
}

if (! $config_problem) {
  include_once("php_code/php_code_find.php");
  // includes for support routines
  include_once(php_code_find("fcdb.php"));
  include_once(php_code_find("versions_file.php"));
  include_once(php_code_find("img.php"));
  include_once(php_code_find("html.php"));

  fcdb_metaserver_connect();
}

$fullself="http://".$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"];

$posts = array(
  "host",
  "port",
  "bye",
  "version",
  "patches",
  "capability",
  "state",
  "ruleset",
  "message",
  "type",
  "serverid",
  "available",
  "humans",
  "vn",
  "vv",
  "plrs",
  "plt",
  "pll",
  "pln",
  "plf",
  "plu",
  "plh",
  "dropplrs",
  /* URL line cgi parameters */
  "server_port",
  "client",
  "client_cap",
  "rss"
);

/* This is where we store what variables we can collect from the server
 * If we want to add variables, they need to be here, and new columns
 * need to be added to the database. They will also be sent to the client */
$sqlvars = array(
  "version",
  "patches",
  "capability",
  "state",
  "ruleset",
  "message",
  "type",
  "available",
  "humans",
  "serverid"
);

/* this little block of code "changes" the namespace of the variables 
 * we got from the $_REQUEST variable to a local scope */
$assoc_array = array();
foreach($posts as $val) {
  if (isset($_REQUEST[$val])) {
    $assoc_array[$val] = $_REQUEST[$val];
  }
}
extract($assoc_array);


if ( isset($port) ) {
  /* All responses to the server will be text */
  header("Content-Type: text/plain; charset=\"utf-8\"");

  /* garbage port */
  if (!is_numeric($port) || $port < 1024 || $port > 65535) {
    print "exiting, garbage port \"$port\"\n";
    exit(1);
  }

  /* This is to check if the name they have supplied matches their IP */
    /* Maybe they have a proxy they can't get around */
  if ( isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ) {
    $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
  } elseif ( isset($_SERVER["HTTP_CLIENT_IP"]) ) {
    $ip = $_SERVER["HTTP_CLIENT_IP"];
  } else {
    $ip = $_SERVER["REMOTE_ADDR"];
  }

  if (gethostbyname($host) != $ip) {
    $host = @gethostbyaddr($ip);
  }

  /* is this server going away? */
  if (isset($bye)) {
    $stmt="delete from servers where host=\"$host\" and port=\"$port\"";
    print "$stmt\n";
    $res = fcdb_exec($stmt);
    $stmt="delete from variables where hostport=\"$host:$port\"";
    print "$stmt\n";
    $res = fcdb_exec($stmt);
    $stmt="delete from players where hostport=\"$host:$port\"";
    print "$stmt\n";
    $res = fcdb_exec($stmt);
    print "Thanks, please come again!\n";
    exit(0); /* toss all entries and exit */
  }

  if (isset($message)) {
    $message = addneededslashes($message); /* escape stuff to go into the database */
  }
  if (isset($type)) {
    $type = addneededslashes($type); /* escape before inserting to the database */
  }
  if (isset($serverid)) {
    $serverid = addneededslashes($serverid); /* escape stuff to go into the database */
  }


  /* lets get the player information arrays if we were given any */
  $playerstmt = array();
  if (isset($plu)) {
    for ($i = 0; $i < count($plu); $i++) { /* run through all the names */
      $ins = "insert into players set hostport=\"$host:$port\", ";

      if (isset($plu[$i]) ) {
        $plu[$i] = addneededslashes($plu[$i]);
        $ins .= "user=\"$plu[$i]\", ";
      }
      if (isset($pll[$i]) ) {
        $pll[$i] = addneededslashes($pll[$i]);
        $ins .= "name=\"$pll[$i]\", ";
      }
      if (isset($pln[$i]) ) {
        $pln[$i] = addneededslashes($pln[$i]);
        $ins .= "nation=\"$pln[$i]\", ";
      }
      if (isset($plf[$i]) ) {
        $plf[$i] = addneededslashes($plf[$i]);
        $ins .= "flag=\"$plf[$i]\", ";
      }
      if (isset($plt[$i]) ) {
        $plt[$i] = addneededslashes($plt[$i]);
        $ins .= "type=\"$plt[$i]\", ";
      }
      $ins .= "host=\"$plh[$i]\"";
      /* an array of all the sql statements; save actual db access to the end */
      debug("\nINS = $ins\n\n");
      array_push($playerstmt, $ins);
    }
  }

  /* delete this variables that this server might have already set. */
  $stmt="delete from variables where hostport=\"$host:$port\"";
  $res = fcdb_exec($stmt);

  /* lets get the variable arrays if we were given any */
  $variablestmt = array();
  if (isset($vn)) {
    for ($i = 0; $i < count($vn); $i++) { /* run through all the names */
      $vn[$i] = addneededslashes($vn[$i]);
      $vv[$i] = addneededslashes($vv[$i]);
      $ins = "insert into variables set hostport=\"$host:$port\", ";
      $ins .= "name=\"$vn[$i]\", ";
      $ins .= "value=\"$vv[$i]\"";
      /* an array of all the sql statements; save actual db access to the end */
      array_push($variablestmt, $ins);
    }
  }

  $stmt = "select * from servers where host=\"$host\" and port=\"$port\"";
  $res = fcdb_exec($stmt);

  /* do we already have an entry for this host:port combo? */
  if (fcdb_num_rows($res) == 1) {
    /* so this is an update */
    $string = array();
    $stmt = "update servers set ";

    /* iterate through the vars to build a list of things to update */
    foreach ($sqlvars as $var) {
      if (isset($assoc_array[$var])) {
        array_push($string, "$var=\"$assoc_array[$var]\"");
      }
    }

    /* we always want to update the timestamp */
    array_push($string, "stamp=now() ");

    $stmt .= join(", ", $string); /* put them all together */
    $stmt .= "where host=\"$host\" and port=\"$port\"";
  } else {
    /* so this is a brand new server and is an insert */
    $string = array();

    foreach($sqlvars as $var) {
      if (isset($assoc_array[$var])) {
        array_push($string, "$var=\"$assoc_array[$var]\"");
      }
    }

    /* we always want to update the timestamp */
    array_push($string, "stamp=now() ");

    $stmt= " insert into servers set host=\"$host\", port=\"$port\", ";
    $stmt .= join(", ", $string); /* put them all together */
  }

  print "$stmt\n"; /* server statement */

  /* Do all the processing above, we now hit the database */
  $res = fcdb_exec($stmt);

  for ($i = 0; $i < count($variablestmt); $i++) {
    print "$variablestmt[$i]\n";
    $res = fcdb_exec($variablestmt[$i]);
  }

  /* if we have a playerstmt array we want to zero out the players
   * and if the server wants to explicitly tell us to drop them all */
  if (count($playerstmt) > 0 || isset($dropplrs)) { 
    $delstmt = "delete from players where hostport=\"$host:$port\"";

    print "$delstmt\n";

    $res = fcdb_exec($delstmt);

    /* if dropplrs=1 then set available back to 0 */
    if (isset($dropplrs)) {
      $avstmt = "update servers set available=0, humans=-1 where host=\"$host\" and port=\"$port\"";
      $res = fcdb_exec($avstmt);
    }

    for ($i = 0; $i < count($playerstmt); $i++) {
      print "$playerstmt[$i]\n";
      $res = fcdb_exec($playerstmt[$i]);
    }
  }

  /* We've done the database so we're done */

} elseif ( isset($client_cap) || isset($client) ) {
  global $freeciv_versions;
  $output = "";
  $output .= "[versions]\n";
  $output .= "latest_stable=\"" . version_by_tag("stable") . "\"\n";
  $verkeys = array_keys($freeciv_versions);
  foreach ($verkeys as $key) {
    $output .= "$key=\"" . version_by_tag("$key") . "\"\n";
  }
  $stmt="select * from servers where type is NULL order by host,port asc";
  $res = fcdb_exec($stmt);
  $nr = fcdb_num_rows($res);
  $nservers=0;
  if ( $nr > 0 ) {
    for ($inx = 0; $inx < $nr; $inx++) {
      $row = fcdb_fetch_array($res, $inx);
      // debug("db = \"".$row["capability"]." \" vs \"$client_cap\"\n");
      /* we only want to show compatable servers */
      if ( $client == "all"  || 
           (has_all_capabilities(mandatory_capabilities($row["capability"]),$client_cap) &&
            has_all_capabilities(mandatory_capabilities($client_cap),$row["capability"]) ) ) {
        $output .= "[server$nservers]\n";
        $nservers++;
        $output .= sprintf("host = \"%s\"\n", $row["host"]);
        $output .= sprintf("port = \"%s\"\n", $row["port"]);
        /* the rest of the vars from the database */
        foreach($sqlvars as $var) {
          $noquote=str_replace("\"","",$row[$var]); /* some " were messing it up */
          $output .= "$var = \"$noquote\"\n";
        }

        $stmt="select * from players where hostport=\"".$row["host"].":".$row["port"]."\" order by name";
        $res1 = fcdb_exec($stmt);
        $nr1 = fcdb_num_rows($res1);
        $output .= "nplayers = \"$nr1\"\n";
        if ($nr1 > 0) {
          $output .= "player = { \"name\", \"user\", \"nation\", \"type\", \"host\"\n";
          for ($i = 0; $i < $nr1; $i++) {
            $prow = fcdb_fetch_array($res1, $i);
            $output .= sprintf(" \"%s\", ", stripslashes($prow["name"]));
            $output .= sprintf("\"%s\", ", stripslashes($prow["user"]));
            $output .= sprintf("\"%s\", ", stripslashes($prow["nation"]));
            $output .= sprintf("\"%s\", ", stripslashes($prow["type"]));
            $output .= sprintf("\"%s\"\n", stripslashes($prow["host"]));
          }
          $output .= "}\n";
        }

        $stmt="select * from variables where hostport=\"".$row["host"].":".$row["port"]."\"";
        $res2 = fcdb_exec($stmt);
        $nr2 = fcdb_num_rows($res2);
        if ($nr2 > 0) {
          $output .= "vars = { \"name\", \"value\"\n";
          for ($i = 0; $i < $nr2; $i++) {
            $vrow = fcdb_fetch_array($res2, $i);
            $output .= sprintf(" \"%s\", ", $vrow["name"]);
            $output .= sprintf("\"%s\"\n ", $vrow["value"]);
          }
          $output .= "}\n";
        }
        $output .= "\n";
      }
    }
    $output .= "[main]\n";
    $output .= "nservers = $nservers\n\r\n";

    /* All responses to the client will be in Freeciv's ini format */
    //header("Content-Type: text/x-ini");
    header("Content-Type: text/plain; charset=\"utf-8\"");

    header("Content-Length: " . strlen($output));
    print $output;
  }
} elseif ( isset($rss) ) {
  header("Content-Type: text/xml; charset=\"utf-8\"");
  print "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";
  print "<rss version=\"2.0\">\n";
  print "  <channel>\n<docs>http://blogs.law.harvard.edu/tech/rss</docs>\n";
  print "    <title>Freeciv Metaserver</title>\n";
  print "    <link>$fullself</link>\n";
  print "    <description>Freeciv Servers</description>\n";
  if ($webmaster_email != NULL) {
    print "    <webMaster>$webmaster_email</webMaster>\n";
  }
  print "    <lastBuildDate>".date('r')."</lastBuildDate>\n";
  $stmt="select host,port,version,patches,state,ruleset,message,unix_timestamp(stamp) as date,available,humans,serverid from servers where type is NULL order by host,port asc";
  $res = fcdb_exec($stmt);
  $nr = fcdb_num_rows($res);
  if ( $nr > 0 ) {
    for ( $inx = 0; $inx < $nr; $inx++ ) {
      $row = fcdb_fetch_array($res, $inx);
      $link="$fullself?server_port=".$row["host"].":".$row["port"];
      $stmt="select * from players where hostport=\"".$row['host'].":".$row['port']."\"";
      $res1 = fcdb_exec($stmt);
      $players=fcdb_num_rows($res1);
      $title=implode(" ",array($row["host"],$row["port"],$row["version"],$row["state"],$players));
      print "    <item>\n";
      print "    <title>$title</title>\n";
      print "    <link>$link</link>\n";
      print "    <description>\n";
      print "      Host: ".$row["host"]."<br /> ";
      print "      Port: ".$row["port"]."<br /> ";
      print "      Version: ".$row["version"]."<br /> ";
      print "      Patches: ".stripslashes($row["patches"])."<br /> ";
      print "      State: ".$row["state"]."<br /> ";
      print "      Ruleset: ".$row["ruleset"]."<br />";
      print "      Message: ".stripslashes($row["message"])."<br /> ";
      print "      Players: ".$players."<br /> ";
      print "      Available: ".$row["available"]."<br /> ";
      if ($row["humans"] != "-1") {
        print "      Humans: ".$row["humans"]."<br /> ";
      }
      print "      Serverid: ".stripslashes($row["serverid"])."<br /> ";
      print "    </description>\n";
      print "    <pubDate>".date('r',$row["date"])."</pubDate>\n";
      print "    <guid>$link</guid>\n";
      print "    </item>\n";
    }
  }
  print "  </channel>\n";
  print "</rss>\n";

} else {

  header("Content-Type: text/html; charset=\"utf-8\"");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta http-equiv="expires" content="0"/>
<title>Freeciv Metaserver</title>
<link rel="alternate" type="application/rss+xml" title="RSS" href="<? print $fullself; ?>?rss=1" />
<style type="text/css">
body {
  background-color: #fffaf0;
}

table {
  margin-left: auto;
  margin-right: auto;
  text-align: center;
}

th {
  background-color: #f0e0e0;
}
tr {
  background-color: #f0f0e0;
}

div {
  text-align: center;
}

.left {
  text-align: left;
}

.center {
  text-align: center;
}
</style>
</head>

<body>
<div>
<?php

  if ($error_msg != NULL) {
    echo $error_msg;
  } else {
    if (isset($server_port)) {
      print "<h1>Freeciv server " . db2html($server_port) . "</h1><br />\n";
      $port = substr(strrchr($server_port, ":"), 1);
      $host = substr($server_port, 0, strlen($server_port) - strlen($port) - 1);
      $stmt = "select * from servers where host=\"$host\" and port=\"$port\"";
      $res = fcdb_exec($stmt);
      $nr = fcdb_num_rows($res);
      if ( $nr != 1 ) {
        print "Cannot find the specified server";
      } else {
        $row = fcdb_fetch_array($res, 0);
        $msg = db2html($row["message"]);
        print "<table><tr><th>Version</th><th>Patches</th><th>Capabilities</th>";
        print "<th>State</th><th>Ruleset</th>";
        print "<th>Server ID</th></tr>\n";
        print "<tr><td>";
        print db2html($row["version"]);
        print "</td><td>";
        print db2html($row["patches"]);
        print "</td><td>";
        print db2html($row["capability"]);
        print "</td><td>";
        print db2html($row["state"]);
        print "</td><td>";
        print db2html($row["ruleset"]);
        print "</td><td>";
        print db2html($row["serverid"]);
        print "</td></tr>\n</table></p>\n";
        if ($msg != "") {
          print "<p>";
          print "<table><tr><th>Message</th></tr>\n";
          print "<tr><td>" . $msg . "</td></tr>";
          print "</table></p>\n";
        }
        $stmt="select * from players where hostport=\"$server_port\" order by name";
        $res = fcdb_exec($stmt);
        $nr = fcdb_num_rows($res);
        if ( $nr > 0 ) {
          print "<p><div><table style=\"width: 60%;\">\n";
          print "<tr><th class=\"left\">Leader</th><th>Nation</th>";
          print "<th>Flag</th><th>User</th><th>Type</th><th>Host</th></tr>\n";
          for ( $inx = 0; $inx < $nr; $inx++ ) {
            $row = fcdb_fetch_array($res, $inx);
            print "<tr><td class=\"left\">";
            print db2html($row["name"]);
            print "</td><td>";
            print db2html($row["nation"]);
            print "</td><td>";
            flag_html("f." . $row["flag"]);
            print "</td><td>";
            print db2html($row["user"]);
            print "</td><td>";
            print db2html($row["type"]);
            print "</td><td>";
            print db2html($row["host"]);
            print "</td></tr>\n";
          }
          print "</table></div><p>\n";
        } else {
          print "<p>No players</p>\n";
        }
        $stmt="select * from variables where hostport=\"$server_port\"";
        $res = fcdb_exec($stmt);
        $nr = fcdb_num_rows($res);
        if ( $nr > 0 ) {
          print "<div><table>\n";
          print "<tr><th class=\"left\">Option</th><th>Value</th></tr>\n";
          for ( $inx = 0; $inx < $nr; $inx++ ) {
            $row = fcdb_fetch_array($res, $inx);
            print "<tr><td>";
            print db2html($row["name"]);
            print "</td><td>";
            print db2html($row["value"]);
            print "</td></tr>\n";
          }
          print "</table></div>";
          print "<P><a href=\"".$_SERVER["PHP_SELF"]."\">Return to main Page</a>";
        }

      }
    } else {
      print "<h1>$metaserver_header</h1><br />\n";
      $stmt="select host,port,version,patches,state,message,unix_timestamp()-unix_timestamp(stamp),available,humans from servers where type is NULL order by host,port asc";
      $res = fcdb_exec($stmt);
      $nr = fcdb_num_rows($res);
      if ( $nr > 0 ) {
        print "<table>\n";
        print "<tr><th class=\"left\">Host</th><th>Port</th>";
        print "<th>Version</th><th>Patches</th><th>State</th>";
        print "<th>Players</th><th>Message</th><th>Last Update</th>";
        print "<th>Players Available</th>";
        print "<th>Human Players</th></tr>\n";
        for ( $inx = 0; $inx < $nr; $inx++ ) {
          $row = fcdb_fetch_array($res, $inx);
          print "<tr><td class=\"left\">";
          print "<a href=\"".$_SERVER["PHP_SELF"]."?server_port=";
          print db2html($row["host"]);
          print ":";
          print db2html($row["port"]);
          print "\">";
          print db2html($row["host"]);
          print "</a>";
          print "</td><td>";
          print db2html($row["port"]);
          print "</td><td>";
          print db2html($row["version"]);
          print "</td><td>";
          print db2html($row["patches"]);
          print "</td><td>";
          print db2html($row["state"]);
          print "</td><td>";
          $stmt="select * from players where hostport=\"".$row['host'].":".$row['port']."\"";
          $res1 = fcdb_exec($stmt);
          print fcdb_num_rows($res1);
          print "</td><td style=\"width: 30%\">";
          print db2html($row["message"]);
          print "</td><td>";
          $time_sec = $row["unix_timestamp()-unix_timestamp(stamp)"];
          $last_update = sprintf("%ss", $time_sec);
          if ($time_sec >= 60) {
            $last_update = sprintf("%sm", floor($time_sec/60));
          }
          print $last_update;
          print "</td><td>";
          print db2html($row["available"]);
	  print "</td><td>";
          $humcount = $row["humans"];
          if ($humcount == -1) {
            print "Unknown";
          } else {
            print $humcount;
          }
          print "</td></tr>\n";
        }
        print "</table>";
      } else {
        print "<h2>No servers currently listed</h2>";
      }
?>
<br />
<br />
<p class="center"><em>

<?php
echo "<a href=\"./metaserver.php?rss=1\">";
img_html("rss.png", "RSS1", "BORDER=0", NULL);
echo "</a>";
?>

<br />
Latest stable release is <?php echo version_by_tag("stable"); ?>.
<br />

<?php
      if ($bugs_html != NULL) {
        echo "Please report any bugs to " . $bugs_html . ".";
      }
      echo "<br /><br />";
      echo "Start your freeciv-server with the -m option if you want it listed ";
      if ($metaURL != NULL) {
        echo "on the metaserver.<BR>\n";
        echo "To contact this particular metaserver, use also \"-M $metaURL\"";
      } else {
        echo "here.";
      }
      echo "</em></p>";
    }
    echo "<br /><br /><p class=\"center\">";
    img_html("hr.gif", "--------", NULL, NULL);
    echo "</p><br /><br />";
  }
?>

</div>
</body>
</html>
<?php 
} 

/* This returns a list of the capabilities that are mandatory in a given capstring
 * i.e. those that begin with a + 
 */
function mandatory_capabilities($capstr) {
  $return=array();
  $elements=preg_split("/\s+/",$capstr);
  foreach ($elements as $element) {
    if ( preg_match("/^\+/", $element) ) {
      array_push($return, ltrim($element,"+"));
    }
  }
  return($return);
}

/* This returns true if a cap is contained in capstr
 */
function has_capability($cap,$capstr) {
  $elements=preg_split("/\s+/",$capstr);
  foreach ($elements as $element) {
    $element=ltrim($element,"+"); /*drop + if there, because it wont match with it*/
    // debug("  comparing \"$cap\" to \"$element\"\n");
    if ( $cap == $element) {
      return(TRUE);
    } 
  }
  return(FALSE);
}

/* This returns true if all caps are contained in capstr
 */
function has_all_capabilities($caps,$capstr) {
  foreach ($caps as $cap) {
    if ( ! has_capability($cap,$capstr) ) {
      return(FALSE);
    }
  }
  return(TRUE);
}

function debug($output) {
  global $debug;
  if ( $debug ) {
    $stderr=fopen("php://stderr","a");
    fputs($stderr, $output);
    fclose($stderr);
  }
}
      
?>
