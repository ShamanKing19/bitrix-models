<?php

namespace Shaman\D7Models;

use Bitrix\Iblock\PropertyTable;

class Property extends BaseModel
{
    public static string $table = PropertyTable::class;

    /**
     * ID свойства
     *
     * @return int
     */
    public function getId(): int
    {
        return (int)$this->getField('ID');
    }

    /**
     * Символьный код
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->getField('CODE');
    }

    /**
     * ID инфоблока
     *
     * @return int
     */
    public function getIblockId(): int
    {
        return (int)$this->getField('IBLOCK_ID');
    }

    /**
     * Название свойства
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->getField('NAME');
    }

    /**
     * Тип свойства
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->getField('PROPERTY_TYPE');
    }

    /**
     * Пользовательский тип свойства
     *
     * @return string|null
     */
    public function getUserType(): ?string
    {
        return $this->getField('USER_TYPE') ?: null;
    }

    /**
     * Пользовательские настройки
     *
     * @return array
     */
    public function getUserSettings(): array
    {
        return $this->getField('USER_TYPE_SETTINGS_LIST') ?? [];
    }

    /**
     * Проверка: является ли свойства активным
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->getField('ACTIVE') === 'Y';
    }

    /**
     * Проверка: является ли свойство множественным
     *
     * @return bool
     */
    public function isMultiple(): bool
    {
        return $this->getField('MULTIPLE') === 'Y';
    }

    /**
     * Проверка: является ли свойство обязательным
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->getField('IS_REQUIRED') === 'Y';
    }

    /**
     * Проверка: является ли свойство простой строкой
     *
     * @return bool
     */
    public function isString(): bool
    {
        return $this->getType() === PropertyTable::TYPE_STRING && empty($this->getUserType());
    }

    /**
     * Проверка: является ли свойство "Числом"
     *
     * @return bool
     */
    public function isNumber(): bool
    {
        return $this->getType() === PropertyTable::TYPE_NUMBER;
    }

    /**
     * Проверка: является ли свойство "Списком"
     *
     * @return bool
     */
    public function isList(): bool
    {
        return $this->getType() === PropertyTable::TYPE_LIST;
    }

    /**
     * Проверка: является ли свойство "Файлом"
     *
     * @return bool
     */
    public function isFile(): bool
    {
        return $this->getType() === PropertyTable::TYPE_FILE;
    }

    /**
     * Проверка: является ли свойство "Привязкой к элементу"
     *
     * @return bool
     */
    public function isElementLink(): bool
    {
        return $this->getType() === PropertyTable::TYPE_ELEMENT;
    }

    /**
     * Проверка: является ли свойство "Привязкой к разделу"
     *
     * @return bool
     */
    public function isSectionLink(): bool
    {
        return $this->getType() === PropertyTable::TYPE_SECTION;
    }

    /**
     * Проверка: является ли свойство "Справочником"
     *
     * @return bool
     */
    public function isHighloadLink(): bool
    {
        return $this->getType() === PropertyTable::TYPE_STRING && $this->getUserType() === 'directory';
    }


    /**
     * Название таблицы, если свойство является "Справочником"
     *
     * @return string
     */
    public function getTableName(): string
    {
        if (!$this->isHighloadLink()) {
            throw new \LogicException('Метод можно использовать только для свойства типа "Справочник"');
        }

        return $this->getUserSettings()['TABLE_NAME'] ?? '';
    }
}
