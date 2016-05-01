<?php
$row = 1;
if (($handle = fopen("sample.txt", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 0, " ")) !== FALSE) {
        $num = count($data);
        //echo "$num fields in line $row: \n";
        $row++;
        for ($c=0; $c < $num; $c++) {
            echo $data[$c] . "\n";
        }
    }
    fclose($handle);
}

