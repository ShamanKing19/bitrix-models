<?php

namespace Shaman\IblockModels\Dto;

class ListPropertyDto
{
    /** @var null|int|string|array */
    public $value;
    /** @var null|int|string|array */
    public $valueEnum;
    /** @var null|int|string|array */
    public $valueEnumId;
    /** @var null|int|string|array */
    public $xmlId;

    /**
     * @param null|int|string|array $valueName
     * @param null|int|string|array $enumValue
     * @param null|int|string|array $enumIdValue
     * @param null|int|string|array $xmlId
     */
    public function __construct($valueName, $enumValue, $enumIdValue, $xmlId)
    {
        $this->value = $valueName;
        $this->valueEnum = $enumValue;
        $this->valueEnumId = $enumIdValue;
        $this->xmlId = $xmlId;
    }
}
