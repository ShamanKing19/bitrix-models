<?php

namespace Shaman\IblockModels;

use ArrayAccess;
use Bitrix\Iblock\ORM\CommonElementTable;
use Bitrix\Main\Entity\DataManager;
use DateTime;
use Shaman\IblockModels\Dto\HighloadPropertyDto;
use Shaman\IblockModels\Dto\ListPropertyDto;

/**
 * <h1>Модель инфоблока</h2>
 *
 * <h2>Для начала работы нужно</h3>
 * <ol>
 *     <li>Унаследоваться от данного класса</li>
 *     <li>Переопределить "<b>$iblockId</b>", "<b>$iblockCode</b>" или "<b>$table</b>"</li>
 *     <li>Для работы с торговыми предложениями нужно указать их модель: "<b>protected static bool $skuModel = SomeSkuModel::class;</b>"</li>
 * </ol>
 *
 * @template-covariant sku_template
 */
abstract class BaseIblockModel implements ArrayAccess
{
    /** @var int ID Инфоблока */
    protected static int $iblockId;

    /** @var string Символьный код инфоблока */
    protected static string $iblockCode;

    /** @var class-string<DataManager> Таблица для запросов по API */
    protected static string $table;

    /** @var class-string<sku_template> Модель, объектами которой должны быть торговые предложения */
    public static string $skuModel;

    /** @var string Ключ, под которым хранятся торговые предложения */
    public static string $skuFieldKey = 'SKU';

    /** @var array Словарь с классами и id инфоблоков (из-за бага с совпадающими static::$iblockId у разных моделей) */
    private static array $iblockIdMap = [];

    /**
     * Поля экземпляров класса
     */

    /** @var int ID элемента */
    protected int $id;

    /** @var array<string,mixed> Поля */
    protected array $fields;

    /** @var array<string,array<string,mixed> Свойства */
    protected array $props;

    /** @var array Поля, используемые для кэширования значений */
    protected array $customFields = [];

    /**
     * @param int $id ID Элемента инфоблока
     * @param array $fields Список полей таблицы b_iblock_element. Допускаются поля типа PROPERTY_*_VALUE, поля типа CATALOG_*
     * @param array|null $props Свойства, полученные через \CIBlockElement::GetList()->getNextElement()->getProperties()
     */
    final public function __construct(int $id, array $fields, ?array $props = null)
    {
        $this->id = $id;
        $this->fields = $fields;
        if (isset($props)) {
            $this->props = $props;
        }
    }

    /**
     * Проверка: существует ли элемент
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->getId() > 0;
    }

    /**
     * ID элемента инфоблока
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Символьный код
     *
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->getField('CODE');
    }

    /**
     * Внешний код
     *
     * @return string|null
     */
    public function getXmlId(): ?string
    {
        return $this->getField('XML_ID');
    }

    /**
     * Сортировка
     *
     * @return int|null
     */
    public function getSort(): ?int
    {
        $sort = $this->getField('SORT');

        return $sort ? (int)$sort : null;
    }

    /**
     * Проверка: активен ли элемент
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->getField('ACTIVE') === 'Y';
    }

    /**
     * Получение названия
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->getField('NAME') ?? '';
    }

    /**
     * Получение ссылки на детальную страницу
     *
     * @return string|null
     */
    public function getLink(): ?string
    {
        return $this->getField('DETAIL_PAGE_URL');
    }

    /**
     * ID основного раздела
     *
     * @return int|null
     */
    public function getSectionId(): ?int
    {
        return (int)$this->getField('IBLOCK_SECTION_ID') ?: null;
    }

    /**
     * ID превью изображения
     *
     * @return int|null
     */
    public function getPreviewPictureId(): ?int
    {
        return (int)$this->getField('PREVIEW_PICTURE') ?: null;
    }

    /**
     * ID детального изображения
     *
     * @return int|null
     */
    public function getDetailPictureId(): ?int
    {
        return (int)$this->getField('DETAIL_PICTURE') ?: null;
    }

    /**
     * Краткое описание
     *
     * @return string|null
     */
    public function getPreviewText(): ?string
    {
        return $this->getField('PREVIEW_TEXT');
    }

    /**
     * Тип краткого описания
     *
     * @return string|null "text" или "html"
     */
    public function getPreviewTextType(): ?string
    {
        return $this->getField('PREVIEW_TEXT_TYPE');
    }

    /**
     * Детальное описание
     *
     * @return string|null
     */
    public function getDetailText(): ?string
    {
        return $this->getField('DETAIL_TEXT');
    }

    /**
     * Тип детального описания
     *
     * @return string|null "text" или "html"
     */
    public function getDetailTextType(): ?string
    {
        return $this->getField('DETAIL_TEXT_TYPE');
    }

    /**
     * Дата создания
     *
     * @return DateTime|null
     */
    public function getDateCreated(): ?DateTime
    {
        $date = $this->getField('DATE_CREATE');

        return $date ? new Datetime($date) : null;
    }

    /**
     * Дата изменения
     *
     * @return DateTime|null
     */
    public function getDateModified(): ?DateTime
    {
        $date = $this->getField('TIMESTAMP_X');

        return $date ? new Datetime($date) : null;
    }

    /**
     * ID пользователя создавшего запись
     *
     * @return int|null
     */
    public function getCreatedById(): ?int
    {
        return (int)$this->getField('CREATED_BY') ?: null;
    }

    /**
     * ID последнего пользователя изменившего запись
     *
     * @return int|null
     */
    public function getModifiedById(): ?int
    {
        return (int)$this->getField('MODIFIED_BY') ?: null;
    }

    /**
     * Дата начала активности
     *
     * @return DateTime|null
     */
    public function getActiveFrom(): ?DateTime
    {
        $date = $this->getField('ACTIVE_FROM');

        return $date ? new Datetime($date) : null;
    }

    /**
     * Дата окончания активности
     *
     * @return DateTime|null
     */
    public function getActiveTo(): ?DateTime
    {
        $date = $this->getField('ACTIVE_TO');

        return $date ? new Datetime($date) : null;
    }

    /**
     * Текст, по которому работает штатный поиск
     *
     * @return string|null
     */
    public function getSearchableContent(): ?string
    {
        return $this->getField('SEARCHABLE_CONTENT');
    }

    /**
     * Получение массива полей
     *
     * @return array
     */
    public function toArray(): array
    {
        $fields = $this->getFields();
        if (isset($this->props)) {
            $fields['PROPERTIES'] = $this->props;
        }

        if ($this->issetCustomField(self::$skuFieldKey)) {
            $sku = $this->getCustomField(self::$skuFieldKey);
            if (current($sku) instanceof self) {
                $fields[self::$skuFieldKey] = array_map(static fn($item) => $item->toArray(), $sku);
            } else {
                $fields[self::$skuFieldKey] = $sku;
            }
        }

        if ($this->customFields) {
            $fields['CUSTOM_FIELDS'] = $this->customFields;
        }

        return $fields;
    }

    /**
     * Получение массива простых полей
     *
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Получение массива свойств
     *
     * @return array
     */
    public function getProps(): array
    {
        return $this->props ?? [];
    }

    /**
     * Проверка: подгружено ли поле
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasField(string $key): bool
    {
        return array_key_exists($key, $this->fields);
    }

    /**
     * Получение значения поля
     *
     * @param string $key Символьный код поля
     *
     * @return mixed
     *
     * @throws \LogicException
     */
    public function getField(string $key)
    {
        if (!array_key_exists($key, $this->fields)) {
            throw new \LogicException("Не подгружено поле \"$key\".");
        }

        return $this->fields[$key];
    }

    /**
     * Получение значения свойства
     * TODO: Переименовать в getPropertyValue
     *
     * Если свойство имеет тип "Список", то будет возвращён объект ListPropertyDto
     * Если свойство имеет тип "Справочник", то будет возвращён объект HighloadPropertyDto
     *
     * @param string $key Символьный код свойства
     *
     * @return null|int|string|ListPropertyDto|HighloadPropertyDto|array<int|string|ListPropertyDto|HighloadPropertyDto>
     */
    public function getProperty(string $key)
    {
        if (!isset($this->props) || !array_key_exists($key, $this->props)) {
            try {
                return $this->getField("PROPERTY_{$key}_VALUE");
            } catch (\LogicException $e) {
                throw new \LogicException("Свойство $key не подгружено. Его можно подгрузить добавив PROPERTY_$key в метод \"select\", либо вызвать метод \"loadProps\"");
            }
        }

        $property = $this->props[$key];
        if (!is_array($property)) {
            throw new \LogicException("Свойство \"$key\" хранится в неправильном формате: " . gettype($property));
        }

        if ($property['PROPERTY_TYPE'] === 'L') {
            if ($property['MULTIPLE'] === 'Y') {
                $values = [];
                foreach ($property['VALUE'] as $i => $val) {
                    $values[] = new ListPropertyDto(
                        $val,
                        $property['VALUE_ENUM'][$i],
                        $property['VALUE_ENUM_ID'][$i],
                        $property['VALUE_XML_ID'][$i],
                    );
                }

                return $values;
            }

            return new ListPropertyDto(
                $property['VALUE'],
                $property['VALUE_ENUM'],
                $property['VALUE_ENUM_ID'],
                $property['VALUE_XML_ID'],
            );
        }

        if ($property['PROPERTY_TYPE'] === 'S' && $property['USER_TYPE'] === 'directory') {
            if ($property['MULTIPLE'] === 'Y') {
                $values = [];
                foreach ($property['VALUE'] as $i => $val) {
                    $values[] = new HighloadPropertyDto(
                        $val,
                        $property['PROPERTY_VALUE_ID'][$i]
                    );
                }

                return $values;
            }

            return new HighloadPropertyDto(
                $property['VALUE'],
                $property['PROPERTY_VALUE_ID']
            );
        }

        $unserializedValue = is_string($property['VALUE'])
            ? unserialize($property['VALUE'], ['allowed_classes' => true])
            : false;

        return $unserializedValue === false ? $property['VALUE'] : $unserializedValue;
    }

    /**
     * Описание значения свойства
     *
     * @param string $key
     *
     * @return null|string|array<string>
     */
    public function getPropertyDescription(string $key)
    {
        if (!isset($this->props) || !array_key_exists($key, $this->props)) {
            try {
                return $this->getField("PROPERTY_{$key}_DESCRIPTION");
            } catch (\LogicException $e) {
                throw new \LogicException("Свойство $key не подгружено. Его можно подгрузить добавив PROPERTY_$key в метод \"select\", либо вызвать метод \"loadProps\"");
            }
        }

        $property = $this->props[$key];
        if (!is_array($property)) {
            throw new \LogicException("Свойство \"$key\" хранится в неправильном формате: " . gettype($property));
        }

        return $property['DESCRIPTION'];
    }

    /**
     * Проверка: подгружены ли свойства
     *
     * @return bool
     */
    public function isPropsLoaded(): bool
    {
        return isset($this->props);
    }

    /**
     * Проверка: было ли установлено кастомное поле
     *
     * @param string $name
     *
     * @return bool
     */
    public function issetCustomField(string $name): bool
    {
        return array_key_exists($name, $this->customFields);
    }

    /**
     * Получение кастомного поля
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getCustomField(string $name)
    {
        if (!$this->issetCustomField($name)) {
            throw new \LogicException("Поле $name не было установлено");
        }

        return $this->customFields[$name];
    }

    /**
     * Установка кастомного поля и его возвращение (для удобного использования в выражении return)
     * (нужно для внутреннего кэширования)
     *
     * @param string $name Название поля
     * @param mixed $value Значение поля
     *
     * @return mixed
     */
    public function setCustomField(string $name, $value)
    {
        return $this->customFields[$name] = $value;
    }

    /**
     * Проверка: подгружены ли торговые предложения
     *
     * @return bool
     */
    public function isSkuLoaded(): bool
    {
        return $this->issetCustomField(self::$skuFieldKey);
    }

    /**
     * Получение торговых предложений
     *
     * @return array<sku_template|array>
     */
    public function getSku(): array
    {
        if (!isset(static::$skuModel)) {
            throw new \LogicException('Необходимо указать модель торговых предложений через $skuModel');
        }

        return $this->getCustomField(self::$skuFieldKey);
    }

    /**
     * Построитель запросов, настроенный под данную модель
     *
     * @return IblockQueryBuilder<static>
     */
    public static function query(): IblockQueryBuilder
    {
        return IblockQueryBuilder::make()
            ->setIblockId(static::getIblockId())
            ->model(static::class);
    }

    /**
     * Создание объекта модели
     *
     * @param array $fields Стандартные поля элемента инфоблока
     * @param array[] $props Свойства элемента инфоблока со структурой \CIBlockElement::GetList()->getNextElement()->getProperties();
     *
     * @return static
     */
    public static function makeInstance(array $fields, array $props = [])
    {
        $id = isset($fields['ID']) ? (int)$fields['ID'] : 0;

        return new static($id, $fields, $props);
    }

    /**
     * Таблица
     *
     * @return class-string<CommonElementTable>
     */
    public static function getTable(): string
    {
        if (isset(static::$table)) {
            return static::$table;
        }

        return static::$table = \Bitrix\Iblock\Iblock::wakeUp(static::$iblockId)->getEntityDataClass();
    }

    /**
     * ID инфоблока
     *
     * @return int
     */
    public static function getIblockId(): int
    {
        return (int)static::getIblock()['ID'];
    }

    /**
     * Символьный код инфоблока
     *
     * @return string
     */
    public static function getIblockCode(): string
    {
        return static::getIblock()['CODE'];
    }

    /**
     * Тип инфоблока
     *
     * @return string
     */
    public static function getIblockType(): string
    {
        return static::getIblock()['IBLOCK_TYPE_ID'];
    }

    /**
     * Инфоблок
     *
     * @return array
     */
    public static function getIblock(): array
    {
        if (isset(static::$iblockIdMap[static::class])) {
            return static::$iblockIdMap[static::class];
        }

        $filter = [];
        if (isset(static::$iblockId)) {
            $filter['=ID'] = static::$iblockId;
        } elseif (isset(static::$iblockCode)) {
            $filter['=CODE'] = static::$iblockCode;
        } elseif (isset(static::$table)) {
            $filter['=ID'] = static::$table::getEntity()->getIblock()->getId();
        } else {
            throw new \LogicException('Необходимо заполнить одно из следующих полей: $iblockId, $iblockCode or $table');
        }

        $iblock = \Bitrix\Iblock\IblockTable::getList([
            'filter' => $filter,
            'cache' => ['ttl' => 86400]
        ])->fetch();

        return static::$iblockIdMap[static::class] = $iblock;
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->fields[] = $value;
        } else {
            $this->fields[$offset] = $value;
        }
    }

    public function offsetExists($offset): bool
    {
        if ($offset === 'PROPERTIES') {
            return isset($this->props);
        }

        return isset($this->fields[$offset]);
    }

    public function offsetUnset($offset): void
    {
        unset($this->fields[$offset]);
    }

    public function offsetGet($offset)
    {
        if ($offset === 'PROPERTIES') {
            return $this->props ?? null;
        }

        return $this->fields[$offset] ?? null;
    }
}
