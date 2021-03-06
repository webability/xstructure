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
  2015-12-28 Phil: data is optional in constructor, compile is now protected, rstring added, vectors added, relative positions in cascade added
  2015-12-13 Phil: First release

@End_DESCR */

namespace XStructure;

class XStructure implements \ArrayAccess, \Iterator, \Countable
{
  const VERSION = '1.0.2';
  protected $entries = array();
  protected $pos = 0;

  /* The constructor receive a data, that may be a string (to be compiled) or an array of param => value
     The string of a configuration file has the format:
     parameter=value
     one per line. If a parameter is repeated, it will be inserted as an array of values
     The default array may contains expected values for each parameter, if they are not present.
       a null, 0 or false value in the parameters "is" a value and the default will not be used.
  */
  public function __construct($descriptor, $data = null)
  {
    if ($data)
      $this->entries = XStructure::compile($data, $descriptor['main'], $descriptor, $this->pos);
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
  static protected function compile($data, $structurename, $descriptor, &$pos = 0)
  {
    $struct = array();
    if (!isset($descriptor[$structurename]))
      throw new \Error('Error: the definition of set '.$structurename.' does not exist');
    foreach($descriptor[$structurename] as $k => $p)
    {
      if (isset($p['conditionparam']))
      {
        if (!isset($struct[$p['conditionparam']]))
          throw new \Error('Error: the condition parameter is not defined: '.$p['conditionparam']);
        if ((isset($p['conditionvalue']) && $struct[$p['conditionparam']] != $p['conditionvalue']) || (isset($p['conditionnotvalue']) && $struct[$p['conditionparam']] == $p['conditionnotvalue']))
          continue;
      }
      if (isset($p['vector']) && $p['vector'])
      {
        $length = (!is_numeric($p['length']))?$struct[$p['length']]:$p['length'];
        $val = array();
        if ($length > 0)
        {
          if (isset($p['lengthtype']) && $p['lengthtype'] == 'bytes')
          {
            $initpos = $pos;
            $total = $pos + $length;
            while ($pos < $total)
            {
              $val[] = self::compile($data, $p['cast'], $descriptor, $pos);
            }
            if ($pos != $total) // if strucure if correct, this should NOT happen
              throw new \Error('Error: Wrong data size against structure size: '.$k.' expected '.$length.' bytes, total structure bytes = '. ($pos - $initpos));
          }
          else
          {
            foreach(range(1, $length) as $i)
            {
              $val[] = self::compile($data, $p['cast'], $descriptor, $pos);
            }
          }
        }
      }
      else
      {
        if (isset($p['pos']))
          $pos = $p['pos'];
        // default big endian for common integers
        if (in_array($p['cast'], array('uint16', 'uint24', 'uint32', 'uint64', 'timestamp')))
          $end = isset($p['endian'])&&$p['endian']=='little'?false:true;
        else // default little endian for strings and others
          $end = isset($p['endian'])&&$p['endian']=='big'?true:false;
        
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
          case 'varint':
            $val = 0;
            $first = ord($data[$pos++]);
            switch($first)
            {
              case 0xFD:
                foreach (range(0, 1) as $n) { $val += (ord($data[$pos++]) << 8*($end?1-$n:$n)); } break;
              case 0xFE:
                foreach (range(0, 3) as $n) { $val += (ord($data[$pos++]) << 8*($end?3-$n:$n)); } break;
              case 0xFF:
                foreach (range(0, 7) as $n) { $val += (ord($data[$pos++]) << 8*($end?7-$n:$n)); } break;
              default:
                $val = $first;
                break;
            }
            break;
          case 'rstring':
          case 'string':
          case 'hex':
            $length = (!is_numeric($p['length']))?$struct[$p['length']]:$p['length'];
            $val = substr($data, $pos, $length);
            if ($p['cast'] == 'rstring') // readable string
              $val = preg_replace('/[\x00-\x1f]/', '.', $val);
            if ($end)
              $val = strrev($val);
            if ($p['cast'] == 'hex')
              $val = bin2hex($val);
            $pos += $length;
            break;
          case 'string0':
            $ini = $pos;
            while (ord($data[$pos++]) != 0);
            $val = substr($data, $ini, $pos-$ini-1); // we don't copy the 0
            if ($end)
              $val = strrev($val);
            break;
          case 'timestamp': // timestamp es uint32
            $val = 0;
            foreach (range(0, 3) as $n) { $val += (ord($data[$pos++]) << 8*($end?3-$n:$n)); }
            $val = date('Y-m-d H:i:s', $val);
            break;
          case 'opaque':
            $length = (!is_numeric($p['length']))?$struct[$p['length']]:$p['length'];
            $val = substr($data, $pos, $length);
            $pos += $length;
            break;
          case 'ignore':
            $length = (!is_numeric($p['length']))?$struct[$p['length']]:$p['length'];
            $pos += $length;
            break;
          default:
            $length = (!is_numeric($p['length']))?$struct[$p['length']]:$p['length'];
            $fragment = substr($data, $pos, $length);
            if (strlen($fragment) < $length)
              throw new \Error('Error: missing data: the expected fragment length is less than the total data packet ' . $k . ' for expected length: ' . $length);
            $val = self::compile($fragment, $p['cast'], $descriptor);
            $pos += $length;
            break;
        }
      }
      if ($p['cast']!='ignore')      
        $struct[$k] = $val;
    }
    return $struct;
  }
  
}

?>