<?php

include(__DIR__ . '/../init.inc.php');

Pix_Setting::set('Table:DropTableEnable', true);
Entry::dropTable();
Entry::createTable();
EntryValue::dropTable();
EntryValue::createTable();

$unit_map = new StdClass;
foreach (Unit::search(1) as $unit) {
    $data = json_decode($unit->data);
    foreach ($data->year_id as $year_id2 => $id2) {
        $unit_map->{$year_id2} = $unit->id;
    }
}

$fp = gzopen(__DIR__ . '/預算案-歲出機關別預算表.csv.gz', 'r');
$cols = fgetcsv($fp);

$unit_item = new StdClass;
$names = array();
while ($rows = fgetcsv($fp)) {
    $values = array_combine($cols, $rows);
    // 3102010400 = 31 0201 04 00
    // 31 = 國務支出 (歲出政事別科目編號表)
    // 0201 = 總統府主管 (中央政府歲出機關別科目編號表)
    // 04 = 歲入為來源別子目之編號，歲出為業務計畫科目之編號。
    // 00 = 歲入為來源別細目之編號，歲出為工作計畫科目之編號。
    // 有些「00770010000」不合格的代碼，應該第 6 位多了一個 0
    if (strlen($values['科目編號']) == 11) {
        $values['科目編號'] = substr($values['科目編號'], 0, 4) . substr($values['科目編號'], 5);
    } else if (strlen($values['科目編號']) == 10) {
    } else {
        print_r($values);
        throw new Exception("科目編號不是十位數");
    }
    $year = $values['年'];
    $id = $values['科目編號'];
    $id1 = substr($id, 0, 2);
    $id2 = intval(substr($id, 2, 4));
    $id3 = substr($id, 6, 2);
    $id4 = substr($id, 8, 2);

    if (!$unit_id = $unit_map->{$year  . '-' . $id2}) {
        print_r($values);
        throw new Exception("找不到 {$year}-{$id2} 的單位");
    }
    error_log("{$year}-{$id2} => {$unit_id}");

    if ($id1 == '00' and $id3 == '00' and $id4 == '00') {
        continue;
    } else {
        if ($id3 == '00' and $id4 == '00') {
            $names = array(0, $values['科目名稱']);
            $index = 0;
        } else if ($id4 == '00') {
            $names = array(0, $names[1], $values['科目名稱']);
            $index = 1;
        } else {
            $names[3] = $values['科目名稱'];
            $index = 2;
        }


        $key = "{$unit_id}-{$names[$index]}-{$names[$index + 1]}";
        error_log($key);

        if (property_exists($unit_item, $key)) {
            $entry = $unit_item->{$key};
            $names[$index + 1] = $entry->entry_id;
            $data = json_decode($entry->data);
            $data->year_id->{"{$year}-{$id}"} = $id;
            $entry->update(array(
                'data' => json_encode($data),
            ));
        } else {
            $entry = Entry::insert(array(
                'unit_id' => $unit_id,
                'parent_id' => $names[$index],
                'name' => $names[$index + 1],
                'data' => json_encode(array('year_id' => array("{$year}-{$id}" => $id))),
            ));
            $names[$index + 1] = $entry->entry_id;
            $unit_item->{$key} = $entry;
        }
        EntryValue::insert(array(
            'entry_id' => $entry->entry_id,
            'category' => '預算案',
            'time' => $year,
            'value' => intval($values['本年預算數']),
            'data' => json_encode(array(
                '說明' => $values['說明'],
            )),
        ));
    }
}
