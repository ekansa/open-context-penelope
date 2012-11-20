<?php
class Zend_View_Helper_Dojo extends Zend_View_Helper_Placeholder_Container_Standalone
{ 
    const CDN_AOL_BASE = 'http://o.aolcdn.com/dojo/';

    const CDN_GOOGLE_BASE = 'http://ajax.googleapis.com/ajax/libs/dojo/';

    const CDN_DOJO_PATH = '/dojo/dojo.xd.js';

    public $view; 

    public function setView(Zend_View_Interface $view);

    public function enableDojo();

    public function disableDojo();

    public function isEnabled();
 
    public function requireModule($module);

    public function getModules();
 
    public function registerModulePath($path);

    public function getModulePaths();

    public function setCdnVersion($version = null);
 
    public function getCdnVersion();

    public function setCdn($cdn = 'aol');

    public function getCdn();
 
    public function setLocalPath($path);

    public function getLocalPath();

    public function useLocalPath();
 
    public function setDjConfig($option, $value);

    public function getDjConfig($option = null);
 
    public function addStyleSheetModule($module);

    public function getStyleSheetModules();
 
    public function addStyleSheet($path);

    public function getStyleSheets();

    public function addOnLoad($script);

    public function getOnLoadActions();

    public function onLoadCaptureStart($action);

    public function onLoadCaptureStop($action);

    public function dojo();

    public function __toString();
} 
