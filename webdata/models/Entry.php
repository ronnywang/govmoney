<?php

class Entry extends Pix_Table
{
    public function init()
    {
        $this->_name = 'entry';
        $this->_primary = 'entry_id';

        $this->_columns['entry_id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['unit_id'] = array('type' => 'int');
        $this->_columns['parent_id'] = array('type' => 'int');
        $this->_columns['name'] = array('type' => 'varchar', 'size' => 64);
        $this->_columns['data'] = array('type' => 'text');

        $this->addIndex('unit_parent_name', array('unit_id', 'parent_id', 'name'), 'unique');
    }

    protected static $_year_id_map = null;
    protected static $_unit_map = null;

    public static function getEntryIdOrCreateIt($year, $budgetno, $name)
    {
        if (is_null(self::$_year_id_map)) {
            self::$_year_id_map = new StdClass;
            foreach (Entry::search(1) as $entry) {
                $data = json_decode($entry->data);
                foreach ($data->year_id as $year_id => $id) {
                    self::$_year_id_map->{$year_id} = $entry->entry_id;
                }
            }
        }

        if (property_exists(self::$_year_id_map, "{$year}-{$budgetno}")) {
            return self::$_year_id_map->{"{$year}-{$budgetno}"};
        }

        if (is_null(self::$_unit_map)) {
            self::$_unit_map = new StdClass;
            foreach (Unit::search(1) as $unit) {
                $data = json_decode($unit->data);
                foreach ($data->year_id as $year_id2 => $id2) {
                    self::$_unit_map->{$year_id2} = $unit->id;
                }
            }
        }

        if (strlen($budgetno) == 11) {
            $budgetno = substr($budgetno, 0, 4) . substr($budgetno, 5, 6);
        }
        $id1 = substr($budgetno, 0, 2);
        $id2 = substr($budgetno, 2, 4);
        $id3 = substr($budgetno, 6, 2);
        $id4 = substr($budgetno, 8, 2);

        if (!$unit_id = self::$_unit_map->{$year . '-' . intval($id2)}) {
            throw new Exception("找不到 {$year}-{$id2} 的單位");
        }

        if ($id1 == '00' and $id3 == '00' and $id4 == '00') {
            throw new Exception("無法增加 00XX0000 的 Entry");
        }

        if ($id3 == '00' and $id4 == '00') {
            $parent_no = null;
        } else if ($id4 == '00') {
            $parent_no = "{$id1}{$id2}0000";
        } else {
            $parent_no = "{$id1}{$id2}{$id3}00";
        }

        $parent_entry_id = 0;
        if (!is_null($parent_no)) {
            $parent_entry_id = self::$_year_id_map->{"{$year}-{$parent_no}"};
            if (!$parent_entry_id) {
                throw new Exception("在找 parent 時，找不到 {$year}-{$parent_no}");
            }
        }

        if ($entry = Entry::search(array(
            'parent_id' => $parent_entry_id,
            'unit_id' => $unit_id,
            'name' => $name,
        ))->first()) {
            $data = json_decode($entry->data);
            $data->year_id->{"{$year}-{$budgetno}"} = $budgetno;
            $entry->update(array(
                'data' => json_encode($data),
            ));
        } else {
            $entry = Entry::insert(array(
                'unit_id' => $unit_id,
                'parent_id' => $parent_entry_id,
                'name' => $name,
                'data' => json_encode(array('year_id' => array("{$year}-{$budgetno}" => $budgetno))),
            ));
        }
        self::$_year_id_map->{"{$year}-{$budgetno}"} = $entry->entry_id;
        return $entry->entry_id;
    }
}


