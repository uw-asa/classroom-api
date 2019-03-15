<?php

$json = isset($_GET['json']) && ($_GET['json'] || $_GET['json'] === '');

include('../misc.php');

include('r25.php');

$query25 = array();

$query25['category_id'] = '186,384'; # Type - 110 - General Classroom (Central Assignment), Campus - Seattle -- Upper Campus

dprint(print_r($query25, true));

$spaces = r25_get('spaces', $query25);

#dprint(print_r($spaces, true));

$buildings = array();
foreach ($spaces as $space) {
    #dprint($space->formal_name);

    $room = r25_decode_formal_name($space->formal_name);

    $building = trim(substr($space->space_name, 0, 4));
    if ($building == 'EE1' || $building == 'EEB')
        $building = 'ECE';

    $buildings[$building] = $room['building'];
}
#$buildings = array_filter($buildings, function($b){ return ($b->Site->Code == 'SEA_MN'); });

#uasort($buildings, function($b1, $b2){ return strcmp($b1->LongName, $b2->LongName); });
natsort($buildings);

$results = array();
foreach ($buildings as $code => $name) {

  if ($json) {
    $results[$code]['building_name'] = $name;
  }
  else
  {
    echo '<option value="' . $code . '">' . $name . ' (' . $code . ")</option>\n";
#    print_r($building);
  }

}

if ($json)
{

  echo json_encode($results);

}

if (! isset($_GET['debug']))
    exit();

?>
<br clear="all" />
<hr />
<form method="POST">
 <input type="submit" name="update_cache" value="Reload Cached Entries" />
</form>
<pre style="text-align: left; color: black; background-color: white">
<?= $debug_output ?>
</pre>
