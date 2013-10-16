<?php defined('C5_EXECUTE') or die(_("Access Denied."));

final class RebarLoader {
    
    /**
     * Loads all required elements of the Rebar Framework
     *  
     * @param string $pkgHandle Packge name Rebar is installed into
     */
    static function LoadFramework($pkgHandle = null) {
        
        $frameworkFiles = array(
            'rebar_exceptions', 
            'kohana_validation', 
            'models/attributes/categories/rebar_attribute_key',            
            'models/attributes/categories/rebar_attribute_value',
            'models/rebar_model',
            'models/attributed_rebar_model',
            'controllers/rebar_controller'
        );
        
        foreach ($frameworkFiles as $aFile) {
            
            Loader::library('rebar/'.$aFile, $pkgHandle);
        }
    }    
}
