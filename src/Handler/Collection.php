<?php
/**
 * @author     Ni Irrty <niirrty+code@gmail.com>
 * @copyright  © 2017-2021, Niirrty
 * @package    Niirrty\Plate\Handler
 * @since      2017-11-04
 * @version    0.4.0
 */


declare( strict_types=1 );


namespace Niirrty\Plate\Handler;


use \Niirrty\ArgumentException;


class Collection implements IHandler, \ArrayAccess, \Iterator, \Countable
{


    #region // – – –   P R I V A T E   F I E L D S   – – – – – – – – – – – – – – – – – – – – – – – –

    private array $data;

    private int $position;

    #endregion


    #region // – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –

    public function __construct()
    {

        $this->data = [];
        $this->position = 0;

    }

    #endregion


    #region // – – –   P U B L I C   M E T H O D S   – – – – – – – – – – – – – – – – – – – – – – – –

    /**
     * @param string $contents
     *
     * @return string
     */
    public function execute( string $contents ): string
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

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists( $offset ): bool
    {

        return isset( $this->data[ $offset ] );

    }

    /**
     * @param int $offset
     *
     * @return IHandler
     */
    public function offsetGet( $offset ): IHandler
    {

        return $this->data[ $offset ];

    }

    /**
     * @param int|null $offset
     * @param IHandler $value
     *
     * @throws ArgumentException
     */
    public function offsetSet( $offset, $value )
    {

        if ( null === $value || !( $value instanceof IHandler ) )
        {
            throw new ArgumentException(
                'value',
                $value,
                'Only values that implement \Niirrty\Plate\Handler\IHandler are valid!'
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
            $this->data[ $offset ] = $value;
        }

    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset( $offset )
    {

        unset( $this->data[ $offset ] );

    }

    /**
     * @return IHandler|null
     */
    public function current(): ?IHandler
    {

        return $this->data[ $this->position ] ?? null;

    }

    public function key(): ?int
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

    public function valid(): bool
    {

        return \count( $this->data ) > $this->position;

    }

    public function count(): int
    {

        return \count( $this->data );

    }

    #endregion


}

