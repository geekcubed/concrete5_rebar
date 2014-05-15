<?php

defined('C5_EXECUTE') or die(_("Access Denied."));

/**
 * Rebar Ordered Display Table
 * Extends RebarDisplayTable to support tables with column sorting etc.
 *
 * @package Rebar
 * @subpackage Core
 * @copyright (c) 2014, Ian Stapleton
 */
class RebarOrderedDisplayTable extends RebarDisplayTable {

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
     * @param bool $orderable Can the data table be sorted by this column?
     * @param string $orderby SQL Column name to sort by. If empty, $field is used
     */
    public function addColumn($field, $label = '', $escape_output = true, $orderable = false, $orderby = '') {

        $this->columns[$field] = array(
            'label' => $label,
            'escape' => $escape_output,
            'orderable' => $orderable,
            'orderby' => $orderby
        );

        if (!empty($label)) {
            $this->has_table_header = true;
        }
    }

    /**
     * Generates the thead for the table
     * Overrides parent method by inserts sorting urls
     *
     * @return array Lines of HTML for table header
     */
    protected function getTableHeaderHtml() {

        //Placeholder for output
        $out = array();

        if ($this->has_table_header) {
            $out[] = '<thead>';
            $out[] = '<tr>';
            foreach ($this->actions as $action) {
                $out[] = ($action['placement'] == 'left') ? '<th>&nbsp;</th>' : '';
            }

            //Process data cols
            $searchReq = $this->itemList->getSearchRequest();            
            foreach ($this->columns as $field => $col) {

                //Can we order by this?
                if ($col['orderable']) {

                    //Work out the correct params used to build the URL
                    //And some neat css classes whilst we're at it
                    if ($col['orderby']) {

                        $orderField = $col['orderby'];
                    } else {
                        $orderField = $field;
                    }

                    $classes = 'orderable';
                    if ($searchReq[$this->itemList->getQueryStringSortVariable()]) {

                        $classes .= " active";

                        if ($searchReq[$this->itemList->getQueryStringSortDirectionVariable()] == "desc") {

                            $classes .= " desc";
                        } else {
                            $classes .= " asc";
                        }
                    } else {

                        $classes .= " asc";
                    }

                    //build the URL
                    $orderUrl = $this->itemList->generateSortByURL($orderField);

                    //And mash it all together
                    $out[] = "<th><a class=\"{$classes}\" href=\"{$orderUrl}\">{$col['label']}</a></th>";
                } else {

                    $out[] = "<th>{$col['label']}</th>";
                }
            }

            foreach ($this->actions as $action) {
                $out[] = ($action['placement'] == 'right') ? '<th>&nbsp;</th>' : '';
            }

            $out[] = '</tr>';
            $out[] = '</thead>';
        }


        return $out;
    }

}
