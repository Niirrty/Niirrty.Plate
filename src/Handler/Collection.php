<?php


declare( strict_types=1 );


namespace Niirrty\Plate\Handler;


use Niirrty\ArgumentException;


class Collection implements IHandler, \ArrayAccess, \Iterator, \Countable
{


   // <editor-fold desc="// – – –   P R I V A T E   F I E L D S   – – – – – – – – – – – – – – – – – – – – – – – –">

   private $data;
   private $position;

   // </editor-fold>


   // <editor-fold desc="// – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –">


   public function __construct()
   {

      $this->data     = [];
      $this->position = 0;

   }

   // </editor-fold>


   // <editor-fold desc="// – – –   P U B L I C   M E T H O D S   – – – – – – – – – – – – – – – – – – – – – – – –">

   /**
    * @param  string $contents
    * @return string
    */
   public function execute( string $contents ) : string
   {

      if ( \count( $this->data ) < 1 )
      {
         return $contents;
      }

      foreach ( $this->data as $handler )
      {
          $contents = $handler->execute( $contents );
      }

      return $contents;

   }

   public function offsetExists( $offset )
   {
      return isset( $this->data[ $offset ] );
   }

   /**
    * @param int $offset
    * @return IHandler
    */
   public function offsetGet( $offset )
   {
      return $this->data[ $offset ];
   }

   /**
    * @param int|null $offset
    * @param IHandler $value
    * @throws \Niirrty\ArgumentException
    */
   public function offsetSet( $offset, $value )
   {

      if ( null === $value || ! ( $value instanceof IHandler ) )
      {
         throw new ArgumentException(
            'value',
            $value,
            'Only values that implement \OSF\Stemp\Handler\IHandler ar valid!'
         );
      }

      if ( \is_null( $offset ) )
      {
         $this->data[] = $value;
      }
      else if ( ! \is_int( $offset ) )
      {
         $this->data[] = $value;
      }
      else
      {
         $this->data[$offset] = $value;
      }

  }

   public function offsetUnset($offset)
   {

      unset( $this->data[ $offset ] );

   }

   /**
    * @return IHandler
    */
   public function current()
   {

      return $this->data[ $this->position ];

   }

   public function key()
   {
      return $this->position;
   }
   public function next()
   {
      ++$this->position;
   }
   public function rewind()
   {
      $this->position = 0;
   }
   public function valid()
   {
      return \count( $this->data ) > $this->position;
   }
   public function count()
   {
      return \count( $this->data );
   }

   // </editor-fold>


}

