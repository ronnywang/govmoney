<?php

class IndexController extends Pix_Controller
{
    public function indexAction()
    {
    }

    public function unitAction()
    {
        list(, /*index*/, /*unit*/, $unit_name) = explode('/', $this->getURI());
        $unit_name = urldecode($unit_name);
        if (!$unit = Unit::find_by_name($unit_name)) {
            return $this->redirect('/');
        }
        $this->view->unit = $unit;
    }
}
