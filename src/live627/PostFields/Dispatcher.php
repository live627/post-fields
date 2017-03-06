<?php

/**
 * @package PostFields
 * @version 2.0
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2011-2016, John Rayes
 * @license http://opensource.org/licenses/ISC ISC
 */

namespace live627\PostFields;

class Dispatcher extends \Suki\Ohara
{
    public $name = 'PostFields';

    use \ModHelper\SingletonTrait;

    public function __construct()
    {
        global $context;

        // Load up all the tabs...
        $context[$context['admin_menu_name']]['tab_data'] = array(
            'title' => $this->text('title'),
            'description' => $this->text('desc'),
        );

        $sub_actions = [
            'index' => ['live627\PostFields\Admin', 'Index', 'admin_forum'],
            'edit' => ['live627\PostFields\Admin', 'Edit', 'admin_forum'],
        ];

        // Default to sub action 'index'
        if (!isset($_GET['sa']) || !isset($sub_actions[$_GET['sa']])) {
            $_GET['sa'] = 'index';
        }
        $this_sub_action = $sub_actions[$_GET['sa']];
        $context['sub_template'] = $_GET['sa'];

        // This area is reserved for admins only - do this here since the menu code does not.
        isAllowedTo($this_sub_action[2]);

        // Calls a private function based on the sub-action
        (new $this_sub_action[0])->$this_sub_action[1]();
    }
}
