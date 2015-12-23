<?php

/* @DESCR -- Do not edit

XStructure.lib
Contains the basic class to build a config object
(c) 2015 Philippe Thomassigny

XStructure is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

XStructure is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Xamboo.  If not, see <http://www.gnu.org/licenses/>.

Creation: 2015-12-13
Changes:
  2015-12-13 Phil: First release

@End_DESCR */

namespace XStructure;

class XStructure implements \ArrayAccess, \Iterator, \Countable
{
  const VERSION = '1.0.0';
  protected $entries = array();

  /* The constructor receive a data, that may be a string (to be compiled) or an array of param => value
     The string of a configuration file has the format:
     parameter=value
     one per line. If a parameter is repeated, it will be inserted as an array of values
     The default array may contains expected values for each parameter, if they are not present.
       a null, 0 or false value in the parameters "is" a value and the default will not be used.
  */
  public function __construct($data, $descriptor)
  {
    $this->entries = XStructure::compile($data, $descriptor['main'], $descriptor);
  }
  
  // magic functions implements
  public function __get($name)
  {
    if (isset($this->entries[$name]))
      return $this->entries[$name];
    return null;
  }

  public function __set($name, $val)
  {
    $this->entries[$name] = $val;
    return $this;
  }

  public function __isset($name)
  {
    return isset($this->entries[$name]);
  }

  public function __unset($name)
  {
    unset($this->entries[$name]);
  }

  // ArrayAccess implemented
  public function offsetSet($offset, $value)
  {
    if ($offset)
      $this->entries[$offset] = $value;
  }

  public function offsetExists($offset)
  {
    return isset($this->entries[$offset]);
  }

  public function offsetUnset($offset)
  {
    unset($this->entries[$offset]);
  }

  public function offsetGet($offset)
  {
    return isset($this->entries[$offset]) ? $this->entries[$offset] : null;
  }

  // Iterator implemented
  public function rewind()
  {
    reset($this->entries);
  }

  public function current()
  {
    return current($this->entries);
  }

  public function key()
  {
    return key($this->entries);
  }

  public function next()
  {
    return next($this->entries);
  }

  public function valid()
  {
    return current($this->entries) !== false;
  }

  // Countable implemented
  public function count()
  {
    return count($this->entries);
  }

  // Own array get/set
  public function getArray()
  {
    return $this->entries;
  }

  public function setArray($array)
  {
    foreach($array as $k => $v)
      $this->entries[$k] = $v;
    return $this;
  }

  // is serializable
  protected function serial(&$data)
  {
    $data['entries'] = $this->entries;
  }

  protected function unserial($data)
  {
    $this->entries = $data['entries'];
  }

  // Build a beautifull string with parameters
  public function __toString()
  {
  }

  // Compiler of the configuration string. May be used without creating an instance 
  static private function compile($data, $structurename, $descriptor)
  {
    $struct = array();
    if (!isset($descriptor[$structurename]))
      throw new \Error('Error: the definition of set '.$structurename.' does not exist');
    $pos = 0;
    foreach($descriptor[$structurename] as $k => $p)
    {
      if (isset($p['conditionparam']))
      {
        if (!isset($struct[$p['conditionparam']]))
          throw new \Error('Error: the condition parameter is not defined: '.$p['conditionparam']);
        if ($struct[$p['conditionparam']] != $p['conditionvalue'])
          continue;
      }

      if (isset($p['pos']))
        $pos = $p['pos'];
      $end = isset($p['endian'])&&$p['endian']=='little'?false:true;
      
      switch($p['cast'])
      {
        case 'byte': case 'char': case 'uint8':
          $val = ord($data[$pos++]); break;
        case 'uint16':
          $val = 0;
          foreach (range(0, 1) as $n) { $val += (ord($data[$pos++]) << 8*($end?1-$n:$n)); } break;
        case 'uint24':
          $val = 0;
          foreach (range(0, 2) as $n) { $val += (ord($data[$pos++]) << 8*($end?2-$n:$n)); } break;
        case 'uint32':
          $val = 0;
          foreach (range(0, 3) as $n) { $val += (ord($data[$pos++]) << 8*($end?3-$n:$n)); } break;
        case 'uint64':
          $val = 0;
          foreach (range(0, 7) as $n) { $val += (ord($data[$pos++]) << 8*($end?7-$n:$n)); } break;
        case 'string':
          $val = substr($data, $pos, $p['length']);
          if ($end)
            $val = strrev($val);
          $pos += $p['length'];
          break;
        case 'string0':
          $ini = $pos;
          while (ord($data[$pos++]) != 0);
          $val = substr($data, $ini, $pos-$ini-1); // we don't copy the 0
          if ($end)
            $val = strrev($val);
          break;
        case 'hex':
          $val = substr($data, $pos, $p['length']);
          if ($end)
            $val = strrev($val);
          $val = bin2hex($val);
          $pos += $p['length'];
          break;
        case 'timestamp': // timestamp es uint32
          $val = 0;
          foreach (range(0, 3) as $n) { $val += (ord($data[$pos++]) << 8*($end?3-$n:$n)); }
          $val = date('Y-m-d H:i:s', $val);
          break;
        case 'opaque':
          $val = substr($data, $pos, $p['length']);
          $pos += $p['length'];
          break;
        case 'ignore':
          $pos += $p['length'];
          break;
        default:
          $length = (!is_numeric($p['length']))?$struct[$p['length']]:$p['length'];
          $fragment = substr($data, $pos, $length);
          $val = self::compile($fragment, $p['cast'], $descriptor);
          $pos += $length;
          break;
      }
      if ($p['cast']!='ignore')      
        $struct[$k] = $val;
    }
    return $struct;
  }

}

?>