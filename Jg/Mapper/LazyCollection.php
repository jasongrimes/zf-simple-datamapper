<?php
/**
 * A "lazy" collection, which queries for row data as it is needed.
 *
 * @author     Jason Grimes <jason@grimesit.com>
 * @link       https://github.com/jasongrimes/zf-simple-datamapper
 * @category   Jg
 * @package    Mapper
 * @copyright  Copyright (c) 2011 Jason Grimes <jason@grimesit.com>
 */

class Jg_Mapper_LazyCollection extends Jg_Mapper_Collection {

  /**
   * @var Zend_Db_Select
   */
  protected $_select;

  /**
   * A Zend_Paginator_Adapter_DbSelect instance is used to automagically convert
   * the select object into a count query. @see count()
   *
   * @var Zend_Paginator_Adapter_DbSelect
   */
  protected $_paginator_helper;

  public function __construct(Zend_Db_Select $select, Jg_Mapper $mapper) {
    $this->_select = $select;
    $this->_mapper = $mapper;
    $this->_paginator_helper = new Zend_Paginator_Adapter_DbSelect($select);
  }

  public function count() {
    if (is_null($this->_total)) {
      $this->_total = $this->_paginator_helper->count();
    }
    return $this->_total;
  }

  public function getSelect() {
    return $this->_select;
  }

  protected function _notifyAccess($offset, $length = 1) {
    if (!$this->_issetRawSlice($offset, $length)) {
      $this->_loadRawSlice($offset, $length);
    }
  }

  protected function _issetRawSlice($offset, $length = 1) {
    $last_offset = $offset + $length - 1;
    if ($last_offset > $this->count()) {
      $last_offset = $this->count() - 1;
    }

    for ($i = $offset; $i <= $last_offset; $i++) {
      if (!isset($this->_raw[$i])) {
        return false;
      }
    }
    return true;
  }

  protected function _loadRawSlice($offset, $length = 1) {
    $select = $this->getSelect();
    $select->limit($length, $offset);
    $data = $select->query()->fetchAll();

    $i = $offset;
    foreach ($data as $row_data) {
      $this->_raw[$i] = $row_data;
      $i++;
    }
  }
}
