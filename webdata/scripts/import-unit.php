<?php

include(__DIR__ . '/../init.inc.php');

Pix_Setting::set('Table:DropTableEnable', true);
Unit::dropTable();
Unit::createTable();

$fp = gzopen(__DIR__ . '/預算案-歲出機關別預算表.csv.gz', 'r');
$cols = fgetcsv($fp);
$year_id_name = new StdClass;

while ($rows = fgetcsv($fp)) {
    $values = array_combine($cols, $rows);
    // 3102010400 = 31 0201 04 00
    // 31 = 國務支出 (歲出政事別科目編號表)
    // 0201 = 總統府主管 (中央政府歲出機關別科目編號表)
    // 04 = 歲入為來源別子目之編號，歲出為業務計畫科目之編號。
    // 00 = 歲入為來源別細目之編號，歲出為工作計畫科目之編號。
    if (strlen($values['科目編號']) == 11) {
        $values['科目編號'] = substr($values['科目編號'], 0, 4) . substr($values['科目編號'], 5);
    } else if (strlen($values['科目編號']) == 10) {
    } else {
        print_r($values);
        exit;
    }
    $id = $values['科目編號'];
    $id1 = substr($id, 0, 2);
    $id2 = intval(substr($id, 2, 4));
    $id3 = substr($id, 6, 2);
    $id4 = substr($id, 8, 2);
    if ($id1 == '000' and $id3 == '00' and $id4 == '00') {
        $key = $values['年'] . ' ' . $id2;


        if (!$u = Unit::find_by_name($values['科目名稱'])) {
            $u = Unit::insert(array(
                'name' => $values['科目名稱'],
                'data' => '{"year_id":{},"parent":0}',
            ));
        }
        $year_id_name->{$key} = $u->id;

        $data = json_decode($u->data);
        $data->year_id->{$values['年']} = $id2;
        if ($id2 % 100 != 0 and $data->parent == 0) {
            $data->parent = $year_id_name->{$values['年'] . ' ' . floor($id2 / 100) * 100};
        }
        $u->update(array('data' => json_encode($data)));
    }
}
