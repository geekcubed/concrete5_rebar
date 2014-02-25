<?php defined('C5_EXECUTE') or die(_("Access Denied."));
/**
 * Rebar Runtime Exception
 * Extended Exception class to provide better tracing of Exceptions within the 
 * Rebar framework
 *
 * @package Rebar
 * @subpackage Core
 * @copyright (c) 2014, Ian Stapleton
 */
final class RebarRuntimeException extends Exception {

    /**
     * Some constants to standardise exception messages
     */
    const NONE_STATIC_METHOD = "Non-Static method called Statically";
    const MISCONFIGURED_INSTANCE = "Instance has not been configured correctly";
    const STATIC_NOT_SUPPORTED = "Static access to this method is not supported";
    const METHOD_NOT_OVERRIDEN = "The called method must be overriden in an child class";
    const INCORRECT_REFERENCE_TYPE = "The supplied Type reference is not valid in this context";

    /**
     * Constructor. Redefined from parent to make $message none-optional
     *
     * @param string $message Error message
     * @param int $code Exception Code
     * @param Exception $previous Previous Exception in the chain
     */
    public function __construct($message, $code = 0, Exception $previous = null) {

        //Try to auto-set code
        if (empty($code)) {
            switch ($message) {
                case self::NONE_STATIC_METHOD:
                    $code = 1101;
                default:
                    $code = 1100;
            }
        }

        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }

    /**
     * Generate a standardised, traceable, string version of the Exception
     * @return string
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}
