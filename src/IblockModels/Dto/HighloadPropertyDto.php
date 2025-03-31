<?php

namespace Shaman\IblockModels\Dto;

class HighloadPropertyDto
{
    /** @var int|string|array */
    public $xmlId;

    /** * @var int|string|array */
    public $valueId;

    /**
     * @param int|string|array $xmlId
     * @param int|string|array $valueId
     */
    public function __construct($xmlId, $valueId)
    {
        $this->xmlId = $xmlId;
        $this->valueId = $valueId;
    }
}
