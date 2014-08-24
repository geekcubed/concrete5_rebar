<?php defined('C5_EXECUTE') or die(_("Access Denied."));
/**
 * Rebar Controller
 * Provides some basic improvments over the default c5 controller. Mainly this
 * is to add support for single_page controllers that serve multiple urls
 *
 * @package Rebar
 * @subpackage Controllers
 * @copyright (c) 2014, Ian Stapleton
 */
class RebarController extends Controller {

    /**
     * String variables that allow us to easily decide what action is taken place
     * when we are running a CRUD type controller.
     */
    protected static $ProcessActionAdd = 'add';
    protected static $ProcessActionEdit = 'edit';
    protected static $ProcessActionSuccess = 'success';
    protected static $ProcessActionError = 'error';
    
    protected $record = null;
    
    public function __construct() {
        parent::__construct();

        $this->initFlash();
    }

    /**
     * Generate and handle CSRF tokens.
     *
     * Generates a new validation token and stores it in the View collection as
     * $token If a POST request is taking place, calling this method will attempt
     * to validate a token
     *
     * @throws Exception General exception when validation of the Token fails.
     *
     */
    protected function initCSRFToken() {
        $token = Loader::helper('validation/token');
        if (!empty($_POST) && !$token->validate()) {
            //Invalid
            throw new Exception($token->getErrorMessage());
        }

        $this->set('token', $token->output('', true));
    }

    /**
     * Init session variables for flash messages
     */
    private function initFlash() {
        $types = array('message', 'success', 'error');
        foreach ($types as $type) {
            $key = "flash_{$type}";
            if (!empty($_SESSION[$key])) {
                $this->set($type, $_SESSION[$key]); //C5 automagically displays 'message', 'success', and 'error' for us in dashboard views
                unset($_SESSION[$key]);
            }
        }
    }

    /**
     * Store a Flash message
     *
     * Flash messages can be used to persist information messages when a controller
     * action results in a redirect after processing.
     *
     * When working on Dashboard pages, these messages are automatically shown
     * to the user
     *
     * @param string $text Message string
     * @param string $type Message type ('message', 'success' or 'error')
     */
    public function flash($text, $type = 'message') {
        $key = "flash_{$type}";
        $_SESSION[$key] = $text;
    }

    /**
     * Perform a redirect to a different action. Method excepts additional
     * dynamic arguments which are passed via func_get_args();
     *
     * @param string $action Action name
     */
    public function internalRedirect($action) {
        //Do some fancy php stuff so we can accept and pass along
        // a variable number of args (anything after the $action arg).
        $args = func_get_args();
        array_unshift($args, $this->path());
        call_user_func_array(array('parent', 'redirect'), $args);
    }

    /**
     * Render a view file with the given name that exists in the single_pages
     * directory that corresponds with this controller's location / class name.
     *
     * @param string $view View template name
     */
    public function render($view) {
        $path = $this->path($view);
        parent::render($path);
    }

    /**
     * Return this controller's page path. This is based on the controller's
     * location in the sitemap, not the Action or View being rendered
     *
     * @param string $append Additional path components to append
     * @return string
     */
    public function path($append = '') {
        $path = $this->getCollectionObject()->getCollectionPath();
        if (!empty($append)) {
            $path .= '/' . $append;
        }
        return $path;
    }

    //Wrapper around View::url that always passes the controller's path as the url path
    // so you can call url('task', etc.) instead of url('path/to/controller', 'tasks', etc.).
    /**
     * Wrapper around the default Concrete5 View:url() method. Method excepts
     * additional dynamic arguments which are passed via func_get_args();
     *
     * Shortcut to allow you to call url('task', 'param') instead of the full
     * url('path/to/controller', 'tasks', 'param')
     *
     * @param string $task
     * @return string
     */
    public function url($task = null) {
        //Do some fancy php stuff so we can accept and pass along
        // a variable number of args (anything after the $task arg).
        View::url();
        $args = func_get_args();
        array_unshift($args, $this->path());
        return call_user_func_array(array('View', 'url'), $args);
    }

    /**
     * Sets controller variables from an associative array (keys become variable names)
     *
     * @param array $arr
     */
    public function setArray(array $arr) {
        $this->setObject((object) $arr);
    }
            
    /**
     * Sets controller variables from object properties (Property names
     * become variable names)
     *
     * @param object $obj
     */
    public function setObject($obj) {
        foreach (get_object_vars($obj) as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Renders the default 404 Concrete5 template, setting the correct http
     * header at the same time.
     */
    public function render404() {
        header("HTTP/1.0 404 Not Found");
        parent::render('/page_not_found');
    }

    /**
     * Renders the 404 page with header, and terminates execution. Useful for
     * "early exit" parameter checking on Controller methods.
     */
    public function render404AndExit() {
        $this->render404();
        exit;
    }

    /**
     * Automatically handle Create, Update, Add or Edit actions against RebarModel
     * objects.  If http POST is being performed, the record will be populated,
     * validated and created/updated.
     *
     * The result code allows you to take the correct "next step":
     * <ul>
     * <li>Add : No POST data found. Display the empty form for creating a new record</li>
     * <li>Edit : No POST data found. The record has been loaded and set as $this->record</li>
     * <li>Success : POST data found. The record validated and was updated or created</li>
     * <li>Error : POST data found. The record did not validate. $this->error contains an C5 Error Object</li>
     * </ul>
     *
     * Typically the only action you need to take next is to initialise any
     * form data that isn't related to the Object model directly - e.g. Select
     * element options.
     *
     * @param mixed $id Id of the record to edit/update, or null if creating
     * @param RebarModel $model empty (new) object of the correct model type
     * @return string Result status
     */
    public function processEditForm(&$id, RebarModel $model) {
        
        $this->set($model->getPrimaryKeyColumm(), $id);
        
        if ($this->isPost()) {
            $postData = $this->post();
            $error = $model->validate($postData);
            
            if ($error->has()) {
                $this->set('error', $error); //C5 automagically displays these errors for us in the view
                //C5 form helpers will automatically repopulate form fields from $_POST data
                return self::$ProcessActionError; // caller should manually repopulate data that isn't in $_POST
            } else {
                $id = $model->save($postData);
                $this->record = $model->getByID($id);
                
                return self::$ProcessActionSuccess; // caller should set flash message and redirect
            }
        } else if (empty($id)) {
            
            //caller should initialize form fields that don't start out empty/0
            return self::$ProcessActionAdd; 
        } else {
            //Populate form fields with existing record data
            $this->record = $model->getById($id);
            if (!$this->record) {
                $this->render404AndExit();
            }
            
            $this->setObject($this->record);

            //caller should populate form fields with existing record data
            return self::$ProcessActionEdit;
        }
    }
    

}