<?php defined('C5_EXECUTE') or die(_("Access Denied."));

final class RebarLoader {
    
    /**
     * Loads all required elements of the Rebar Framework
     *  
     * @param string $pkgHandle Packge name Rebar is installed into
     */
    static function LoadFramework($pkgHandle = null) {
        
        $frameworkFiles = array('rebar_exceptions');
        
        foreach ($frameworkFiles as $aFile) {
            
            Loader::library($aFile, $pkgHandle);
        }
    }    
}