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
    
    function delete() {
        
        parent::delete();
        
        //And Remove search values
        $ak = new static::$attributeKeyType();
        $ak->removeRecordFromIndex($this->getID());
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
    
    public function setAttribute($ak, $value = null) {
                 
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
           
            $av = forward_static_call(
                    array(static::$attributeValueType, 'getByID'), $avID);
            
            if (is_object($av)) {
                
                $av->setOwnerObject($this);
                $av->setAttributeKey($ak);
            }
        }

        if ($createIfNotFound) {
           if ((!is_object($av)) /*|| ($av->getValueIDUseCount() > 1)*/) {
                $av = $ak->addAttributeValue();
            }
        }

        return $av;
    }  
    
    protected function reindex() {
        
        //Stop stupidity 
        if ($this->getID()) {
            //Turn off the caching so we get a proper flush & reindex
            Cache::disableLocalCache();

            //Load the AtrributeKey for this object
            $ak = new static::$attributeKeyType();

            //1> Clear out the old values
            $ak->removeRecordFromIndex($this->getID());

            //2> Get a list of attributes for this record
            $attrValues = $this->getAttributes('getSearchIndexValue');

            //3> Column Headers for query
            $searchableAttrs = array($ak->getSearchIndexPkID() => $this->getID());

            //3> A dummy query we need for some reason
            $rs = $this->db->Execute("SELECT * FROM {$ak->getIndexedSearchTable()} "
                . "WHERE {$ak->getSearchIndexPkID()} = -1");

            //Reindex
            $ak->addRecordToIndex($searchableAttrs, $attrValues, $rs);

            //Finally, re-enable the cache
            Cache::enableLocalCache();
        }
        
    }
}