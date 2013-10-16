<?php defined('C5_EXECUTE') or die(_("Access Denied."));

final class RebarRuntimeException extends Exception {
    
    /**
     * Some constants to standardise exception messages
     */
    const NONE_STATIC_METHOD = "Non-Static method called Statically";
    const MISCONFIGURED_INSTANCE = "Instance has not ben configured correctly";
    const STATIC_NOT_SUPPORTED = "Static access to this method is not supported";
    
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
