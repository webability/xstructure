<?php

/* @DESCR -- Do not edit

index.php examples for XStructure.lib
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
  2015-12-28 Phil: SSL handshake example now complete with full real structure read
  2015-12-13 Phil: First release

@End_DESCR */

setlocale(LC_ALL, 'es_MX.UTF8', 'es_MX', '');
date_default_timezone_set('America/Mexico_City');

echo '<h1>XStructure examples</h1>';

if (PHP_VERSION_ID < 70000)
{
  // creates \Error class to simulate PHP7 behaviour
  class Error extends \Exception {}
}

include_once '../include/xstructure/XStructure.class.php';

echo '<h2>Example of a SSL handshake:</h2>';
echo 'Note: This is not an exact reproduction but an example<br />';

$def = array(
    'main' => 'PlainText',
    'PlainText' => array(
      'type' => array('cast' => 'uint8'),
      'major' => array('cast' => 'uint8'),
      'minor' => array('cast' => 'uint8'),
      'length' => array('cast' => 'uint16'),

      'handshake' => array('cast' => 'Handshake',
                           'conditionparam' => 'type',
                           'conditionvalue' => 22,
                           'length' => 'length'
                           )
    ),
    'Handshake' => array(
      'type' => array('cast' => 'uint8'),
      'length' => array('cast' => 'uint24'),
      
      'client_hello' => array('cast' => 'ClientHello',
                              'conditionparam' => 'type',
                              'conditionvalue' => 1,
                              'length' => 'length'
                              )
    ),
    
    'ClientHello' => array(
      'major' => array('cast' => 'uint8'),
      'minor' => array('cast' => 'uint8'),
      'gmt_unix_time' => array('cast' => 'timestamp'),
      'random_bytes' => array('cast' => 'hex', 'length' => 28),
      'session_ids_length' => array('cast' => 'uint8'),
      'session_ids' => array('cast' => 'session_id', 'vector' => true, 'length' => 'session_ids_length', 'lengthtype' => 'bytes'),
      'cipher_suites_length' => array('cast' => 'uint16'),
      'cipher_suites' => array('cast' => 'cipher_suite', 'vector' => true, 'length' => 'cipher_suites_length', 'lengthtype' => 'bytes'),
      'compression_methods_length' => array('cast' => 'uint8'),
      'compression_methods' => array('cast' => 'compression_method', 'vector' => true, 'length' => 'compression_methods_length', 'lengthtype' => 'bytes'),
      'extensions_length' => array('cast' => 'uint16'),
      'extensions' => array('cast' => 'extension', 'vector' => true, 'length' => 'extensions_length', 'lengthtype' => 'bytes')
    ),
    
    'session_id' => array(
      'id' => array('cast' => 'uint8')
    ),

    'cipher_suite' => array(
      'minor' => array('cast' => 'hex', 'length' => 1),
      'major' => array('cast' => 'hex', 'length' => 1)
    ),
    
    'compression_method' => array(
      'id' => array('cast' => 'uint8')
    ),
    
    'extension' => array(
      'extension_type' => array('cast' => 'uint16'),
      'extension_length' => array('cast' => 'uint16'),
      'extension_servername' => array('cast' => 'servernamelist', 'length' => 'extension_length',
                                      'conditionparam' => 'extension_type',
                                      'conditionvalue' => 0),
      'extension_data' => array('cast' => 'hex', 'length' => 'extension_length',
                                      'conditionparam' => 'extension_type',
                                      'conditionnotvalue' => 0),
    ),
    
    'servernamelist' => array(
      'length' => array('cast' => 'uint16'),
      'servernames' => array('cast' => 'servername', 'vector' => true, 'length' => 'length', 'lengthtype' => 'bytes')
    ),
    
    'servername' => array(
      'nametype' => array('cast' => 'uint8'),
      'length' => array('cast' => 'uint16'),
      'hostname' => array('cast' => 'string', 'length' => 'length')
    )
);

$handshake = new \xstructure\XStructure($def, rawurldecode(file_get_contents('data/sslhandshake.data')));
echo '<pre>'.print_r($handshake, true).'</pre>';
echo '<br />';

echo '<h2>Example of the BitCoin Original Block 0 Header:</h2>';

class BitCoinBlock extends \xstructure\XStructure
{
  private $descriptor = array(
    'main' => 'Block',
    'Block' => array(
      'magic' => array('cast' => 'hex', 'length' => 4),
      'size' => array('cast' => 'uint32', 'endian' => 'little'),
      'header' => array('cast' => 'Header', 'length' => 80),
    ),
    'Header' => array(
      'version' => array('cast' => 'uint32', 'endian' => 'little'),
      'hashPrevBlock' => array('cast' => 'hex', 'length' => 32),
      'hashMerkleRoot' => array('cast' => 'hex', 'length' => 32),
      'Time' => array('cast' => 'timestamp', 'endian' => 'little'),
      'Bits' => array('cast' => 'hex', 'length' => 4),
      'Nonce' => array('cast' => 'uint32', 'endian' => 'little'),
    )
  );
  
  public function __construct($filename)
  {
    parent::__construct($this->descriptor, rawurldecode(file_get_contents($filename)));
  }
}

$BTCBlock = new BitCoinBlock('data/bitcoin-block0-header.data');
echo '<pre>'.print_r($BTCBlock, true).'</pre>';
echo '<br />';

?>