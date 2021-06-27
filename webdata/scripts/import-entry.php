<?php

include(__DIR__ . '/../init.inc.php');

Pix_Setting::set('Table:DropTableEnable', true);
Entry::dropTable();
EntryValue::dropTable();
Entry::createTable();
EntryValue::createTable();

$fp = gzopen(__DIR__ . '/預算案-歲出機關別預算表.csv.gz', 'r');
$cols = fgetcsv($fp);

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
    $id3 = substr($id, 6, 2);
    $id4 = substr($id, 8, 2);

    if ($id1 == '00' and $id3 == '00' and $id4 == '00') {
        continue;
    } else {
        $entry_id = Entry::getEntryIdOrCreateIt($year, $id, $values['科目名稱']);
        error_log("{$year}-{$id} {$values['科目名稱']} => $entry_id");
        try {
            EntryValue::insert(array(
                'entry_id' => $entry_id,
                'category' => '預算案',
                'time' => $year,
                'value' => intval($values['本年預算數']),
                'data' => json_encode(array(
                    '說明' => $values['說明'],
                )),
            ));
        } catch (Pix_Table_DuplicateException $e) {
            print_r($values);
            print_r(EntryValue::search(array('entry_id' => $entry_id, 'category' => '預算案', 'time' => $year))->toArray());
            print_r(Entry::find($entry_id)->toArray());
            exit;
        }
    }
}
