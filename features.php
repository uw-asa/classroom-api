<?php

include('misc.php');

include('r25.php');

#Get all features in all generally-assignable classrooms
$query = array(
    'category_id' => implode(',', array(
        '186', # Type - 110 - General Classroom (Central Assignment)
        '384', # Campus - Seattle -- Upper Campus
    )),
    'scope' => 'extended',
    'include' => 'features',
);

$spaces = r25_get('spaces', $query, 86400);

$features = array();
foreach ($spaces as $space) {
    foreach ($space->feature as $featureObj) {
        $id = (int)$featureObj->feature_id;
        if (isset($features[$id]))
            continue;

        $decoded_feature = r25_decode_feature_name((string)$featureObj->feature_name);
        if ($decoded_feature['category'] == 'Hidden')
            continue;

        $features[$id] = array_merge((array)$featureObj, $decoded_feature);
    }
}

array_multisort(array_column($features, 'category'), SORT_ASC, SORT_NATURAL,
                array_column($features, 'display_name'), SORT_ASC, SORT_NATURAL,
                $features);


dprint_r($features, true);

echo json_encode($features);

if (! isset($_GET['debug']))
    exit();

?>
<br clear="all" />
<hr />
<pre style="text-align: left; color: black; background-color: white">
<?= $debug_output ?>
</pre>

<form method="POST">
 <input type="submit" name="update_cache" value="Reload Cached Entries" />
</form>
