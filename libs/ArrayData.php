<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/07/03
 * Time: 14:50
 * To change this template use File | Settings | File Templates.
 */

class ArrayData extends ArrayObject
{
    private $isReadOnly = false;

    public function setReadOnly($readOnly)
    {
        $this->isReadOnly = $readOnly;
    }

    public function isReadOnly()
    {
        return $this->isReadOnly;
    }

    protected $_typeName = 'Object';

    /**
     *
     * @param string $type 要素の指定型
     * @param array $input
     * @param int $flags
     * @param string $iterator_class
     * @throws InvalidArgumentException
     */
    public function __construct($type, $input = array(), $flags = 0, $iterator_class = 'ArrayIterator') {
        $this->_typeName = $type;
        if (!$this->_isValidArray($input)) {
            throw new InvalidArgumentException("Arguments type does not meet constraint " . $this->_typeName);
        }
        parent::__construct($input, $flags, $iterator_class);
    }

    /**
     * _isValidType
     * @param _className $value
     * @return boolean
     */
    private function _isValidType($value) {
        $checkFunc = 'is_' . $this->_typeName;
        if (!function_exists($checkFunc)) {
            return $value instanceof $this->_typeName;
        } else {
            return call_user_func($checkFunc, $value);
        }
    }

    /**
     * _checkArray
     * @param array $array
     * @return boolean
     */
    protected function _isValidArray($array) {
        if(count($array)>0) {
            foreach ($array as $value) {
                if (!$this->_isValidType($value)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * exchangeArray
     * @param array  $input
     * @throws  InvalidArgumentException
     * @return array
     */
    public function exchangeArray($input) {
        if (!$this->_isValidArray($input)) {
            throw new InvalidArgumentException("array type does not meet constraint " . $this->_typeName);
        } else {
            return parent::exchangeArray($input);
        }
    }

    /**
     * setType
     * @param   string $className
     * @throws  Exception
     * @access  public
     */
    public function setType($className) {
        if (parent::count() > 0 && $this->_typeName !== $className) {
            throw new Exception($this->_typeName);
        }
        $this->_typeName = $className;
    }

    /**
     * append
     * @param     mixed    $value
     * @throws    InvalidArgumentException
     * @throws    BadMethodCallException
     * @access    public
     */
    public function append($value) {

        if (!$this->isReadOnly) {

            if ($this->_isValidType($value)) {
                parent::append($value);
            } else {
                throw new InvalidArgumentException("Appended type does not meet constraint " . $this->_typeName);
            }

        } else {
            throw new BadMethodCallException('this instance is readOnly!');
        }
    }

    /**
     * offsetSet
     * @param     mixed    $index
     * @param     string    $newval
     * @throws    InvalidArgumentException
     * @throws    BadMethodCallException
     * @access    public
     */
    public function offsetSet($index, $newval) {

        if (!$this->isReadOnly) {

            if ($this->_isValidType($newval)) {
                parent::offsetSet($index, $newval);
            } else {
                throw new InvalidArgumentException("Appended type does not meet constraint " . $this->_typeName);
            }

        } else {
            throw new BadMethodCallException('this instance is readOnly!');
        }
    }

    /**
     * @param mixed $index
     * @throws BadMethodCallException
     */
    public function offsetUnset($index)
    {
        if (!$this->isReadOnly) {
            parent::offsetUnset($index);
        } else {
            throw new BadMethodCallException('this instance is readOnly!');
        }
    }
}

