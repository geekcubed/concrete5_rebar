<?php defined('C5_EXECUTE') or die(_("Access Denied."));


abstract class RebarAttributeValue extends AttributeValue {
    
    protected $ownerObject = null;
    protected $attributeValueTable;
    protected $attributeValueOwnerIdField;
    
    public function getAttributeValueTable() {
        return $this->attributeValueTable;
    }
    
    public function getAttributeValueIdField() {
        return $this->attributeValueOwnerIdField;
    }
    
    public function __construct() {
        
        if (empty($this->$attributeValueTable)) {
            
            throw new Exception('RebarAttributeValue Exception - 
                Attribute Value Table not declared');            
        }
        
        if (empty($this->attributeValueOwnerIdField)) {
            
            throw new Exception('RebarAttributeValue Exception - 
                OwnerId Field not declared');            
        }
    }
    
    public function setOwnerObject($obj) {
        
        $this->ownerObject = $obj;        
    }
    
    public static function getValueID($ownerID, $akID) {
        
        $db = Loader::db();
        $avID = 0;
        
        $avID = $db->GetOne("SELECT avID FROM {$this->attributeValueTable} WHERE 
            {$this->attributeValueOwnerIdField} = ? AND akID = ?",
            array($ownerID, $akID)
        );
            
        return $avID;
    }
    
    public function delete() {
        
        $db = Loader::db();
        
        $db->Execute("DELETE FROM {$this->attributeValueTable} WHERE 
        {$this->attributeValueOwnerIdField} = ? AND akID = ? AND avID = ?",
                array($this->ownerObject->getID(),
            $this->attributeKey->getAttributeKeyID(),
            $this->getAttributeValueID()
        ));
        
        // Before we run delete() on the parent object, we make sure that 
        // attribute value isn't being referenced in the table anywhere else
        $num = $db->GetOne("SELECT COUNT(avID) FROM {$this->attributeValueTable}
            WHERE avID = ?", array($this->getAttributeValueID()));
        
        if ($num < 1) {
            parent::delete();
        }
    }
    
    public function deleteAllByKey($akID) {
        
        $results = $this->db->Execute("SELECT avID FROM {$this->$attributeValueTable} 
            WHERE akID = ?", array($akID));
        
        
        
        while ($row = $rresults->FetchRow()) {
            $this->db->Execute('DELETE FROM AttributeValues WHERE avID = ?', array($row['avID']));
        }
        
        $db->Execute("DELETE FROM {$this->$attributeValueTable} 
            WHERE akID = ?", array($akID));
    }
}
    
