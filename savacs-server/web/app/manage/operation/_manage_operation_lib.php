<?php

function createErrorMessage(Exception $e, string $position) : string {
    return sprintf(
        "%s in %s:\n%s\n\n%s",
        get_class($e),
        $position,
        $e->getMessage(),
        $e->getTraceAsString()
    );
}

function exitWithText(string $text) : void {
    $text = $text;
    require('./_template_show_text.php');
    exit;
}

function exitWithList(array $array) : void {
    $array = $array;
    require('./_template_show_list.php');
    exit;
}

function exitWithTable(array $array) : void {
    if (count($array) == 0)
        exitWithText('Array (Count: 0)');

    $rows = $array;
    $colnames = array_keys($array[0]);
    require('./_template_show_table.php');
    exit;
}

