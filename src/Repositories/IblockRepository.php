<?php

namespace Shaman\Repositories;

use Bitrix\Main\Application;
use Shaman\Contracts\Makeable;
use Shaman\D7Models\Iblock;

class IblockRepository implements Makeable
{
    protected static $cacheTtl = 86400;

    public static function make(): self
    {
        return new self();
    }

    public function getById(int $id): Iblock
    {
        return Iblock::query()
            ->filter(['=ID' => $id])
            ->cache(self::$cacheTtl)
            ->first();
    }

    public function getByCode(string $code): Iblock
    {
        return Iblock::query()
            ->filter(['=CODE' => $code])
            ->cache(self::$cacheTtl)
            ->first();
    }

    public function getIdByCode(string $code): ?int
    {
        $iblock = Iblock::query()
            ->select(['ID'])
            ->filter(['=CODE' => $code])
            ->cache(self::$cacheTtl)
            ->first();

        return $iblock ? $iblock->getId() : null;
    }

    /**
     * Таблица с одиночными значениями
     *
     * @return string
     */
    public function getPropertySingleValuesTable(Iblock $iblock): string
    {
        $version = $iblock->getVersion();
        $tableName = $version === 2 ? 'b_iblock_element_prop_s' . $iblock->getId() : 'b_iblock_element_property';
        $connection = Application::getConnection();
        if ($version === 2 && !$connection->isTableExists($tableName)) {
            $tableName = 'b_iblock_element_property';
        }

        return $tableName;
    }

    /**
     * Таблица с множественными значениями
     *
     * @return string
     */
    public function getPropertyMultipleValuesTable(Iblock $iblock): string
    {
        $version = $iblock->getVersion();
        $tableName = $version === 2 ? 'b_iblock_element_prop_m' . $iblock->getId() : 'b_iblock_element_property';
        if ($version === 2 && !Application::getConnection()->isTableExists($tableName)) {
            $tableName = 'b_iblock_element_property';
        }

        return $tableName;
    }
}
