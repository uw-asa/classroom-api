<?php

ini_set('memory_limit', '32M');

if (isset($_GET['debug']))
    ini_set('display_errors', 1);

function dprint($text)
{
  global $debug_output;
  $debug_output .= "$text\n";
}

function dprint_r($array)
{
    dprint(print_r($array, true));
}

function ddie($status=NULL)
{
    global $debug_output;

    echo $status;
?>
<br clear="all" />
<hr />
<pre style="text-align: left; color: black; background-color: white">
<?= $debug_output ?>
</pre>
<?php
     die();
}

?>
