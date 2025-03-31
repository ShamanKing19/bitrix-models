<?php

namespace Shaman\D7Models;

use ArrayAccess;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ORM\Data\DataManager;

/**
 * Модель для таблицы, потомка DataManager
 * <ol>
 *     <li>Для работы нужно унаследоваться и объявить: <p><b>public static string $table = SomeTable::class</b></p></li>
 *     <li>Можно присоединить таблицу с пользовательскими полями UF_* методом "static::queryWithUf()" (для некоторых сущностей реализовано по умолчанию, например, \Bitrix\Main\UserTable).
 *         Для этого нужно переопределить метод <p><b>static::getUfTableName()</b></p>
 *     </li>
 * </ol>
 */
abstract class BaseModel implements ArrayAccess
{
    /**
     * Обязательные к заполнению поля
     */

    /** @var class-string<DataManager> Название класса (SomeTable::class), потомка DataManager */
    public static string $table;

    /** @var string Название highload-таблицы */
    public static string $highloadTableName;

    /** @var class-string<DataManager> */
    private static string $highloadTable;

    /**
     * Настраиваемые поля
     */

    /** @var int|string Поле, являющееся идентификатором */
    public static string $idKey = 'ID';

    /**
     * Свойства
     */

    /* @var int|string $id Элемента таблицы */
    protected $id;

    /* @var array<string,mixed> Список полей элемента */
    protected array $fields;

    /* @var array<string,mixed> Поля, которые нужно обновить методом save() */
    protected array $fieldsToUpdate;

    /* @var array Кастомные поля, использующиеся для подготовки данных для последующего избегания n+1 запросов */
    protected array $customFields = [];

    public function __construct($id, array $fields)
    {
        $this->id = $id;
        $this->fields = $fields;
    }

    /**
     * Получение id элемента
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Получение полей элемента
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->fields;
    }

    /**
     * Получение значения поля
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getField(string $key)
    {
        if (!array_key_exists($key, $this->fields)) {
            throw new \LogicException("Поле \"$key\" не подгружено");
        }

        return $this->fields[$key];
    }

    /**
     * Установка нового значения поля
     *
     * @param string $key
     * @param bool|int|string|null $value
     *
     * @return static
     */
    public function setField(string $key, $value)
    {
        $this->fields[$key] = $value;
        $this->fieldsToUpdate[$key] = $value;

        return $this;
    }

    /**
     * Проверка: подгружено ли поле
     *
     * @param string $key
     *
     * @return bool
     */
    public function issetField(string $key): bool
    {
        return array_key_exists($key, $this->fields);
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
     * Добавление полей к уже существующим
     *
     * @param array $fields
     *
     * @return static
     */
    public function mergeFields(array $fields)
    {
        $this->fields = array_merge($this->fields, $fields);

        return $this;
    }

    /**
     * Проверка: подгружены ли UF_* поля
     *
     * @return bool
     */
    public function isUfLoaded(): bool
    {
        foreach (array_reverse($this->fields) as $key => $value) {
            if (strpos($key, 'UF_') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Обновление записи в базе с текущими полями
     *
     * @return static
     *
     * @throws \RuntimeException Не удалось обновить поля
     */
    public function save()
    {
        if (empty($this->fieldsToUpdate)) {
            return $this;
        }

        $result = static::getTable()::update($this->getId(), $this->fieldsToUpdate);
        if ($result->isSuccess()) {
            return $this;
        }

        throw new \RuntimeException(implode('. ', $result->getErrorMessages()));
    }

    /**
     * Удаление записи из базы
     *
     * @return bool
     */
    public function delete(): bool
    {
        return static::deleteById($this->getId());
    }

    /**
     * Получение объекта по id
     *
     * @param int|string $id id элемента
     *
     * @return static|null
     */
    public static function find($id)
    {
        return static::query()->where([static::$idKey => $id])->first();
    }

    /**
     * Получение объекта по значению определённого поля из таблицы
     *
     * @param string $key название поля таблицы или массив с несколькими полями и значениями
     * @param int|string|bool $value значение
     *
     * @return static|null
     */
    public static function findBy(string $key, $value)
    {
        return static::query()->where([$key => $value])->first();
    }

    /**
     * Запрос с настройками для данной модели и UF_* полями
     *
     * @return QueryBuilder<static>
     */
    public static function queryWithUf(): QueryBuilder
    {
        return static::query()->ufTable(static::getUfTableName());
    }

    /**
     * Запрос с настройками для данной модели
     *
     * @return QueryBuilder<static>
     */
    public static function query(): QueryBuilder
    {
        return QueryBuilder::query(static::getTable())->model(static::class);
    }

    /**
     * Создание экземпляра класса
     *
     * @param $id
     * @param array $fields
     *
     * @return static
     */
    public static function makeInstance($id, array $fields)
    {
        return new static($id, $fields);
    }

    /**
     * Формирует массив сущностей из простого массива с полями
     *
     * @param array $items Массив элементов
     *
     * @return array<static>
     */
    public static function makeInstanceList(array $items): array
    {
        $instanceList = [];
        foreach ($items as $item) {
            $itemId = $item[static::$idKey];
            $instanceList[$itemId] = static::makeInstance($itemId, $item);
        }

        return $instanceList;
    }

    /**
     * Создание элемента
     *
     * @param array $fields поля, которые нужно сохранить
     *
     * @return static|null
     *
     * @throws \RuntimeException Не удалось создать запись
     */
    public static function create(array $fields)
    {
        $result = static::getTable()::add($fields);
        if ($result->isSuccess() && ($id = $result->getId())) {
            return new static($id, $fields);
        }

        throw new \RuntimeException(implode('. ', $result->getErrorMessages()));
    }

    /**
     * Удаление элемента
     *
     * @param int $id id элемента
     *
     * @throws \RuntimeException Не удалось удалить запись
     */
    public static function deleteById(int $id): bool
    {
        $result = static::getTable()::delete($id);
        if ($result->isSuccess()) {
            return true;
        }

        throw new \RuntimeException(implode('. ', $result->getErrorMessages()));
    }

    /**
     * Получение названия таблицы, в которой лежат значения пользовательских полей UF_*
     *
     * @return string
     */
    protected static function getUfTableName(): string
    {
        throw new NotImplementedException('Необходимо переопределить этот метод');
    }

    /**
     * D7 таблица для выполнения запросов
     *
     * @return class-string<DataManager>
     */
    public static function getTable(): string
    {
        if (isset(static::$table)) {
            return static::$table;
        }

        if (isset(static::$highloadTable)) {
            return static::$highloadTable;
        }

        if (isset(static::$highloadTableName)) {
            $table = HighloadBlockTable::getList([
                'filter' => ['TABLE_NAME' => static::$highloadTableName],
                'select' => ['ID'],
                'cache' => ['ttl' => 86400]
            ])->fetch();

            if (empty($table)) {
                throw new \LogicException('Таблица "' . static::$highloadTableName . '" не найдена');
            }

            return static::$highloadTable = HighloadBlockTable::compileEntity($table['ID'])->getDataClass();
        }

        throw new \LogicException('Не указана таблица');
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
        return isset($this->fields[$offset]);
    }

    public function offsetUnset($offset): void
    {
        unset($this->fields[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->fields[$offset] ?? null;
    }
}
