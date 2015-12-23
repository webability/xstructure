<?php

setlocale(LC_ALL, 'es_MX.UTF8', 'es_MX', '');
date_default_timezone_set('America/Mexico_City');

echo '<h1>XStructure examples</h1>';

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
    'session_id' => array('cast' => 'hex', 'length' => 32),
    'cipher_suites' => array('cast' => 'uint16'),
    'cipher_suites2' => array('cast' => 'uint16'),
    'compression_methods' => array('cast' => 'uint8'),
    'extensions' => array('cast' => 'uint8')
  )
);

$handshake = new \xstructure\XStructure(rawurldecode(file_get_contents('data/sslhandshake.data')), $def);
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
    parent::__construct(file_get_contents($filename), $this->descriptor);
  }
}

$BTCBlock = new BitCoinBlock('data/bitcoin-block0-header.data');
echo '<pre>'.print_r($BTCBlock, true).'</pre>';
echo '<br />';



?>