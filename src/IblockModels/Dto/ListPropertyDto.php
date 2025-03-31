<?php

namespace Shaman\IblockModels\Dto;

class ListPropertyDto
{
    /** @var null|int|string|array */
    public $displayValue;
    /** @var null|int|string|array */
    public $enumValue;
    /** @var null|int|string|array */
    public $enumIdValue;
    /** @var null|int|string|array */
    public $xmlIdValue;

    /**
     * @param null|int|string|array $displayValue
     * @param null|int|string|array $enumValue
     * @param null|int|string|array $enumIdValue
     * @param null|int|string|array $xmlIdValue
     */
    public function __construct($displayValue, $enumValue, $enumIdValue, $xmlIdValue)
    {
        $this->displayValue = $displayValue;
        $this->enumValue = $enumValue;
        $this->enumIdValue = $enumIdValue;
        $this->xmlIdValue = $xmlIdValue;
    }
}
