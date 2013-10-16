<?php defined('C5_EXECUTE') or die(_("Access Denied."));

abstract class RebarModel extends Object {
    
    protected $table;
    protected $pkID = 'id';
    ///TODO - FieldMeta needs to be profiled to see if a static property would
    //enable some duplicate DB calls to be removed (hopefully the DB layer will
    //cache this but you never know)
    protected $fields = null;
    protected $db = null;
    protected $populateFieldMeta = false;
    
    public abstract function delete();   
    public abstract function validate(&$data);
    
    public function __construct($populateFieldMeta = false) {
        
        if (empty($this->table)) {
            
            throw new Exception('RebarModel Exception - table not set');            
        }
        
        $this->populateFieldMeta = $populateFieldMeta;
        $this->db = Loader::db();
        
        //Auto populate fields if required
        if ($this->populateFieldMeta && empty($this->fields)) {            
            $this->loadFieldMeta();
        }
    }
    
    public function getID() {
        
        return $this->__get($this->$pkID);
    }
    
    public static function getByID($id) {
        
        $db = Loader::db();
        $obj = null;
        $objClass = get_called_class();
        
        $data = $db->getRow("SELECT * FROM " . self::$table . " WHERE " . self::$pkID . " = ?", 
                array($id));
        
        if (!empty($data)) {
            
            $obj = new $objClass();
            
            $obj->setPropertiesFromArray($data);
        }
        
        return is_a($obj, $objClass) ? $obj : false;
    }
    
    public static function getByField($fieldName, $fieldValue) {
        
        $db = Loader::db();
        $obj = null;
        $objClass = get_called_class();
        
        $data = $db->getRow("SELECT * FROM " . self::$table . " WHERE {$fieldName} = ?", 
                array($fieldValue));
        
        if (!empty($data)) {
            
            $obj = new $objClass();
            
            $obj->setPropertiesFromArray($data);
        }
        
        return is_a($obj, $objClass) ? $obj : false;        
    }
    
    public function save($data) {

        $record = $this->recordFromData($data);

        if ($this->isNewRecord($data)) {
            $this->db->AutoExecute($this->table, $record, 'INSERT');
            return $this->db->Insert_ID();
        } else {
            $id = intval($post[$this->pkid]);
            $this->db->AutoExecute($this->table, $record, 'UPDATE', "{$this->pkid}={$id}");
            return $id;
        }
    }
    
    protected function isNewRecord($data) {
        $id = isset($post[$this->pkid]) ? intval($post[$this->pkid]) : 0;
	
        return ($id == 0);
    }
    
    private function recordFromData($data) {
        
        $record = array();
        
        //We MUST have meta at this point
        //Bit of deferred loading :)
        if(empty($this->field)) {
            $this->loadFieldMeta();
        }
        
        foreach ($this->fields as $field) {
            $val = array_key_exists($field, $post) ? $post[$field] : null;
            $val = ($val === '') ? null : $val; //don't just check for empty() because then a '0' would erroneously become null!
            $record[$field] = $val;
        }
        
        return $record;
    }
    
    private function loadFieldMeta() {
        
        foreach ($this->db->MetaColumns(self::$table) as $aCol) {
            if ($aCol->name != self::$pkID) {

                $this->fields[] = $aCol->name;
            }
        }
        
    }
    
    //Calls add_rule() on the given KohanaValidation object for a variety of "standard" rules.
    //We will only add rules for fields that exist in the given $fields_and_labels array,
    // which should have keys of field names and values of human-readable labels (for error messages).
    //
    //The following rules are added (depending on field's definition in db.xml):
    // -'required' rule is added to any fields having <NOTNULL/>
    // -'length[0,x]' rule is added to varchar fields ("x" is field size)
    // -'numeric' rule is added to float fields
    // -'digit' rule is added to integer fields
    // -'atleast[0]' rule is added to non-required unsigned integer fields
    // -'atleast[1]' rule is added to required unsigned integer fields
    protected function add_standard_rules(KohanaValidation &$v, $fields_and_labels) {
        $cols = $this->db->MetaColumns($this->table);
        foreach ($cols as $col) {
            if (array_key_exists($col->name, $fields_and_labels)) {
                $field = $col->name;
                $label = $fields_and_labels[$field];
                $type = $col->type;

                if ($col->not_null) {
                    $v->add_rule($field, 'required', "{$label} is required.");
                }

                if ($type == 'varchar') {
                    $v->add_rule($field, "length[0,{$col->max_length}]", "{$label} cannot exceed {$col->max_length} characters in length.");
                }

                if ($type == 'float') {
                    $v->add_rule($field, 'numeric', "{$label} must be a number.");
                }

                if ($type == 'int') {
                    $v->add_rule($field, 'digit', "{$label} must be a whole number.");
                    if ($col->unsigned) {
                        if ($col->not_null) {
                            $v->add_rule($field, 'atleast[1]', "You must choose a {$label}."); //Assumes required unsigned ints are foreign key id's, and hence have a dropdown list for selections
                        } else {
                            $v->add_rule($field, 'atleast[0]', "{$label} must be a positive number");
                        }
                    }
                }
            }
        }
    }

}