<?php

class Unit extends Pix_Table
{
    public function init()
    {
        $this->_name = 'unit';
        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['name'] = array('type' => 'varchar', 'size' => 32);
        $this->_columns['data'] = array('type' => 'text');

        $this->addIndex('name', array('name'), 'unique');
    }
}
