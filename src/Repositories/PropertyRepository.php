<?php

namespace Shaman\Repositories;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Data\DataManager;
use Shaman\Contracts\Makeable;
use Shaman\D7Models\Property;
use Shaman\Exceptions\NotFoundException;

class PropertyRepository implements Makeable
{
    private static array $instanceList = [];

    private array $idKeyListValues;
    private array $xmlIdKeyListValues;

    public static function make(): self
    {
        return new self();
    }

    /**
     * Общая информация о свойстве
     *
     * @param int $iblockId
     * @param string $code
     *
     * @return array|null
     */
    public function findByCode(int $iblockId, string $code): ?array
    {
        if (isset(self::$instanceList[$iblockId][$code])) {
            return self::$instanceList[$iblockId][$code];
        }

        return self::$instanceList[$iblockId][$code] = Property::query()->filter([
            'filter' => [
                '=IBLOCK_ID' => $iblockId,
                '=CODE' => $code
            ]
        ])->first();
    }

    /**
     * @param int $iblockId
     *
     * @return array<Property>
     */
    public function getAll(int $iblockId): array
    {
        return Property::query()->filter(['=IBLOCK_ID' => $iblockId])->get();
    }

    /**
     * @param int $iblockId
     * @param int $id
     *
     * @return Property|null
     */
    public function getById(int $iblockId, int $id): ?Property
    {
        return Property::query()->filter(['=IBLOCK_ID' => $iblockId, '=ID' => $id])->first();
    }

    /**
     * Попытка найти свойство
     *
     * @param int $iblockId
     * @param string $code
     *
     * @return Property
     *
     * @throws NotFoundException
     */
    public function tryFindByCode(int $iblockId, string $code): Property
    {
        $property = $this->getByCode($iblockId, $code);
        if ($property === null) {
            throw new NotFoundException("Свойство \"$code\" в инфоблоке \"$iblockId\" не найдено");
        }

        return $property;
    }

    /**
     * @param int $iblockId
     * @param string $code
     *
     * @return Property|null
     */
    public function getByCode(int $iblockId, string $code): ?Property
    {
        return Property::query()->filter(['=IBLOCK_ID' => $iblockId, '=CODE' => $code])->first();
    }

    /**
     * Записи свойства типа "Справочник" по списку XML_ID
     *
     * @param int $iblockId
     * @param string $propertyCode
     * @param array<string> $xmlIdList
     *
     * @return array<string,array{
     *     'ID': int,
     *     'UF_XML_ID': string,
     *     'UF_NAME': string,
     *     'UF_DESCRIPTION': string,
     *     ...
     * }>
     *
     * @throws NotFoundException
     */
    public function getHighloadItemsMap(int $iblockId, string $propertyCode, array $xmlIdList): array
    {
        $property = $this->tryFindByCode($iblockId, $propertyCode);
        $table = $this->getTableEntity($property);
        $request = $table::getList(['filter' => ['=UF_XML_ID' => $xmlIdList]]);

        $map = [];
        while ($row = $request->fetch()) {
            $map[$row['UF_XML_ID']] = $row;
        }

        return $map;
    }

    /**
     * Значение по его id для "Списков"
     *
     * @param int $id
     *
     * @return array{
     *     'ID': int,
     *     'PROPERTY_ID': int,
     *     'VALUE': string,
     *     'DEF': string,
     *     'SORT': int,
     *     'XML_ID': string,
     *     'TMP_ID': string
     * }
     *
     * @throws NotFoundException
     */
    public function getListValueById(int $iblockId, string $propertyCode, int $id): array
    {
        $property = $this->tryFindByCode($iblockId, $propertyCode);
        if (!$property->isList()) {
            throw new \LogicException('Метод можно использовать только со свойствами "Список"');
        }

        $values = $this->getListValues($property, true);

        return $values[$id] ?? [];
    }

    /**
     * Значение по его XML_ID для "Списков" и "Справочников"
     *
     * @param string $xmlId
     *
     * @return array{
     *     'ID': int,
     *     'PROPERTY_ID': int,
     *     'VALUE': string,
     *     'DEF': string,
     *     'SORT': int,
     *     'XML_ID': string,
     *     'TMP_ID': string
     *  }
     */
    public function getListValueByXmlId(int $iblockId, string $propertyCode, string $xmlId): array
    {
        $property = $this->tryFindByCode($iblockId, $propertyCode);
        if (!$property->isList()) {
            throw new \LogicException('Метод можно использовать только со свойствами "Список"');
        }

        $values = $this->getListValues($property);

        return $values[$xmlId] ?? [];
    }

    /**
     * Все значения свойства типа "Список"
     *
     * @param Property $property
     * @param bool $idKey
     *
     * @return array<array{
     *     'ID': int,
     *     'PROPERTY_ID': int,
     *     'VALUE': string,
     *     'DEF': string,
     *     'SORT': int,
     *     'XML_ID': string,
     *     'TMP_ID': string
     * }>
     */
    public function getListValues(Property $property, bool $idKey = false): array
    {
        if ($idKey && isset($this->idKeyListValues)) {
            return $this->idKeyListValues;
        }

        if (!$idKey && isset($this->xmlIdKeyListValues)) {
            return $this->xmlIdKeyListValues;
        }

        if (!$property->isList()) {
            throw new \LogicException('Метод можно использовать только для свойства типа "Список"');
        }

        $listPropertyValueQuery = PropertyEnumerationTable::getList([
            'filter' => ['=PROPERTY_ID' => $property->getId()],
            'select' => ['ID', 'VALUE', 'XML_ID', 'SORT'],
            'order' => ['SORT' => 'ASC']
        ]);

        $idToValuesMap = [];
        $xmlIdToValuesMap = [];
        while ($listPropertyValue = $listPropertyValueQuery->fetch()) {
            $listPropertyValue['ID'] = (int)$listPropertyValue['ID'];
            $listPropertyValue['SORT'] = (int)$listPropertyValue['SORT'];
            $idToValuesMap[$listPropertyValue['ID']] = $listPropertyValue;
            $xmlIdToValuesMap[$listPropertyValue['XML_ID']] = $listPropertyValue;
        }

        $this->idKeyListValues = $idToValuesMap;
        $this->xmlIdKeyListValues = $xmlIdToValuesMap;

        return $idKey ? $idToValuesMap : $xmlIdToValuesMap;
    }

    /**
     * ID таблицы, если свойство является "Справочником"
     *
     * @param Property $property
     *
     * @return int
     *
     * @throws NotFoundException
     */
    public function getHighloadTableId(Property $property): int
    {
        if (!$property->isHighloadLink()) {
            throw new \LogicException('Метод можно использовать только для свойства типа "Справочник"');
        }

        Loader::includeModule('highloadblock');
        $tableName = $property->getTableName();
        $table = HighloadBlockTable::getList([
            'filter' => ['=TABLE_NAME' => $tableName],
            'select' => ['ID'],
            'limit' => 1
        ])->fetch();

        if (empty($table)) {
            throw new NotFoundException("Таблица \"$tableName\" не найдена");
        }

        return (int)$table['ID'];
    }

    /**
     * Сущность таблицы, если свойство является "Справочником"
     *
     * @return class-string<DataManager>
     */
    public function getTableEntity(Property $property): string
    {
        if (!$property->isHighloadLink()) {
            throw new \LogicException('Метод можно использовать только для свойства типа "Справочник"');
        }

        Loader::includeModule('highloadblock');
        $tableName = $property->getTableName();
        $table = HighloadBlockTable::getList([
            'filter' => ['=TABLE_NAME' => $tableName],
            'select' => ['ID']
        ])->fetch();

        return HighloadBlockTable::compileEntity($table['ID'])->getDataClass();
    }
}
