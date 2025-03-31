<?php

namespace Shaman\Contracts;

interface Makeable
{
    /**
     * Создание объекта с зависимостями по умолчанию
     *
     * @return static
     */
    public static function make();
}
