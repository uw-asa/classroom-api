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

// Add meta-features
$features[] = array(
    // 'category'      => 'AV',
    'display_name'  => 'Lecture Capture',
    'slug'          => 'lecture-capture',
    'feature_id'    => implode('+', array(
        '282', # Automated Panopto Recorder
        '283', # A/V Bridge
        '297', # USB Self-Service Camera and Audio Feed
    )),
);

array_multisort(array_column($features, 'category'), SORT_ASC, SORT_NATURAL,
                array_column($features, 'display_name'), SORT_ASC, SORT_NATURAL,
                $features);


dprint_r($features, true);

if (! isset($_GET['debug']))
    exit(json_encode($features));

?>
<form method="POST"><input type="submit" name="update_cache" value="Reload Cached Entries" /></form>
<pre><?= htmlentities(json_encode($features, JSON_PRETTY_PRINT)) ?></pre>
<hr />
<pre><?= $debug_output ?></pre>
