<?php

namespace Lum\XML;

/**
 * Simplistic PList XML builder.
 */
class PList 
{
  protected $root;

  public function __construct ()
  {
    $this->root = new PListDict();
  }

  public function dict (string $key) :PListDict
  {
    return $this->root->dict($key);
  }

  public function arr (string $key) :PListArray
  {
    return $this->root->arr($key);
  }

  public function val (string $key, $val)
  {
    return $this->root->val($key, $val);
  }

  public function date (string $key, $date)
  {
    return $this->root->date($key, $date);
  }

  public function data (string $key, $data)
  {
    return $this->root->data($key, $data);
  }

  public function toSimpleXML ()
  {
    $xml = new \SimpleXMLElement('<plist version="1.0" />');
    $this->root->_to_xml($xml);
    return $xml;
  }

  public function asXML ()
  {
    $xml = $this->toSimpleXML();
    return $xml->asXML();
  }

  public function __toString ()
  {
    return $this->asXML();
  }

  public static function _val_to_xml (\SimpleXMLElement $xml, $val)
  {
    if (is_string($val))
    {
      $xml->addChild('string', $val);
    }
    elseif (is_int($val))
    {
      $xml->addChild('integer', $val);
    }
    elseif (is_float($val))
    {
      $xml->addChild('real', $val);
    }
    elseif (is_bool($val))
    {
      if ($val)
      {
        $xml->addChild('true');
      }
      else
      {
        $xml->addChild('false');
      }
    }
    else
    { // This shouldn't happen.
      throw new PListException("Unhandled value found");
    }
  }

}

/**
 * A dictionary, maps keys and values.
 */
class PListDict
{
  protected $data = [];

  public function dict (string $key) :PListDict
  {
    $dict = new PListDict();
    $this->data[$key] = $dict;
    return $dict;
  }

  public function arr (string $key) :PListArray
  {
    $arr = new PListArray();
    $this->data[$key] = $arr;
    return $arr;
  }

  public function val (string $key, $val) :self
  {
    if (!is_scalar($val))
    {
      throw new PListException("Cannot add non-scalar value using val()");
    }
    $this->data[$key] = $val;
    return $this;
  }

  public function date (string $key, $date) :self
  {
    $date = new PListDate($date);
    $this->data[$key] = $date;
    return $this;
  }

  public function data (string $key, $data) :self
  {
    $data = new PListData($data);
    $this->data[$key] = $data;
    return $this;
  }

  public function _to_xml ($xml)
  {
    $dict = $xml->addChild('dict');
    foreach ($data as $key => $val)
    {
      $dict->addChild('key', $key);
      if (is_object($val) && is_callable([$val, '_to_xml']))
      {
        $val->_to_xml($dict);
      }
      else
      {
        PList::_val_to_xml($dict, $val);
      }
    }
  }

}

class PListArray
{
  protected $data = [];

  public function dict () :PListDict
  {
    $dict = new PListDict();
    $this->data[] = $dict;
    return $dict;
  }

  public function arr () :PListArray
  {
    $arr = new PListArray();
    $this->data[] = $arr;
    return $arr;
  }

  public function val ($val) :self
  {
    if (!is_scalar($val))
    {
      throw new PListException("Cannot add non-scalar value using val()");
    }
    $this->data[] = $val;
    return $this;
  }

  public function date ($date) :self
  {
    $date = new PListDate($date);
    $this->data[] = $date;
    return $this;
  }

  public function data ($data) :self
  {
    $data = new PListData($data);
    $this->data[] = $data;
    return $this;
  }

  public function _to_xml ($xml)
  {
    $dict = $xml->addChild('array');
    foreach ($data as $val)
    {
      if (is_object($val) && is_callable([$val, '_to_xml']))
      {
        $val->_to_xml($dict);
      }
      else
      {
        PList::_val_to_xml($dict, $val);
      }
    }
  }

}

class PListDate
{
  protected $date;

  public function __construct ($date)
  {
    if (is_numeric($date))
    { // Assume it's a unix timestamp.
      $this->date = date('c', $date);
    }
    elseif (is_string($date))
    { // Assume it's already an ISO 8601 string.
      $this->date = $date;
    }
    elseif ($date instanceof \DateTime)
    { // Easy as pi.
      $this->date = $date->format('c');
    }
    else
    { // Don't recognize this.
      throw new PListException("Unrecognized date format");
    }
  }

  public function _to_xml ($xml)
  {
    $xml->addChild('date', $this->date);
  }
}

class PListData
{
  protected $data;

  public function __construct ($data)
  {
    $this->data = \base64_encode($data);
  }

  public function _to_xml ($xml)
  {
    $xml->addChild('data', $this->data);
  }
}

class PListException extends \Exception {}

