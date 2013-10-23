<?php defined('C5_EXECUTE') or die(_("Access Denied."));

abstract class RebarAttributedModel extends RebarModel {
    
    protected static $attributeKeyType;
    protected static $attributeValueType;
    
    public function __construct($populateFieldMeta = false) {
                
        parent::__construct($populateFieldMeta);
        
        if (empty(static::$attributeKeyType)
                || empty(static::$attributeValueType)){
            
            throw new RebarRuntimeException(
                RebarRuntimeException::MISCONFIGURED_INSTANCE, 0,
                new  Exception('Attribute Class(es) not declared'));
        }
    }
    
    public static function getAttributeKeyType() {
        
        $rtn = static::$attributeKeyType;
        return $rtn;
        
    }
    
    public static function getAttributeValueType() {
        
        return static::$attributeValueType;
        
    }
    
    protected function getAttributeKeyObj($ak) {
        
        if (!is_object($ak)) {            
            $ak = forward_static_call(
                    array(static::$attributeKeyType, 'getByHandle'), $ak);
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
        
        $attrValObj = new static::$attributeValueType();
        
        $values = $this->db->GetAll(
                "SELECT akID, avID FROM {$attrValObj->getAttributeValueTable()} 
                    WHERE {$attrValObj->getAttributeValueIdField()} = ?", 
                            array($this->getID()));
        
        $avl = new AttributeValueList();
        
        foreach ($values as $val) {
           
            $ak = forward_static_call(array(static::$attributeKeyType, 'getByID'),
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
                    array(static::$attributeValueType, 'getValueID'),
                $this->getID(), $ak->getAttributeKeyID());
        
       if ($avID > 0) {
           
           forward_static_call(
                    array(static::$attributeValueType, 'getByID'), $avID);
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