<?php

namespace Shaman\Repositories;

use Bitrix\Catalog\PriceTable;
use Shaman\Contracts\Makeable;

class PriceRepository implements Makeable
{
    public static function make(): self
    {
        return new self();
    }

    /**
     * Словарь из id элементов и словаря из кода цены и её значения
     *
     * @param array<int> $elementIdList
     * @param array<string> $priceTypeList
     *
     * @return array<int,array<string,int>>
     */
    public function getElementIdToPriceTypesMap(array $elementIdList = [], array $priceTypeList = []): array
    {
        $filter = [];
        if ($elementIdList) {
            $filter['=PRODUCT_ID'] = $elementIdList;
        }
        if ($priceTypeList) {
            $filter['=TYPE'] = $priceTypeList;
        }

        $request = PriceTable::getList([
            'filter' => $filter,
            'select' => [
                'PRODUCT_ID',
                'PRICE',
                'TYPE' => 'CATALOG_GROUP.NAME'
            ],
        ]);

        $elementIdToPriceListMap = [];
        while ($row = $request->fetch()) {
            $elementIdToPriceListMap[(int)$row['PRODUCT_ID']][$row['TYPE']] = $row;
        }

        $map = [];
        foreach ($elementIdToPriceListMap as $elementId => $priceList) {
            foreach ($priceList as $priceRow) {
                $map[$elementId][$priceRow['TYPE']] = (int)$priceRow['PRICE'];
            }

            unset($elementIdToPriceListMap[$elementId]);
        }

        return $map;
    }
}
