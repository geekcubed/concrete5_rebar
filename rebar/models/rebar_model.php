<?php defined('C5_EXECUTE') or die(_("Access Denied."));
/**
 * Rebar Model
 * Base Model type. Provides scaffoldin
 *
 * @package Rebar
 * @subpackage Models
 * @copyright (c) 2014, Ian Stapleton
 */
abstract class RebarModel extends Object {

    protected static $table;
    protected static $pkID = 'id';
    protected static $markDeleted = true;
    ///TODO - FieldMeta needs to be profiled to see if a static property would
    //enable some duplicate DB calls to be removed (hopefully the DB layer will
    //cache this but you never know)
    protected $fields = null;
    protected $db = null;
    protected $populateFieldMeta = false;

    public abstract function validate(&$data);

    public function __construct($populateFieldMeta = false) {

        if (empty(static::$table)) {

            throw new RebarRuntimeException(
            RebarRuntimeException::MISCONFIGURED_INSTANCE, 0, new Exception('Backing Table not declared'));
        }

        $this->populateFieldMeta = $populateFieldMeta;
        $this->db = Loader::db();

        //Auto populate fields if required
        if ($this->populateFieldMeta && empty($this->fields)) {
            $this->loadFieldMeta();
        }
    }

    public function getTable() {
        return static::$table;
    }

    public function getPrimaryKeyColumm() {
        return static::$pkID;
    }

    public function getMarkDeleted() {
        return static::$markDeleted;
    }

    public function getID() {

        if (!empty($this->{static::$pkID})) {
            return $this->{static::$pkID};
        }
    }

    protected static function who() {
        return get_called_class();
    }

    public static function getByID($id, $includeDeleted = false) {

        $db = Loader::db();
        $obj = null;
        $objClass = get_called_class();

        $qry = "SELECT * FROM " . static::$table . " WHERE " . static::$pkID . " = ?";

        if (static::$markDeleted && !$includeDeleted) {
            //Make sure records marked as deleted are excluded
            $qry .= " AND deleted = 0";
        }

        //Exec sql query
        $data = $db->getRow($qry, array($id));

        if (!empty($data)) {

            $obj = new $objClass();

            $obj->setPropertiesFromArray($data);
        }

        return is_a($obj, $objClass) ? $obj : false;
    }

    public static function getByField($fieldName, $fieldValue, $includeDeleted = false) {

        $db = Loader::db();
        $obj = null;
        $objClass = get_called_class();

        $qry = "SELECT * FROM " . static::$table . " WHERE {$fieldName} = ?";

        if (static::$markDeleted && !$includeDeleted) {
            //Make sure records marked as deleted are excluded
            $qry .= " AND deleted = 0";
        }

        //Exec sql query
        $data = $db->getRow($qry, array($fieldValue));

        if (!empty($data)) {

            $obj = new $objClass();

            $obj->setPropertiesFromArray($data);
        }

        return is_a($obj, $objClass) ? $obj : false;
    }

    public function save($data) {

        $record = $this->recordFromData($data);

        if ($this->isNewRecord($data)) {
            $this->db->AutoExecute(static::$table, $record, 'INSERT');
            return $this->db->Insert_ID();
        } else {

            $id = intval($data[static::$pkID]);
            $this->db->AutoExecute(static::$table, $record, 'UPDATE', static::$pkID . "={$id}");

            return $id;
        }
    }

    public function delete() {

        //How do we delete?
        if (static::$markDeleted) {
            //If we are marking deleted (default), the run an Update query
            $qry = "UPDATE " . static::$table . " SET deleted = 1 WHERE " . static::$pkID . " = ?";
        } else {
            //Old fashioned :)
            $qry = "DELETE FROM " . static::$table . " WHERE " . static::$pkID . " = ? LIMIT 1";
        }

        $this->db->Execute($qry, array($this->getID()));
    }

    protected function isNewRecord($data) {
        $id = isset($data[static::$pkID]) ? intval($data[static::$pkID]) : 0;

        return ($id == 0);
    }

    private function recordFromData($data) {

        $record = array();

        //We MUST have meta at this point
        //Bit of deferred loading :)
        if (empty($this->fields)) {
            $this->loadFieldMeta();
        }

        foreach ($this->fields as $field) {

            $val = array_key_exists($field->name, $data) ? $data[$field->name] : null;

            //Null out nullables
            if ($field->not_null == false && $val == '') {
                $val = null;
            }

            //Handle types etc;
            switch ($field->type) {
                //Integers
                case 'tinyint':
                case 'smallint':
                case 'int':
                case 'bigint':
                case 'integer':
                case 'serial':
                    $val = intval($val);
                    break;
                //Dates
                case 'date':
                    if ($val) {
                        $val = date('Y-m-d', strtotime($val));
                    }
                    break;
                case 'datetime':
                    if ($val) {
                        $val = date('Y-m-d H:i:s', strtotime($val));
                    }
                    break;
            }

            $record[$field->name] = $val;
        }

        return $record;
    }

    private function loadFieldMeta() {

        foreach ($this->db->MetaColumns(static::$table) as $aCol) {
            if ($aCol->name != static::$pkID) {

                $this->fields[] = $aCol;
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
        $cols = $this->db->MetaColumns(static::$table);
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
