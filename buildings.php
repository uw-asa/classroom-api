<?php

$json = isset($_GET['json']) && ($_GET['json'] || $_GET['json'] === '');

include('misc.php');

include('r25.php');

header("Access-Control-Allow-Origin: https://academictechnologies.asa.uw.edu");

$query25 = array();

$query25['category_id'] = implode(',', array(
    '186', # Type - 110 - General Classroom (Central Assignment)
    '384', # Campus - Seattle -- Upper Campus
));

dprint(print_r($query25, true));

$spaces = r25_get('spaces', $query25);

#dprint(print_r($spaces, true));

$buildings = array();
foreach ($spaces as $space) {
    $building = trim(substr($space->space_name, 0, 4));
    if ($building == 'EE1' || $building == 'EEB')
        $building = 'ECE';

    if ($room = r25_decode_formal_name((string)$space->formal_name))
        $buildings[$building] = $room['building'];
    else
        $buildings[$building] = (string)$space->formal_name;
}
#$buildings = array_filter($buildings, function($b){ return ($b->Site->Code == 'SEA_MN'); });

#uasort($buildings, function($b1, $b2){ return strcmp($b1->LongName, $b2->LongName); });
natsort($buildings);

$results = array();
foreach ($buildings as $code => $name) {

  if ($json) {
    if (empty($name)) {
      $results[$code]['building_name'] = $code;
    } else {
      $results[$code]['building_name'] = $name;
    }
  }
  else
  {
    echo '<option value="' . $code . '">' . $name . ' (' . $code . ")</option>\n";
#    print_r($building);
  }

}

if (! $json)
    exit();

if (! isset($_GET['debug']))
    exit(json_encode($results));

?>
<form method="POST"><input type="submit" name="update_cache" value="Reload Cached Entries" /></form>
<pre><?= htmlentities(json_encode($results, JSON_PRETTY_PRINT)) ?></pre>
<hr />
<pre><?= $debug_output ?></pre>
