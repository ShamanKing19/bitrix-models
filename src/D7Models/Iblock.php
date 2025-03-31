<?php

namespace Shaman\D7Models;

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Type\DateTime;

class Iblock extends BaseModel
{
    public static string $table = IblockTable::class;

    public function getId(): int
    {
        return (int)parent::getId();
    }

    /**
     * Тип инфоблока
     *
     * @return string
     */
    public function getTypeId(): string
    {
        return $this->getField('IBLOCK_TYPE_ID');
    }

    /**
     * ID сайта
     *
     * @return string
     */
    public function getSiteId(): string
    {
        return $this->getField('LID');
    }

    /**
     * Символьный идентификатор
     *
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->getField('CODE') ?: null;
    }

    /**
     * Код API
     *
     * @return string|null
     */
    public function getApiCode(): ?string
    {
        return $this->getField('API_CODE') ?: null;
    }

    /**
     * Название
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->getField('NAME');
    }

    /**
     * Проверка: активен ли инфоблок
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->getField('ACTIVE') === 'Y';
    }

    /**
     * Сортировка
     *
     * @return int
     */
    public function getSort(): int
    {
        return (int)$this->getField('SORT');
    }

    /**
     * Дата последнего изменения параметров блока
     *
     * @return DateTime
     */
    public function getDateModified(): DateTime
    {
        return $this->getField('TIMESTAMP_X');
    }

    /**
     * Шаблон URL-а к странице для публичного просмотра списка элементов информационного блока
     *
     * @return string|null
     */
    public function getListPageUrl(): ?string
    {
        return $this->getField('LIST_PAGE_URL') ?: null;
    }

    /**
     * Шаблон URL-а странице для детального просмотра элемента
     *
     * @return string|null
     */
    public function getDetailPageUrl(): ?string
    {
        return $this->getField('DETAIL_PAGE_URL') ?: null;
    }

    /**
     * Шаблон URL-а к странице для просмотра раздела
     *
     * @return string|null
     */
    public function getSectionPageUrl(): ?string
    {
        return $this->getField('SECTION_PAGE_URL') ?: null;
    }

    /**
     * Канонический URL элемента
     *
     * @return string|null
     */
    public function getCanonicalPageUrl(): ?string
    {
        return $this->getField('CANONICAL_PAGE_URL') ?: null;
    }

    /**
     * ID изображения
     *
     * @return int|null
     */
    public function getPictureId(): ?int
    {
        return (int)$this->getField('PICTURE') ?: null;
    }

    /**
     * Описание
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->getField('DESCRIPTION') ?: null;
    }

    /**
     * Тип описания
     *
     * @return string 'text'|'html'
     */
    public function getDescriptionType(): string
    {
        return $this->getField('DESCRIPTION_TYPE');
    }

    /**
     * Внешний код
     *
     * @return string|null
     */
    public function getXmlId(): ?string
    {
        return $this->getField('XML_ID') ?: null;
    }

    /**
     * Индексировать для поиска элементы информационного блока
     *
     * @return bool
     */
    public function indexElements(): bool
    {
        return $this->getField('INDEX_ELEMENT') === 'Y';
    }

    /**
     * Индексировать для поиска разделы информационного блока
     *
     * @return bool
     */
    public function indexSections(): bool
    {
        return $this->getField('INDEX_SECTION') === 'Y';
    }

    /**
     * Признак наличия фасетного индекса
     *
     * @return bool
     */
    public function indexProperties(): bool
    {
        return $this->getField('PROPERTY_INDEX') === 'Y';
    }

    /**
     * Инфоблок участвует в документообороте
     *
     * @return bool
     */
    public function workflow(): bool
    {
        return $this->getField('WORKFLOW') === 'Y';
    }

    /**
     * Инфоблок участвует в бизнес-процессах
     *
     * @return bool
     */
    public function bizproc(): bool
    {
        return $this->getField('BIZPROC') === 'Y';
    }

    /**
     * Флаг выбора интерфейса для отображения привязки элемента к разделам
     *
     * @return string|null 'L'|'D'|'P'
     */
    public function getSectionChooser(): ?string
    {
        return $this->getField('SECTION_CHOOSER') ?: null;
    }

    /**
     * Режим отображения списка элементов в административном разделе
     *
     * @return string|null 'C'|'S'
     */
    public function getListMode(): ?string
    {
        return $this->getField('LIST_MODE') ?: null;
    }

    /**
     * Режим проверки прав доступа
     *
     * @return mixed 'S'|'E'
     */
    public function getRightsMode(): ?string
    {
        return $this->getField('RIGHTS_MODE');
    }

    /**
     * Признак наличия привязки свойств к разделам
     *
     * @return bool
     */
    public function canAttachPropertyToSection(): bool
    {
        return $this->getField('SECTION_PROPERTY') === 'Y';
    }

    /**
     * Версия
     *
     * @return int 1|2
     */
    public function getVersion(): int
    {
        return (int)$this->getField('VERSION');
    }
}
