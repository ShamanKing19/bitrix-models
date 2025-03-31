<?php

namespace Shaman\Repositories;

use Shaman\Contracts\Makeable;

class IblockElementRepository implements Makeable
{
    public static function make(): self
    {
        return new self();
    }

    /**
     * Подгрузка свойств элементам инфоблока
     *
     * @param int $iblockId
     * @param array<array> $elementList Список элементов инфоблока (ОБЯЗАТЕЛЬНО С КЛЮЧОМ "PROPERTIES" = [])
     * @param array $propertyFilter Фильтр для свойств, значения которых нужно получить
     * @param array $propertySelect Поля свойств, которые нужно получить
     * @param bool $escapeValues Нужно ли применять htmlspecialCharsEx для значений
     */
    public function attachProperties(
        int $iblockId,
        array &$elementList,
        array $propertyFilter = [],
        array $propertySelect = [],
        bool $escapeValues = false
    ): void
    {
        \CIBlockElement::GetPropertyValuesArray(
            $elementList,
            $iblockId,
            [
                'IBLOCK_ID' => $iblockId,
                'ID' => array_column($elementList, 'ID')
            ],
            $propertyFilter,
            [
                'PROPERTY_FIELDS' => $propertySelect,
                'GET_RAW_DATA' => $escapeValues ? 'N' : 'Y'
            ]
        );
    }

    /**
     * Битриксовый запрос в базу данных
     *
     * @param array $order
     * @param array $filter
     * @param array $select
     * @param int $limit
     * @param int $pageNumber
     *
     * @return \CIBlockResult
     */
    public function performRequest(array $order, array $filter, array $select, int $limit = 0, int $pageNumber = 0): \CIBlockResult
    {
        $pagination = [];
        if ($limit > 0) {
            if ($pageNumber > 0) {
                $pagination['iNumPage'] = $pageNumber;
                $pagination['nPageSize'] = $limit;
            } else {
                $pagination['nTopCount'] = $limit;
            }
        }

        // Выборка обязательных полей
        if (!in_array('*', $select, true)) {
            foreach (['ID', 'IBLOCK_ID'] as $field) {
                if (!in_array($field, $select, true)) {
                    $select[] = $field;
                }
            }
        }

        return \CIBlockElement::GetList($order, $filter, false, $pagination, $select);
    }
}
