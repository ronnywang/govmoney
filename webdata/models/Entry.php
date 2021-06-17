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

        $this->addIndex('unit_parent_name', array('unit_id', 'parent_id', 'name'), 'unique');
    }
}


