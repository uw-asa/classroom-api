<?php

include('misc.php');

include('r25.php');

#Get all categories in all generally-assignable classrooms
$query = array(
    'category_id' => implode(',', array(
        '186', # Type - 110 - General Classroom (Central Assignment)
        '384', # Campus - Seattle -- Upper Campus
    )),
    'scope' => 'extended',
    'include' => 'categories',
);

$spaces = r25_get('spaces', $query, 86400);

$categories = array();
foreach ($spaces as $space) {
    foreach ($space->category as $categoryObj) {
        $id = (int)$categoryObj->category_id;
        if (isset($categories[$id]))
            continue;

        #$categories[$id] = array_merge((array)$categoryObj, r25_decode_category_name((string)$categoryObj->category_name));
        $categories[$id] = (array)$categoryObj;
    }
}

#array_multisort(array_column($categories, 'category'), SORT_ASC, SORT_NATURAL,
#                array_column($categories, 'display_name'), SORT_ASC, SORT_NATURAL,
#                $categories);


dprint_r($categories, true);

echo json_encode($categories);

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
