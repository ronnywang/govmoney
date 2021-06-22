<?php

include(__DIR__ . '/../init.inc.php');

$year_id_map = new StdClass;
foreach (Entry::search(1) as $entry) {
    $data = json_decode($entry->data);
    foreach ($data->year_id as $year_id => $id) {
        $year_id_map->{$year_id} = $entry->entry_id;
    }
}
$fp = gzopen('決算-機關別.csv.gz', 'r');
$columns = fgetcsv($fp);
while ($rows = fgetcsv($fp)) {
    $values = array_combine($columns, $rows);
    //年,款,項,目,節,科目編號,科目名稱,原預算數,預算增減數,預算合計,實現數,應付數,保留數,決算合計,比較增減數,說明
    $id = "{$values['年']}-{$values['科目編號']}";
    if (!property_exists($year_id_map, $id)) {
        echo $id . ' ' .  $values['科目名稱']  . "\n";
        continue;
    }
    $c ++;
    $entry_id = $year_id_map->{$id};
    $year = $values['年'];
    try {
    EntryValue::insert(array(
        'entry_id' => $entry_id,
        'category' => '決算',
        'time' => $year,
        'value' => intval($values['決算合計'] / 1000),
        'data' => json_encode(array(
            '說明' => $values['說明'],
        )),
    ));
    } catch (Exception $e) {
    }
}

$fp = gzopen('法定預算-歲出機關別預算表.csv.gz', 'r');
$columns = fgetcsv($fp);
while ($rows = fgetcsv($fp)) {
    $values = array_combine($columns, $rows);
    // 年,款,項,目,節,科目編號,科目名稱,本年預算數,上年預算數,前年預算數,說明
    $id = "{$values['年']}-{$values['科目編號']}";
    if (!property_exists($year_id_map, $id)) {
        echo $id . ' ' .  $values['科目名稱']  . "\n";
        continue;
    }
    $entry_id = $year_id_map->{$id};
    $year = $values['年'];
    try {
    EntryValue::insert(array(
        'entry_id' => $entry_id,
        'category' => '法定預算',
        'time' => $year,
        'value' => intval($values['本年預算數']),
        'data' => json_encode(array(
            '說明' => $values['說明'],
        )),
    ));
     } catch (Exception $e) {
     }
}

