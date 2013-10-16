<?php defined('C5_EXECUTE') or die(_("Access Denied."));

abstract class AttributedRebarModel extends RebarModel {
    
    protected static $attributeKeyClass;
    protected static $attributeValueClass;
    
    public function __construct($populateFieldMeta = false) {
                
        parent::__construct($populateFieldMeta);
        
        if (empty($this->attributeKeyClass)
                || empty($this->attributeValueClass)){
            
            throw new Exception('AttributedRebarModel Exception - 
                Attribute Class(es) not set');           
        }
    }
    
    protected function getAttributeKeyObj($ak) {
        
        if (!is_object($ak)) {            
            $ak = forward_static_call(
                    array(self::$attributeKeyClass, 'getByHandle'), $ak);
        }
        
        return $ak;
    }    
    
    public function setAttribute($ak, $value) {
                 
        if (!is_object($ak)) {            
            $ak = $this->getAttributeKeyObj($ak);
        }        
        
        $ak->setAttribute($this, $value);
        $this->reindex();        
    }
    
    public function getAttribute($ak, $displayMode = false) {
        
        if (!is_object($ak)) {            
            $ak = $this->getAttributeKeyObj($ak);
        }
        
        if (is_object($ak)) {
            
            $av = $this->getAttributeValueObject($ak);
            
            if (is_object($av)) {
                return $av->getValue($displayMode);
            }
        }
    }
    
    public function getAttributes($method = 'getValue') {
        
        $attrValObj = new self::$attributeValueClass();
        
        $values = $this->db->GetAll(
                "SELECT akID, avID FROM {$attrValObj->getAttributeValueTable()} 
                    WHERE {$attrValObj->getAttributeValueIdField()} = ?", 
                            array($this->getID()));
        
        $avl = new AttributeValueList();
        
        foreach ($values as $val) {
           
            $ak = forward_static_call(array(self::$attributeKeyClass, 'getByID'),
                    $val['akID']);
            
            if (is_object($ak)) {
                $value = $ak->getAttributeValue($val['avID'], $method);
                $avl->addAttributeValue($ak, $value);
            }
        }
        
        return $avl;
    }
    
    
    public function getAttributeValueObject($ak, $createIfNotFound = false) {
        
        $av = false;
        $v = array();
        
        $avID = forward_static_call(
                    array($self::$attributeValueClass, 'getValueID'),
                $this->getID(), $ak->getAttributeKeyID());
        
       if ($avID > 0) {
           
           forward_static_call(
                    array(self::$attributeValueClass, 'getByID'), $avID);
            if (is_object($av)) {
                
                $av->setOwnerObject($this);
                $av->setAttributeKey($ak);
            }
        }

        if ($createIfNotFound) {
           if ((!is_object($av)) || ($av->getValueIDUseCount() > 1)) {
                $av = $ak->addAttributeValue();
            }
        }

        return $av;
    }  
    
    abstract function reindex();
    /*
    public function reindex() {
        
        Cache::disableLocalCache();
        
        $attributes = forward_static_call(
                array($this->attributeKeyClass, 'getAttributes'), 
                $this->getID(), 'getSearchIndexValue');
        
        //$this->db->Execute
        
        
        Cache::enableLocalCache();
        
    }*/
}