<?php

// What are you doing here, SMF?
define('SMF', 1);

function call_integration_hook($hook, $parameters = array()) {
    // You're fired! You're all fired!
}

function fatal_error($msg, $log) {
    die($msg);
}
class MockUtil extends live627\PostFields\Util
{
    public $Fields = array();

    public function setFields(array $columns, array $data)
    {
        foreach ($data as $dataRow)
            $this->Fields[] = array_combine(array_keys($columns), $dataRow);
    }

    function total_getPostFields()
    {
        return $this->Fields;
    }
}

class Test extends PHPUnit_Framework_TestCase
{
    protected $loader;

    protected function setUp()
    {
        global $scripturl, $settings, $sourcedir;

        $settings['default_images_url'] = '';
        $settings['images_url'] = '';
        $scripturl = '';
        $sourcedir = __DIR__  . '/../src/live627/PostFields';

        $in_col = array(
            'name' => 'string', 'type' => 'string', 'size' => 'string', 'options' => 'string', 'active' => 'string', 'default_value' => 'string',
            'can_search' => 'string', 'groups' => 'string', 'boards' => 'string', 'topic_only' => 'string', 'bbc' => 'string', 'mask' => 'string',
        );
        $in_data = array(
            array(
                'Notice', 'text', 80, '', 'yes', '',
                'no', '1', '1', 'yes', 'no', 'nohtml',
            ),
            array(
                'When', 'select', 80, 'ASAP,Tomorrow,This Week', 'yes', 'Tomorrow',
                'no', '1,2', '1,2', 'yes', 'no', '',
            ),
            array(
                'Price', 'radio', 80, 'Quote Me,Fixed', 'yes', 'Quote Me',
                'no', '1', '1,2', 'yes', 'yes', '',
            ),
            array(
                '£', 'text', 80, '', 'yes', '',
                'no', '1', '1', 'yes', 'yes', 'float',
            ),
            array(
                'To', 'text', 80, '', 'yes', '',
                'no', '1', '1', 'yes', 'yes', 'email',
            ),
            array(
                'Human', 'check', 80, '', 'yes', '',
                'no', '1', '1', 'yes', 'yes', '',
            ),
            array(
                '$', 'text', 80, '', 'yes', '',
                'no', '1', '4', 'yes', 'no', 'number',
            ),
        );

        $this->loader = new MockUtil;
        $this->loader->setFields($in_col, $in_data);
        $i = 0;
        require_once($sourcedir . '/Class-PostFields.php');
        foreach ($this->loader->Fields as &$field)
        {
            $field['id_field'] = ++$i;
            $field['description'] = '';
            $field['bbc'] = 'no';
            $class_name = '\\live627\\PostFields\\postFields_' . $field['type'];
            $field['class'] = new $class_name($field, '', false);
            $value = $field['class']->getValue();
            if (empty($value)) {
                $value = $field['id_field'];
            }
            if ($field['type'] == 'text') {
                switch ($field['mask']) {
                    case 'regex':
                    $value = '/^def//';
                    break;
                    case 'email':
                    $value = 'live627@gmail.com';
                    break;
                    case 'float':
                    $value = 6.8;
                    break;
                    case 'number':
                    $value = 6;
                    break;
                }
            }
            $field['value'] = $value;
        }
    }

    public function testExistingFields()
    {
        foreach ($this->loader->Fields as $field)
        {
            $actual = $this->loader->renderField($field, '', false);
            $this->assertSame($field['name'], $actual['name']);
        }
    }

    public function testFilteredFields()
    {
        global $user_info;

        $user_info['groups'] = [1];
        $actual = $this->loader->filterFields(1);
        $this->assertCount(6, $actual);

        $actual = $this->loader->filterFields(2);
        $this->assertCount(2, $actual);

        $user_info['groups'] = [2];
        $actual = $this->loader->filterFields(1);
        $this->assertCount(1, $actual);

        $actual = $this->loader->filterFields(2);
        $this->assertCount(1, $actual);

        $user_info['groups'] = [1, 2, 4];
        $actual = $this->loader->filterFields(1);
        $this->assertCount(6, $actual);

        $actual = $this->loader->filterFields(2);
        $this->assertCount(1, $actual);
    }

    public function testFieldErrors()
    {
        foreach ($this->loader->Fields as $field)
        {
            $field['class']->validate();
            $this->assertFalse($field['class']->getError());
        }
    }

    public function testFieldCount()
    {
        $this->assertCount(7, $this->loader->Fields);
    }
}
