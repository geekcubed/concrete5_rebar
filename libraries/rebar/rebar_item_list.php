<?php defined('C5_EXECUTE') or die(_("Access Denied."));
/**
 * Rebar Item List
 * Extension of core DatabaseItemList to handle model features in Rebar
 *
 * @package Rebar
 * @subpackage Core
 * @copyright (c) 2014, Ian Stapleton
 */
abstract class RebarItemList extends DatabaseItemList {

    protected $modelType;
    protected $autoSortColumns = array();
    protected $itemsPerPage = 10;
    protected $attributeClass;
    protected $attributeFilters = array();
    protected $modelObj;
    protected $db;
    protected $showDeleted = false;

    /**
     * Constructor - ensures thats the abstract implmentation is correctly configured. 
     * If the implementation model has attributes, then it loads the key ready for use.
     * 
     * @throws RebarRuntimeException If the implmentation's $modelType is not a RebarModel
     */
    public function __construct() {

        if (empty($this->modelType)) {

            throw new RebarRuntimeException(
            RebarRuntimeException::MISCONFIGURED_INSTANCE, 0, new Exception('Model Class not declared'));
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
    
    /**
     * If the base model type supports the "mark deleted" pattern, then deleted 
     * records will be excluded by default. 
     * 
     * <strong>Call this *BEFORE* a call to get / getTotal</strong>
     */
    public function includeDeleted() {
        
        $this->showDeleted = true;
    }

    /**
     * Sets core filters and joins.
     * 
     * If the implemented class is for a model with Attributes, then a JOIN is 
     * constructed to include the attribute value search table.
     * 
     * Automatically excludes deleted records if the model supports the "show deleted"
     * pattern (and a call to includeDeleted() hasn't been made).
     */
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
            }

            if ($this->modelObj->getMarkDeleted()  && !$this->showDeleted) {
                $this->filter('ot.deleted', 0, '=');
            }

            $this->queryCreated = 1;
        }
    }
    
    /**
     * Builds the base SELECT statement
     */
    protected function setBaseQuery() {

        $this->setQuery('SELECT DISTINCT ot.' . $this->modelObj->getPrimaryKeyColumm()
                . ' FROM ' . $this->modelObj->getTable() . ' ot');
    }

    /**
     * Magic method for supprting filterByColumn and sortByColumn dynamic methods
     * 
     * @param string $nm method name called
     * @param array $a arguments passed
     */
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

    /**
     * Generates and executes the SQL SELECT query. For each returned record ID,
     * statically calls getByID() on the base modelType class
     * 
     * @param int $itemsToGet Number per page. Default to 0 (all)
     * @param int  $offset Paging offset
     * @return array of modelType objects
     */
    public function get($itemsToGet = 0, $offset = 0) {

        $items = array();
        $this->createQuery();
        $r = parent::get($itemsToGet, intval($offset));

        foreach ($r as $row) {
            $item = forward_static_call(array($this->modelType, 'getByID'), $row[$this->modelObj->getPrimaryKeyColumm()]);

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Executes a COUNT to returnt the total number of records matched by the
     * set filters. Used for paging etc.
     * 
     * @return int total matched records
     */
    public function getTotal() {

        $this->createQuery();
        return parent::getTotal();
    }

    /**
     * Method to filter explicitly by ID. Required as RebarModel supports dynamic 
     * (runtime) declaration of the primary key column.
     * 
     * @param string $objID
     * @param string $comparison
     */
    public function filterByID($objID, $comparison = '=') {
        $this->filter($this->modelObj->getPrimaryKeyColumm(), $objID, $comparison);
    }

    /**
     * @todo Needs to be coded!
     * @param sting $keywords
     */
    public function filterByKeywords($keywords) {

        /* $db = Loader::db();
          $keywordsExact = $db->quote($keywords);
          $qkeywords = $db->quote('%' . $keywords . '%'); */
        /* $keys = KiaPartAttributeKey::getSearchableIndexedList();
          $attribsStr = '';
          foreach ($keys as $ak) {
          $cnt = $ak->getController();
          $attribsStr.=' OR ' . $cnt->searchKeywords($keywords);
          } */

        //$this->filter(false, "(kp.description LIKE $qkeywords)");
        /* ' OR kp.extra LIKE ' . $qkeywords . $attribsStr . ')'); */
    }

}
