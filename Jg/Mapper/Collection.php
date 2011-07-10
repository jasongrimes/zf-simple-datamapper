<?php
/**
 * A simple collection class that also works with Zend_Paginator.
 *
 * @author     Jason Grimes <jason@grimesit.com>
 * @link       https://github.com/jasongrimes/zf-simple-datamapper
 * @category   Jg
 * @package    Mapper
 * @copyright  Copyright (c) 2011 Jason Grimes <jason@grimesit.com>
 */

class Jg_Mapper_Collection implements Iterator, Countable, Zend_Paginator_Adapter_Interface {
  protected $_mapper;
  protected $_total;
  protected $_raw = array();

  protected $_result;
  protected $_pointer = 0;
  protected $_objects = array();

  public function __construct(array $raw, Jg_Mapper $mapper) {
    $this->_raw = $raw;
    $this->_total = count($raw);
    $this->_mapper = $mapper;
  }

  public function add($object) {
    $class = $this->_mapper->getDomainObjectClass();
    if (!($object instanceof $class)) {
      throw new Jg_Mapper_Exception('This is a "' . $class . '" collection');
    }
    $this->_objects[$this->count()] = $object;
    $this->_total++;
  }

  protected function _getRow($num) {
    $this->_notifyAccess($num);

    if ($num >= $this->_total || $num < 0) {
      return null;
    }
    if (!isset($this->_objects[$num]) && isset($this->_raw[$num])) {
      $this->_objects[$num] = $this->_mapper->createObject($this->_raw[$num]);
    }
    return $this->_objects[$num];
  }

  /**
	 * Rewind the Iterator to the first element. Required by Iterator interface.
   * 
	 * @link http://php.net/manual/en/iterator.rewind.php
	 * @return void Any returned value is ignored.
	 */
  public function rewind() {
    $this->_pointer = 0;
  }

  /**
	 * Return the current element. Required by Iterator interface.
   * 
	 * @link http://php.net/manual/en/iterator.current.php
	 * @return mixed Can return any type.
	 */
  public function current() {
    return $this->_getRow($this->_pointer);
  }

  /**
	 * Return the key of the current element. Required by Iterator interface.
   *
	 * @link http://php.net/manual/en/iterator.key.php
	 * @return scalar scalar on success, integer 0 on failure.
	 */
  public function key() {
    return $this->_pointer;
  }

  /**
	 * Move forward to next element. Required by Iterator interface.
   * 
	 * @link http://php.net/manual/en/iterator.next.php
	 * @return void Any returned value is ignored.
	 */
  public function next() {
    $row = $this->_getRow($this->_pointer);
    if ($row) {
      $this->_pointer++;
    }
    return $row;
  }

  /**
	 * Checks if current position is valid. Required by Iterator interface.
   * 
	 * @link http://php.net/manual/en/iterator.valid.php
	 * @return boolean The return value will be casted to boolean and then evaluated.
	 * Returns true on success or false on failure.
	 */
  public function valid() {
    return !is_null($this->current());
  }

  /**
	 * Count elements of an object. Required by Countable interface.
   *
	 * @link http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 */
  public function count() {
    return $this->_total;
  }

  /**
   * Returns an array of items for a page. Required by Zend_Paginator_Adapter_Interface.
   *
   * @param  integer $offset Page offset
   * @param  integer $itemCountPerPage Number of items per page
   * @return array
   */
  public function getItems($offset, $itemCountPerPage) {
    $this->_notifyAccess($offset, $itemCountPerPage);

    $items = array();
    for ($i = $offset; $i < ($offset + $itemCountPerPage); $i++) {
      $items[] = $this->_getRow($i);
    }
    return $items;
  }

  protected function _notifyAccess($offset, $length = 1) {
    // Empty on purpose. Child classes can extend to support lazy loading
  }
}
