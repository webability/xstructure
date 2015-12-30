# XStructure

## Introduction

Actual version: 1.0.1

The XStructure library is used to easily build a data set based on a data stream, packet, fragment, other language, like C++ or DB files. Examples come with a TLS1.2 encrypted HTTP1.1 message and the Bitcoin Block-0 header

## Change History

v1.0.1:
- Added vector structure type
- Added rstring structure type (readable string: replace ascii characters < 32 by points)
- Added flexible size substructures and vectors
- Compiler is now protected so extended classes can compile too
- Data is optional into constructor, arguments order has changed
- The example of TLS1.2 is fully decoded
- The example of the bitcoin block 0 is rawurlencoded too

v1.0.0:
- Original release

## User guide

Many time while programming you have to read and easily understand structures that comes from other programs, languages or official standards, RFCs, etc.

PHP lacks of structures to easily read and understand those data.

The XStructure class is intented to be a translator between the external data structures and PHP, based on a dictionary to extract information from the datastreams.
It builds the data into a local dataset to directly use them.

The library comes with 2 real explicit examples:
- A SSL Handshake based on TLS 1.2
- The Bitcoin Block 0 header

** Note: If the examples are missing, please refer to the github repository (It seems phpclasses.org have problems to import the raw examples).
https://github.com/webability/xstructure


### The XStructure recognize the following basic data casts:

Bytes:  byte, char, uint8

Integers: uint16, uint24, uint32, uint64

Time: unix timestamp

strings: normal, hexadecimal, 0-ended

opaque (we have no clue what is inside this segment of information, or not yet decoded)

ignore (we just ignore this information and it is not copied to the XStructure object)



### The XStructure can read the data as big-endian (Motorola-type) or little-endian (Intel-type) for numeric data (integers and timestamp)

- Big endian:

Multibyte value on N bytes:

  value = (byte[0] << 8*(n-1)) | (byte[1] << 8*(n-2)) | ... | byte[n-1];

- Little endian:

Multibyte value on N bytes:

  value = (byte[0] | (byte[1] << 8) | ... | (byte[n-1] << 8*(n-1));
          

Warning: PHP7 does not recognize uint, so you will obtain most likely a signed INT (negative) if it is overflowed on uint64, or a float.

*** Note for the programmers: implement here BCMath, GMP, etc ?

The descriptor is sent to the XStructure with the data to decode.

### Data Structure Descriptor:


The descriptor has the following structure:

#### Simple parameters:

```
$descriptor = array(
  'main' => 'ParamName1',
  'ParamName1' => array('cast' => '[cast]', 'endian' => 'little', 'pos' => POS, 'length' => LENGTH),
  'ParamName2' => array('cast' => '[cast]', 'endian' => 'little', 'pos' => POS, 'length' => LENGTH),
  'ParamName3' => array('cast' => 'ParamName4', 'endian' => 'little', 'pos' => POS, 'length' => LENGTH),
  ...
);
```

The 'main' parameter points to the main structure to use to extract the data. It is mandatory.


[cast] can be:

- char, byte, uint8, uint16, uint24, uint32, uint64, timestamp, string, string0, hex, opaque, ignore
- It can also be another ParamNameX defined elsewhere into the descriptor.


Default byte order is "commonplace network byte order" or big-endian. If your datastream has little-endian structure, you'll have to specify it explicitely.

POS Is the position in the datastream of our data. It is optional.
If it is not present it will be automatically incremented based on the previous parameter descriptor.

LENGTH is optional if we know the size of the data (bytes, integers, timestamp, string0)
It is mandatory for string, hex, opaque and ignore.


To define a new structure, just build the inner parameters into the named array:

```
  'ParamName4' => array(
     'SubParam1' => array('cast' => '[cast]', 'endian' => 'little', 'pos' => POS, 'length' => LENGTH),
     'SubParam2' => array('cast' => '[cast]', 'endian' => 'little', 'pos' => POS, 'length' => LENGTH),
     'SubParam3' => array('cast' => '[cast]', 'endian' => 'little', 'pos' => POS, 'length' => LENGTH),
     ...
   )
```


#### Conditional Parameters and Sub-Structures:

It is usual that the structure is based on a type value or so at the beginning of the datastream.
It is like having a structure into a superstructure and is very usual in any datastreams as put into the examples.
SSL is one of them TCP/IP is another one, etc.

In this case, the descriptor have this format:

```
$descriptor = array(
  'ParamNameX' => array('cast' => '[cast]', 'endian' => 'little', 'pos' => POS, 'length' => LENGTH),
  'ParamNameXL' => array('cast' => '[cast]', 'endian' => 'little', 'pos' => POS, 'length' => LENGTH),
  'ParamNameY' => array('cast' => '[cast]',
                        'conditionparam' => 'ParamNameX',
                        'conditionvalue' => N,
                        'pos' => POS,
                        'length' => LENGTH | 'ParamNameXL'
                        ),
  'ParamNameZ' => array('cast' => '[cast]',
                        'conditionparam' => 'ParamNameX',
                        'conditionvalue' => M,
                        'pos' => POS,
                        'length' => LENGTH | 'ParamNameXL'
                        )
);
```

This can be read like this:
- IF the ParamNameX has the value N, then the ParamNameY will be extracted from the datastream, otherwise NO
- IF the ParamNameX has the value M, then the ParamNameZ will be extracted from the datastream, otherwise NO
The ParamNameX MUST be extracted BEFORE conditional structures
The length of the sub-structure to use (ParamNameY, ParamNameZ) can be set with a parameter defined BEFORE its definition.

See the SSL example for superstructures and sub-conditional structures.

#### Vectors

Add the keyword "vector" => true to a structure to turn it into a vector.

The vector will read tue quantity of substructures pointed by length.

The length can be a quantity of substrctures, or the size of the complete vector ( in this case, add the "vectortype" => "bytes" keyword)

If the length is quantity of bytes, so the position of the end os the structure MUST be the same as the next parameter, otherwise an error is thrown.

Example: (See the TLS1.2 structure for more examples)

```
    'servernamelist' => array(
      'length' => array('cast' => 'uint16'),
      'servernames' => array('cast' => 'servername', 'vector' => true, 'length' => 'length', 'lengthtype' => 'bytes')
    ),
    
    'servername' => array(
      'nametype' => array('cast' => 'uint8'),
      'length' => array('cast' => 'uint16'),
      'hostname' => array('cast' => 'string', 'length' => 'length')
    )
```


### Object and Data Access:

To create the XStructure, just create it with its descriptor and data:

```
$X = new \xstructure\XStructure($descriptor, $data);
```

To access to the data in the XStructure object, you just have to point the needed attribute:

If:
``` 
$descriptor = array(
  'ParamName1' => array('cast' => '[cast]', 'endian' => 'little', 'pos' => POS, 'length' => LENGTH),
  'ParamName2' => array('cast' => '[cast]', 'endian' => 'little', 'pos' => POS, 'length' => LENGTH),
  'ParamName3' => array('cast' => 'ParamName4', 'endian' => 'little', 'pos' => POS, 'length' => LENGTH),
  ...
);
```

Use:
```
echo $X->ParamName1;
echo $X->ParamName2;
etc.
```

When you define a subtructure, you can use it in cascade:
```
echo $X->ParamName3->SubParam1;
echo $X->ParamName3->SubParam2;
etc.
```

### Encapsulation:


It is usefull to encapsulate and extend the XStructure into a well-known structure to avoid descriptors everywhere and datastreams:

For example:

```
class BitCoinBlock extends \xstructure\XStructure
{
  private $descriptor = array(
    ... // (see examples)
  );
  
  public function __construct($filename)
  {
    parent::__construct(file_get_contents($filename), $this->descriptor);
  }
}

$BTCBlock = new BitCoinBlock('/temp/block00000.dat');
echo $BTCBlock->MerkleRoot;
```

---