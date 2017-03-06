<?php

// What are you doing here, SMF?
define('SMF', 1);
$settings['default_images_url'] = '';
$settings['images_url'] = '';
$scripturl = '';
$sourcedir = __DIR__ . '/../src/live627/PostFields';

/**
 * @param string $hook
 */
function call_integration_hook($hook, $parameters = array())
{
    // You're fired! You're all fired!
}

/**
 * @param string $msg
 * @param boolean $log
 */
function fatal_error($msg, $log)
{
    echo $msg;
}

class MockUtil extends live627\PostFields\Util
{
    public function setFields(array $columns, array $data)
    {
        foreach ($data as $dataRow)
            $this->fields[] = array_combine(array_keys($columns), $dataRow);
    }

    // Work some magic into this joint.
    public function &__get($name)
    {
        return $this->$name;
    }
}

class Test extends PHPUnit_Framework_TestCase
{
    protected $loader;

    protected function setUp()
    {
        global $scripturl, $settings, $sourcedir;

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
                'no', '4', '1', 'yes', 'no', 'number',
            ),
        );

        $this->loader = new MockUtil;
        $this->loader->setFields($in_col, $in_data);
        $i = 0;
        require_once($sourcedir . '/Class-PostFields.php');
        foreach ($this->loader->fields as &$field) {
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

            // Refresh the fields!
            $field['class'] = new $class_name($field, $value, !empty($value));
        }
    }

    public function testExistingFields()
    {
        foreach ($this->loader->getFields() as $field) {
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
        $this->assertCount(7, $actual);

        $actual = $this->loader->filterFields(2);
        $this->assertCount(2, $actual);
    }

    public function testFieldHasNoErrors()
    {
        foreach ($this->loader->getFields() as $field) {
            $field['class']->validate();
            $this->assertFalse($field['class']->getError());
        }
    }

    public function testFieldHasErrors()
    {
        foreach ($this->loader->getFields() as $field) {
            if ($field['type'] == 'text' || $field['type'] == 'select') {
                $class_name = '\\live627\\PostFields\\postFields_' . $field['type'];
                $field['class'] = new $class_name($field, 'some<br>m<a>rkup</a>', true);
                $field['class']->validate();
                $this->assertCount(2, $field['class']->getError());
            }
        }
    }

    public function testInvalidMask()
    {
        foreach ($this->loader->getFields() as $field) {
            if ($field['type'] == 'text') {
                $class_name = '\\live627\\PostFields\\postFields_' . $field['type'];
                $field['mask'] .= 'INV';
                $field['class'] = new $class_name($field, 'some<br>m<a>rkup</a>', true);
                $field['class']->validate();
                $this->assertContains('Mask', $this->getActualOutput());
                break;
            }
        }
    }

    public function testFieldSearchableCount()
    {
        $this->assertCount(0, $this->loader->getFieldsSearchable());
    }

    public function testFieldCount()
    {
        $this->assertCount(7, $this->loader->getFields());
    }
}
