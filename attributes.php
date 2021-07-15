<?php

include('misc.php');

include('r25.php');

#Get all custom attributes in all generally-assignable classrooms
$query = array(
               'category_id' => '384', # Type - 110 - General Classroom (Central Assignment), Campus - Seattle -- Upper Campus
               'scope' => 'extended',
               'include' => 'attributes',
               );

$spaces = r25_get('spaces', $query, 86400);

#dprint_r($spaces, true);

$attributes = array();
foreach ($spaces as $space) {
    foreach ($space->custom_attribute as $attributeObj) {
        $id = (int)$attributeObj->attribute_id;
        if (isset($attributes[$id]))
            continue;

        $attributes[$id] = (array)$attributeObj;
    }
}

#array_multisort(array_column($attributes, 'category'), SORT_ASC, SORT_NATURAL,
#                array_column($attributes, 'display_name'), SORT_ASC, SORT_NATURAL,
#                $attributes);


dprint_r($attributes, true);

echo json_encode($attributes);

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
