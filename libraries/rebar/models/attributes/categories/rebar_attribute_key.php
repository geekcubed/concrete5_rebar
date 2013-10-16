<?php defined('C5_EXECUTE') or die(_("Access Denied."));

abstract class RebarAttributeKey extends AttributeKey {
    
    protected $searchIndexFieldDefinition;
    protected $searchIndexTable;
    protected $attributeKeyTable;
    protected $attributeCategoryHandle;
    protected $attributeValueClass;
    protected $db = null;
    
    public function getIndexedSearchTable() {
        return $this->searchIndexTable;
    }

    public function getSearchIndexFieldDefinition() {
        return $this->searchIndexFieldDefinition;
    }
    
    public function getAttributeKeyDisplayOrder() {
        return $this->displayOrder;
    }
    
    public function getAttributeCategoryHandle() {
        return $this->attributeCategoryHandle;
    }
        
    public function __construct() {
        
        if(empty($this->searchIndexFieldDefinition)) {            
            throw new RebarRuntimeException(
                RebaRuntimeException::MISCONFIGURED_INSTANCE, 0,
                new  Exception('SearchIndexField not declared'));
        }
        
        if(empty($this->searchIndexTable)) {            
            throw new RebarRuntimeException(
                RebaRuntimeException::MISCONFIGURED_INSTANCE, 0,
                new  Exception('SearchIndexTable not declared'));
        }
        
        if(empty($this->attributeKeyTable)) {            
            throw new RebarRuntimeException(
                RebaRuntimeException::MISCONFIGURED_INSTANCE, 0,
                new  Exception('AttributeKeyTable not declared'));
        }
        
        if(empty($this->attributeCategoryHandle)) {            
            throw new RebarRuntimeException(
                RebaRuntimeException::MISCONFIGURED_INSTANCE, 0,
                new  Exception('AttributeCategoryHandle not declared'));
        }
        
        if(empty($this->attributeValueClass)) {            
            throw new RebarRuntimeException(
                RebaRuntimeException::MISCONFIGURED_INSTANCE, 0,
                new  Exception('AttributeValueClass not declared'));
        }
        
        $this->db = Loader::db();
    }
    
    public function load($akID) {
        
        parent::load($akID);
        
        $row = $this->db->GetRow("SELECT displayOrder FROM " . $this->attributeKeyTable . " 
            WHERE akID = ?", array($akID));
        
        $this->setPropertiesFromArray($row);
    }
    
    public function getByID($akID) {
    
        if (!isset($this)) {
            throw new RebarRuntimeException(
                    RebarRuntimeException::NONE_STATIC_METHOD);
        }
        
        $objName = get_called_class();
        $ak = new $objName();
        $ak->load($akID);
        
        if ($ak->getAttributeKeyID() > 0) {
            return $ak;
        }
    }
    
    public function getByHandle($akHandle) {
            
        if (!isset($this)) {
            throw new RebarRuntimeException(
                    RebarRuntimeException::NONE_STATIC_METHOD);
        }
        
        $objName = get_called_class();
        $ak = new $objName;
        
        $akID = $this->db->GetOne("SELECT akID FROM AttributeKeys 
            INNER JOIN AttributeKeyCategories ON AttributeKeys.akCategoryID = 
            AttributeKeyCategories.akCategoryID WHERE akHandle = ? AND 
            akCategoryHandle = ?", 
                array($akHandle, $ak->getAttributeCategoryHandle()));
       
        $ak->load($akID);
        
        if ($ak->getAttributeKeyID() > 0) {
            return $ak;
        }
    }
    
    public function getAttributeValue($avID, $method = 'getValue') {
    
        //$objName = get_called_class();
        $av = forward_static_call(array($this->attributeValueClass, 'getByID'),
                $avID);
        
        $av->setAttributeKey($this);
        return call_user_func_array(array($av, $method), array());
    }
    
    protected function saveAttribute(AttributedRebarModel $ownerObj, 
            $value = false) {
       
        $av = $ownerObj->getAttributeValueObject($this, true);
        
        parent::saveAttribute($av, $value);
        
        $this->db->Replace(
                $av->getAttributeValueTable(), 
                array(
                    $av->getAttributeValueIdField() => $ownerObj->getID(),
                    'akID' => $this->getAttributeKeyID(),
                    'avID' => $av->getAttributeValueID()
                ),
                array($av->getAttributeValueIdField(), 'akID'));
        
        unset($av);
    }
    
    public function add($type, $args, $pkg = false) {
            
        if (!isset($this)) {
            throw new RebarRuntimeException(
                    RebarRuntimeException::NONE_STATIC_METHOD);
        }
        
        $ak = parent::add($this->attributeCategoryHandle, $type, $args, $pkg);

        extract($args);

        $displayOrder = 
                $this->db->GetOne("SELECT max(displayOrder) FROM " . $this->attributeKeyTable);
        if (!$displayOrder) {
            $displayOrder = 0;
        }
        $displayOrder++;

        $v = array($ak->getAttributeKeyID(), $displayOrder);
        $db = Loader::db();
        $db->Execute("INSERT INTO " . $this->attributeKeyTable . " 
            (akID, displayOrder) VALUES (?, ?)", $v);

        //Reload & Return
        $objClass = get_called_class();
        $nak = new $objClass;
        $nak->load($ak->getAttributeKeyID());
        
        return $nak;
    }
    
    public function update($args) {
        $ak = parent::update($args);
    }

    public function delete() {
        
        parent::delete();
        
        $this->db->Execute("DELETE FROM " . $this->attributeKeyTable . 
            " WHERE akID = ?", array($this->getAttributeKeyID()));
        
        $valueObj = new $this->attributeValueClass();
        $valueObj->deleteAllByKey($this->getAttributeKeyKeyID());
    }
    
    /**
     * This is a Static method inherited from the base Concrete5 class.
     * Rebar can't support this mode of access, as we need to dynamically create
     * instances of the child class, which late-static binding won't support.
     * 
     * @see RebarAttributeKey::getDefaultList()
     * @deprecated since version 1.0
     * @param string $akCategoryHandle
     * @param array $filters
     * @throws RebarRuntimeException 
     */
    public static function getList($akCategoryHandle, $filters = array()) {
        
        throw new RebarRuntimeException(
                    RebarRuntimeException::STATIC_NOT_SUPPORTED
                );
    }
    
    public function getDefaultList($filters = array()) {
        
        return parent::getList($this->attributeCategoryHandle, $filters);        
    }
    
    public function getColumnHeaderList() {
        
        $list = parent::getList($this->getAttributeCategoryHandle(),
                array('akIsColumnHeader' => 1));
        
        usort($list, array(get_called_class(), 'sortListByDisplayOrder'));
        
        return $list;
    }
    
    public function getSearchableIndexedList() {
        
        return parent::getList($this->getAttributeCategoryHandle(), 
                array('akIsSearchableIndexed' => 1));
    }
    
    public function getSearchableList() {
        
        return parent::getList($this->getAttributeCategoryHandle(),
                array('akIsSearchable' => 1));
    }
    
    public function sortListByDisplayOrder($a, $b) {
        
        if ($a->getAttributeKeyDisplayOrder() == $b->getAttributeKeyDisplayOrder()) {
            return 0;
        } else {
            return ($a->getAttributeKeyDisplayOrder() < $b->getAttributeKeyDisplayOrder()) ? -1 : 1;
        }
    }
    
    public function updateAttributesDisplayOrder($ats) {
        
        if (!isset($this)) {
            throw new RebarRuntimeException(
                    RebarRuntimeException::NONE_STATIC_METHOD);
        }
        
        $objClass = get_called_class();
        $ak = new $objClass();
        
        for ($i = 0; $i < count($ats); $i++) {
            
            $oak = $ak->getByID($ats[$i]);
            $oak->refreshCache();
            
            $db->query("UPDATE " . $this->attributeKeyTable . 
                " SET displayOrder = {$i} WHERE akID = ?", array($ats[$i]));
        }
    }
}

/*public function getAttributes($ownerPartID, $method = 'getValue') {
        
        $values = $this->db->GetAll(
                "SELECT akID, avID from KiaPartAttributeValues where kiaPartID = ?", array($kiaPartID));
        $avl = new AttributeValueList();
        
        foreach ($values as $val) {
            $ak = KiaPartAttributeKey::getByID($val['akID']);
            if (is_object($ak)) {
                $value = $ak->getAttributeValue($val['avID'], $method);
                $avl->addAttributeValue($ak, $value);
            }
        }
        
        return $avl;
    }*/
    
  
    

    

    

    