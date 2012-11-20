<?php
class My_Helper_FooBar
{
    public $view;

    public function fooBar($name)
    {
        return 'fooBar ' . $this->view->escape($name);
    }

    public function setView(Zend_View_Interface $view)
    {
        $this->view = $view;
    }
}
