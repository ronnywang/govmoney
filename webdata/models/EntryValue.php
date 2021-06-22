<?php

class EntryValue extends Pix_Table
{
    public function init()
    {
        $this->_name = 'entry_value';
        $this->_primary = array('entry_id', 'category', 'time');

        $this->_columns['entry_id'] = array('type' => 'int');
        $this->_columns['category'] = array('type' => 'varchar', 'size' => 16);
        $this->_columns['time'] = array('type' => 'int');
        $this->_columns['value'] = array('type' => 'int');
        $this->_columns['data'] = array('type' => 'text');
    }
}
