<?php
/**
 * @package Niirrty\Plate\TagParser
 * @version 0.4.0
 * @since   2021-07-03
 * @author  Ni Irrty <niirrty+code@gmail.com>
 */


declare( strict_types=1 );


namespace Niirrty\Plate\TagParser;


interface IPlateTagParser
{

    /**
     * Gets the tag identifier character (only a single character is accepted)
     *
     * @return string
     */
    public function getIdentifier() : string;

    /**
     * Gets all parts of the tag, excluding open chars, close chars, identifier, and filters.
     *
     * @return array
     */
    public function getData() : array;

    /**
     * Gets all filters, defined by the tag.
     *
     * @return array
     */
    public function getFilters() : array;

    /**
     * Return if the tag is already parsed.
     *
     * @return bool
     */
    public function isParsed() : bool;

    /**
     * Gets, if the tag is parsed, the compiled tag data, null otherwise.
     *
     * @return PlateTagCompiled|null
     */
    public function getCompiled() : ?PlateTagCompiled;

    /**
     * Parse the tag definition.
     *
     * @param string $tagDefinition
     * @param string $afterTagClose
     * @param string $newLineAfter
     * @param string|null $package
     * @return bool
     */
    public function parse(
        string $tagDefinition, string $afterTagClose = '', string $newLineAfter = '', ?string $package = null ) : bool;

}