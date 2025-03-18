<?php
/**
 * Main loader class for the plugin
 *
 * @package Report_Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main loader class for Report Content plugin.
 * Responsible for loading all classes and running the plugin.
 */
class ReportContentLoader {

    /**
     * Array of actions to register with WordPress
     *
     * @var array
     */
    protected $actions;

    /**
     * Array of filters to register with WordPress
     *
     * @var array
     */
    protected $filters;

    /**
     * Initialize the class and set properties
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();

        $this->loadDependencies();
        $this->defineAdminHooks();
        $this->definePublicHooks();
    }

    /**
     * Load required dependencies for this plugin
     */
    private function loadDependencies() {
        // Core functionality
        require_once REPORT_CONTENT_PLUGIN_DIR . 'includes/core/ReportContentPostHandler.php';
        require_once REPORT_CONTENT_PLUGIN_DIR . 'includes/core/ReportContentSettings.php';
        
        // Admin-specific functionality
        require_once REPORT_CONTENT_PLUGIN_DIR . 'includes/admin/ReportContentAdmin.php';
        
        // Public-facing functionality
        require_once REPORT_CONTENT_PLUGIN_DIR . 'includes/public/ReportContentPublic.php';
    }

    /**
     * Define admin hooks for the plugin
     */
    private function defineAdminHooks() {
        $pluginAdmin = new ReportContentAdmin();
        
        // Add menu pages
        $this->addAction('admin_menu', $pluginAdmin, 'addMenuPages');
        
        // Add settings
        $this->addAction('admin_init', $pluginAdmin, 'registerSettings');
        
        // Admin scripts and styles
        $this->addAction('admin_enqueue_scripts', $pluginAdmin, 'enqueueStyles');
        $this->addAction('admin_enqueue_scripts', $pluginAdmin, 'enqueueScripts');
    }

    /**
     * Define public hooks for the plugin
     */
    private function definePublicHooks() {
        $pluginPublic = new ReportContentPublic();
        
        // Public scripts and styles
        $this->addAction('wp_enqueue_scripts', $pluginPublic, 'enqueueStyles');
        $this->addAction('wp_enqueue_scripts', $pluginPublic, 'enqueueScripts');
        
        // Add reporting functionality
        $this->addAction('wp_ajax_report_content', $pluginPublic, 'handleReport');
        $this->addAction('wp_ajax_nopriv_report_content', $pluginPublic, 'handleReport');
        
        // Add report button to posts
        $this->addFilter('the_content', $pluginPublic, 'addReportButton');
    }

    /**
     * Add a new action to the collection to be registered with WordPress.
     *
     * @param string $hook          The name of the WordPress action to register.
     * @param object $component     A reference to the instance of the object where the action is defined.
     * @param string $callback      The name of the function to call.
     * @param int    $priority      Optional. The priority. Default 10.
     * @param int    $acceptedArgs  Optional. The number of args the function accepts. Default 1.
     */
    public function addAction($hook, $component, $callback, $priority = 10, $acceptedArgs = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $acceptedArgs);
    }

    /**
     * Add a new filter to the collection to be registered with WordPress.
     *
     * @param string $hook          The name of the WordPress filter to register.
     * @param object $component     A reference to the instance of the object where the filter is defined.
     * @param string $callback      The name of the function to call.
     * @param int    $priority      Optional. The priority. Default 10.
     * @param int    $acceptedArgs  Optional. The number of args the function accepts. Default 1.
     */
    public function addFilter($hook, $component, $callback, $priority = 10, $acceptedArgs = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $acceptedArgs);
    }

    /**
     * Utility function for registering hooks
     *
     * @param array  $hooks         The collection of hooks to register.
     * @param string $hook          The name of the WordPress filter to register.
     * @param object $component     A reference to the instance of the object where the filter is defined.
     * @param string $callback      The name of the function to call.
     * @param int    $priority      The priority at which to register.
     * @param int    $acceptedArgs  The number of arguments the function accepts.
     * @return array
     */
    private function add($hooks, $hook, $component, $callback, $priority, $acceptedArgs) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'acceptedArgs'  => $acceptedArgs
        );
        return $hooks;
    }

    /**
     * Register all filters and actions with WordPress.
     */
    public function run() {
        // Register all actions
        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['acceptedArgs']
            );
        }

        // Register all filters
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['acceptedArgs']
            );
        }
    }
}