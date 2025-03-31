<?php

namespace Shaman\D7Models;

use Bitrix\Catalog\StoreTable;
use Bitrix\Main\Type\DateTime;

class Store extends BaseModel
{
    public static string $table = StoreTable::class;

    public function getId(): int
    {
        return (int)parent::getId();
    }

    /**
     * Название
     *
     * @return string
     */
    public function getTitle(): string
    {
        if ($name = $this->getField('TITLE')) {
            return $name;
        }

        throw new \RuntimeException('Не заполнено поле "TITLE" у склада с id=' . $this->getId());
    }

    /**
     * Символьный код
     *
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->getField('CODE') ?: null;
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
     * Проверка: активен ли склад
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->getField('ACTIVE') === 'Y';
    }

    /**
     * Проверка: является ли складом по-умолчанию
     *
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->getField('IS_DEFAULT') === 'Y';
    }

    /**
     * Проверка: является ли пунктом выдачи
     *
     * @return bool
     */
    public function isIssuingCenter(): bool
    {
        return $this->getField('ISSUING_CENTER') === 'Y';
    }

    /**
     * Проверка: можно ли совершать отгрузку
     *
     * @return bool
     */
    public function isShippingCenter(): bool
    {
        return $this->getField('SHIPPING_CENTER') === 'Y';
    }

    /**
     * Сайт, которому принадлежит склад
     *
     * @return string|null
     */
    public function getSiteId(): ?string
    {
        return $this->getField('SITE_ID') ?: null;
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
     * Адрес
     *
     * @return string
     */
    public function getAddress(): string
    {
        return $this->getField('ADDRESS');
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
     * Номер телефона
     *
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->getField('PHONE') ?: null;
    }

    /**
     * Почта
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->getField('EMAIL') ?: null;
    }

    /**
     * Расписание
     *
     * @return string|null
     */
    public function getSchedule(): ?string
    {
        return $this->getField('SCHEDULE') ?: null;
    }

    /**
     * Широта
     *
     * @return float|null
     */
    public function getLatitude(): ?float
    {
        return (float)$this->getField('GPS_N') ?: null;
    }

    /**
     * Долгота
     *
     * @return float|null
     */
    public function getLongitude(): ?float
    {
        return (float)$this->getField('GPS_S') ?: null;
    }

    /**
     * ID изображения
     *
     * @return int|null
     */
    public function getImageId(): ?int
    {
        return (int)$this->getField('IMAGE_ID') ?: null;
    }

    /**
     * ID местоположения
     *
     * @return int|null
     */
    public function getLocationId(): ?int
    {
        return (int)$this->getField('LOCATION_ID') ?: null;
    }

    /**
     * Дата создания
     *
     * @return DateTime
     */
    public function getDateCreated(): DateTime
    {
        return $this->getField('DATE_CREATE');

    }

    /**
     * Дата последнего изменения
     *
     * @return DateTime
     */
    public function getDateModified(): DateTime
    {
        return $this->getField('DATE_MODIFY');
    }

    /**
     * ID создателя
     *
     * @return int|null
     */
    public function getCreatedUserId(): ?int
    {
        return (int)$this->getField('USER_ID') ?: null;
    }

    /**
     * ID последнего пользователя, изменившего запись
     *
     * @return int|null
     */
    public function getModifiedUserId(): ?int
    {
        return (int)$this->getField('MODIFIED_BY') ?: null;
    }

    protected static function getUfTableName(): string
    {
        return 'b_uts_cat_store';
    }
}
