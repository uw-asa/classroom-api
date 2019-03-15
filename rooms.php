<?php

$starttime = microtime(true);

include('../misc.php');

include('r25.php');

$query25 = array();

if (isset($_GET['capacity']) && intval($_GET['capacity']) > 0) {
        $query25['min_capacity'] = intval($_GET['capacity']);
}

if (isset($_GET['active'])) {
        $query25['category_id'] = '384'; # Campus - Seattle -- Upper Campus
        $query25['formal_name'] = 'Seattle-';
}

$feature_ids = array();
if (isset($_GET['feature'])) {
    foreach(array_keys($_GET['feature']) as $feature_id)
        $feature_ids[] = $feature_id;
}


$query25['feature_id'] = implode(',', $feature_ids);

#$includes[] = 'categories';
$includes[] = 'features';
#$includes[] = 'attributes';

$query25['scope'] = 'extended';
$query25['include'] = implode('+', $includes);

dprint(print_r($query25, true));

$spaces = r25_get('spaces', $query25)->space;

#dprint(print_r($spaces, true));

foreach($spaces as $space) {
    $building = trim(substr($space->space_name, 0, 4));
    if ($building == 'EE1' || $building == 'EEB')
        $building = 'ECE';

    $room_number = trim(substr($space->space_name, 4));

    $room = $building . ' ' . $room_number;

    dprint(print_r($space, true));

    $results[$room]['room_name'] = (string)$space->formal_name;
    $results[$room]['room_number'] = $room_number;
    $results[$room]['capacity'] = (string)$space->max_capacity;

    foreach($space->feature as $feature)
        $results[$room]['feature'][(int)$feature->feature_id] = (string)$feature->feature_name;
}



if ( isset($_GET['debug']) ) {
?>
<form method="POST">
 <input type="submit" name="update_cache" value="Reload Cached Entries" />
</form>
<?php
}

dprint(count($results) . " results");

echo json_encode($results);

if (! isset($_GET['debug']))
    exit();

?>
<br clear="all" />
<hr />
<pre style="text-align: left; color: black; background-color: white">
<?= $debug_output ?>
</pre>
