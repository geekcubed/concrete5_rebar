<?php defined('C5_EXECUTE') or die(_("Access Denied."));
/**
 * Rebar Display Table
 * Displays a listing of records in a styled html table with various action
 * buttons in each row.
 *
 * @package Rebar
 * @subpackage Core
 * @copyright (c) 2014, Ian Stapleton
 */
class RebarDisplayTable {

    private $view = null;
    private $columns = array();
    private $actions = array();
    private $sort_action_key = null;
    private $id_field_name = 'id';
    //if all columns have an empty "label", then don't output the table header row
    private $has_table_header = false;

    public function __construct(View &$c5_view_object) {
        $this->view = & $c5_view_object;
    }

    /**
     * Sets the primary key column name for the base object. Otherwise defaults
     * to 'id'
     *
     * @param string $field
     */
    public function overrideIdFieldName($field) {
        $this->id_field_name = $field;
    }

    /**
     * Adds a column to the table for display
     *
     * The field is a public property or method of the base model type. The object
     * is examined for $field in the following order
     * <ol><li>For a public Method $field()</li>
     * <li>For a public Method display_$field()</li>
     * <li>For a public Property $field</li></ol>
     * 2) Is useful for displaying things such as Email addresses formated as
     * clickable mailto: links.
     *
     * @param string $field Object Property or Method name
     * @param string $label Table column header
     * @param bool $escape_output Escape any HTML content
     */
    public function addColumn($field, $label = '', $escape_output = true) {
        $this->columns[$field] = array(
            'label' => $label,
            'escape' => $escape_output,
        );

        if (!empty($label)) {
            $this->has_table_header = true;
        }
    }

    /**
     * Add an action column to the table for display
     *
     * action can be either a controller action for a standard link-button, or
     * "click:myFunc", which will be automatically create a link with an
     * onClick="return myFunc(event, {Object ID});"
     *
     * @param string $action controller action or javascript action
     * @param string $placement 'left' or 'right' (display this link to the left or right of the data columns)
     * @param string $title Button text
     * @param string $icon CSS Class names added to an i element for glyph icons
     * @param boolean $is_sort_action Not currently used. <strike>true if this is the "sort" action. Used for Attribute tables.</strike>
     * @param string $override_id_value Override the base object ID with a set value (e.g. Parent ID)
     */
    public function addAction($action, $placement, $title, $icon, $is_sort_action = false, $override_id_value = null) {
        $this->actions[$action] = array(
            'action' => $action,
            'placement' => $placement,
            'title' => $title,
            'icon' => $icon,
            'sort' => $is_sort_action,
            'value' => $override_id_value,
        );

        /*if ($is_sort_action) {
            $this->sort_action_key = $action;
        }*/
    }

    /**
     * Generates and outputs (echos) HTML table
     *
     * @param RebarItemList $cl List of items to display
     * @param bool $paged Show All records (default) or single page thereof
     */
    public function display(RebarItemList $cl, $paged = false) {

        $out = array();
        $records = ($paged ? $cl->getPage() : $cl->get());

        //open wrappers
        if (!empty($this->sort_action_key)) {
            $action = $this->actions[$this->sort_action_key];
            $out[] = '<div class="sortable-container"'
                    . ' data-sortable-save-url="' . $this->view->action($action['action'], $action['value']) . '"'
                    . ' data-sortable-save-token="' . Loader::helper('validation/token')->generate() . '"'
                    . '>';
        }
        $out[] = '<table class="table table-striped">';

        //headings
        if ($this->has_table_header) {
            $out[] = '<thead>';
            $out[] = '<tr>';
            foreach ($this->actions as $action) {
                $out[] = ($action['placement'] == 'left') ? '<th>&nbsp;</th>' : '';
            }
            foreach ($this->columns as $field => $col) {
                $out[] = "<th>{$col['label']}</th>";
            }
            foreach ($this->actions as $action) {
                $out[] = ($action['placement'] == 'right') ? '<th>&nbsp;</th>' : '';
            }
            $out[] = '</tr>';
            $out[] = '</thead>';
        }

        //rows
        $out[] = '<tbody>';
        foreach ($records as $record) {
            $out[] = empty($this->sort_action_key) ? '<tr>' : '<tr data-sortable-id="' . $record->{$this->id_field_name} . '">';

            foreach ($this->actions as $action) {

                $idToPass = $record->{$this->id_field_name};
                if ($action['value']) {
                    //Override
                    if (property_exists($record, $action['value'])) {

                        $idToPass = $record->{$action['value']};
                    } else {
                        //Use raw
                        $idToPass = $action['value'];
                    }
                }

                $out[] = $this->getActionCellMarkup($action, $idToPass, 'left');
            }

            $last_field = array_pop(array_keys($this->columns));

            foreach ($this->columns as $field => $col) {
                $out[] = ($field === $last_field) ? '<td class="last-field">' : '<td>';

                if (method_exists($record, $field)) {

                    $val = $record->$field();
                } elseif (method_exists($record, "display_{$field}")) {

                    $method = "display_{$field}";

                    $val = $record->$method();
                } elseif (property_exists($record, $field)) {

                    $val = $record->$field;
                } else {

                    $val = '';
                }

                $out[] = $col['escape'] ? htmlentities($val) : $val;
                $out[] = '</td>';
            }
            foreach ($this->actions as $action) {
                $out[] = $this->getActionCellMarkup($action, $record->{$this->id_field_name}, 'right');
            }
            $out[] = '</tr>';
        }
        $out[] = '</tbody>';

        //close wrappers
        $out[] = '</table>';
        $out[] = empty($this->sort_action_key) ? '' : '</div><!-- .sortable-container -->';

        //output
        $nonempty_lines = array();
        foreach ($out as $line) {
            if (!empty($line)) {
                $nonempty_lines[] = $line;
            }
        }
        echo implode("\n", $nonempty_lines);
    }

    private function getActionCellMarkup($action, $id, $must_have_placement = '') {
        if (!empty($must_have_placement) && ($action['placement'] != $must_have_placement)) {
            //this action did not have the 'placement' that we're looking for
            return '';
        }

        $markup = '';

        $markup .= '<td class="action">';

        if ($action['sort']) {
            $markup .= '<span class="sortable-handle" title="' . $action['title'] . '"><i class="' . $action['icon'] . '"></i></span>';
        } else {

            //Handle JS events
            if (substr($action['action'], 0, 6) == 'click:') {

                $event = 'onclick="return ' . substr($action['action'], 6) . '(event, \'' . $id . '\')"';
            } else {

                $href = $this->view->action($action['action'], $id);
                $event = "href='{$href}'";
            }


            $markup .= '<a class="btn" ' . $event . '>';
            if ($action['icon']) {
                $markup .= '<i class="' . $action['icon'] . '"></i>';
            }
            if ($action['title']) {
                $markup .= ' ' . $action['title'];
            }
            $markup .= '</a>';
        }

        $markup .= '</td>';

        return $markup;
    }

}
