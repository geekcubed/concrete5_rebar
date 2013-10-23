<?php defined('C5_EXECUTE') or die(_("Access Denied."));

abstract class RebarItemList extends DatabaseItemList {
    
    protected $modelType;   
    protected $autoSortColumns = array();
    protected $itemsPerPage = 10;
    
    protected $attributeClass;
    protected $attributeFilters = array();
    protected $modelObj;
    protected $db;
    
    public function __construct() {
        
        if (empty($this->modelType)) {
            
             throw new RebarRuntimeException(
                RebarRuntimeException::MISCONFIGURED_INSTANCE, 0,
                new  Exception('Model Class not declared'));         
        }   
                
        $this->modelObj = new $this->modelType();
        if (!($this->modelObj instanceof RebarModel)) {
            
            throw new RebarRuntimeException(
                RebarRuntimeException::INCORRECT_REFERENCE_TYPE);
            
        }
        
        if ($this->modelObj instanceof RebarAttributedModel) {
            
            $this->attributeClass = forward_static_call(
                    array($this->modelType, 'getAttributeKeyType'));
        }
        
        $this->db = Loader::db();
    }

    protected function createQuery() {
        
        if (!$this->queryCreated) {
            $this->setBaseQuery();
            
            if ($this->modelObj instanceOf RebarAttributedModel) {
                //Join the attribute search table   
                $ak = new $this->attributeClass();
                $searchJoinSQL = "LEFT JOIN " . $ak->getIndexedSearchTable() . 
                        " aksia ON (aksia." . $ak->getSearchIndexPkID() . " = ot." .
                        $this->modelObj->getPrimaryKeyColumm() . ")";
                    
                $this->setupAttributeFilters($searchJoinSQL);
                    //forward_static_call(array)
                    //$this->setupAttributeFilters("LEFT JOIN KiaPartSearchIndexAttributes kpsia ON (kpsia.kiaPartID = kp.kiaPartID)");
                    
                
            }
            
            $this->queryCreated = 1;
        }
    }

    protected function setBaseQuery() {
        
        $this->setQuery('SELECT DISTINCT ot.'.$this->modelObj->getPrimaryKeyColumm()
                . ' FROM ' .$this->modelObj->getTable() . ' ot');
    }

    /* magic method for filtering and ordering by attributes. */

    public function __call($nm, $a) {
        
//filterByXxxxxxxx
        if (substr($nm, 0, 8) == 'filterBy') {
            $txt = Loader::helper('text');
            $attrib = $txt->uncamelcase(substr($nm, 8));
            if (count($a) == 2) {
                $this->filterByAttribute($attrib, $a[0], $a[1]);
            } else {
                $this->filterByAttribute($attrib, $a[0]);
            }
        }
        
//sortByXxxxxxxx
        if (substr($nm, 0, 6) == 'sortBy') {
            $txt = Loader::helper('text');
            $attrib = $txt->uncamelcase(substr($nm, 6));
            $this->sortBy($attrib, $a[0]);
        }
    }

    // Returns an array of event objects based on current filter settings
    public function get($itemsToGet = 0, $offset = 0) {
        
        $items = array();
        $this->createQuery();
        $r = parent::get($itemsToGet, intval($offset));
        
        foreach ($r as $row) {
            $item = forward_static_call(array($this->modelType, 'getByID'), 
                    $row[$this->modelObj->getPrimaryKeyColumm()]);
            
            $items[] = $item;
        }
        
        return $items;
    }

    public function getTotal() {
        
        $this->createQuery();
        return parent::getTotal();        
    }

    public function filterByID($objID, $comparison = '=') {
        $this->filter($this->modelObj->getPrimaryKeyColumm(), $objID, $comparison);
    }

    public function filterByKeywords($keywords) {
        
        /*$db = Loader::db();
        $keywordsExact = $db->quote($keywords);
        $qkeywords = $db->quote('%' . $keywords . '%');*/
        /*$keys = KiaPartAttributeKey::getSearchableIndexedList();
        $attribsStr = '';
        foreach ($keys as $ak) {
            $cnt = $ak->getController();
            $attribsStr.=' OR ' . $cnt->searchKeywords($keywords);
        }*/
        
        //$this->filter(false, "(kp.description LIKE $qkeywords)");
                /*' OR kp.extra LIKE ' . $qkeywords . $attribsStr . ')');*/
        
    }

}