<?php

namespace Shaman\D7Models;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

/**
 * Построитель запросов к таблицам, у которых есть API D7
 *
 * @template-covariant model_template of BaseModel
 */
class QueryBuilder
{
    /** @var class-string<DataManager>|string Таблица D7, к которой формируется запрос */
    private string $table;

    /** @var string Ключ, используемый в качестве id */
    private string $idKey = 'ID';

    /** @var array Поля для фильтра */
    private array $filterFields = [];

    /** @var array Поля для сортировки */
    private array $sortFields = [];

    /** @var array Поля, которые нужно выбрать */
    private array $selectFields = ['*'];

    /** @var int Время кэширования результата запроса */
    private int $cacheTtl = 0;

    /** @var array Вычисляемые поля */
    private array $runtime = [];

    /** @var int Ограничение выборки */
    private int $itemsPerPage;

    /** @var int Номер страницы с учётом ограничения $this->limit() */
    private int $pageNumber;

    /** @var class-string<BaseModel> Модель, к которой будут приводиться найденные элементы */
    private string $modelClass;

    /** @var string Таблица с пользовательскими полями (UF_*) */
    private string $ufTableName;

    /** @var array<Reference> Join'ы */
    private array $joins;

    /** @var bool Нужно ли делать join'ы */
    private bool $hasJoins = false;

    private Connection $connection;

    /** @var array<string> Колонки таблицы с UF_* полями (для кэширования результата) */
    private array $ufColumnNameList;

    /**
     * @param class-string<DataManager> $table Таблица D7, к которой формируется запрос
     */
    private function __construct(string $table)
    {
        $this->table = $table;
        $this->connection = Application::getConnection();
    }

    /**
     * Экземпляр построителя запросов для более удобного вызова
     * TODO: Удалить
     *
     * @param class-string<DataManager> $table Таблица D7, к которой формируется запрос
     *
     * @return self
     */
    public static function query(string $table): self
    {
        return self::make($table);
    }

    /**
     * Экземпляр построителя запросов для более удобного вызова
     *
     * @param class-string<DataManager> $table Таблица D7, к которой формируется запрос
     *
     * @return self
     */
    public static function make(string $table): self
    {
        return new self($table);
    }

    /**
     * Первый элемент
     *
     * Если указаны join'ы, то этот метод может работать очень долго из-за общего количества записей, потому что
     * появляются дубли записей с разными значениями join'ов и их приходится вручную соединять, а ограничить выборку
     * через LIMIT невозможно, т. к. неизвестно сколько всего записей будет получено для строки с одним ID
     *
     * @return model_template|array|null
     */
    public function first()
    {
        return $this->getIterator()->current();
    }

    /**
     * Получение количества элементов
     *
     * @return int
     */
    public function getCount(): int
    {
        return $this->table::getCount($this->filterFields);
    }

    /**
     * Поиск элементов порционно
     *
     * @param int $chunkSize Размер возвращаемой порции
     *
     * @return \Generator<array<model_template|array>>
     */
    public function getChunk(int $chunkSize): \Generator
    {
        if ($chunkSize < 1) {
            throw new \LogicException('Chunk can\' t be less than 1.');
        }

        if (isset($this->itemsPerPage)) {
            $limit = $this->itemsPerPage;
        }

        $itemsCount = $this->getCount();
        $pagesNumber = ceil($itemsCount / $chunkSize);

        $currentPage = 1;
        $this->page($currentPage);
        $this->limit($chunkSize);

        while ($currentPage <= $pagesNumber) {
            yield $this->get();
            $this->page(++$currentPage);
        }

        $this->page(1);
        if (isset($limit)) {
            $this->limit($limit);
        }
    }

    /**
     * Поиск элементов
     *
     * @return array<model_template|array>
     */
    public function get(): array
    {
        // Для того чтобы в методе getIterator() не присоединялись значения в цикле, вместо этого присоединим 1 раз
        if (isset($this->ufTableName)) {
            $ufTable = $this->ufTableName;
            unset($this->ufTableName);
        }

        // Для того чтобы в методе getIterator() не присоединялись значения в цикле, вместо этого присоединим 1 раз
        $hasJoins = $this->hasJoins;
        $this->hasJoins = false;

        $items = [];
        foreach ($this->getIterator() as $item) {
            if ($hasJoins) {
                $items[] = $item;
            } else {
                $items[$item[$this->idKey]] = $item;
            }
        }

        if ($this->hasJoins = $hasJoins) {
            $items = $this->attachJoins($items);
        }

        if (isset($ufTable)) {
            $this->ufTableName = $ufTable;
            $this->attachUserFields($items);
        }

        return $items;
    }

    /**
     * Поиск элементов
     *
     * @return \Generator<model_template,int,model_template|array,null>
     */
    public function getIterator(): \Generator
    {
        if (!isset($this->table)) {
            throw new \LogicException('You have to specify table for request with "table()" method.');
        }

        $params = [];
        if (isset($this->filterFields)) {
            $params['filter'] = $this->filterFields;
        }

        if (isset($this->sortFields)) {
            $params['order'] = $this->sortFields;
        }

        if (isset($this->selectFields)) {
            if (!in_array('ID', $this->selectFields, true)) {
                $this->selectFields[] = 'ID';
            }

            $params['select'] = $this->selectFields;
        }

        if (isset($this->itemsPerPage)) {
            $params['limit'] = $this->itemsPerPage;
        }

        if (isset($this->pageNumber, $this->itemsPerPage)) {
            $params['offset'] = ($this->pageNumber - 1) * $this->itemsPerPage;
        }

        if (!empty($this->runtime)) {
            $params['runtime'] = $this->runtime;
        }

        if (!empty($this->joins)) {
            $params['runtime'] = array_merge($params['runtime'] ?? [], $this->joins);
            foreach ($this->joins as $join) {
                $params['select'][] = $join->getName() . '.*';
            }
        }

        if ($this->cacheTtl > 0) {
            $params['cache'] = ['ttl' => $this->cacheTtl];
        }

        // Обычная выборка + выборка с UF полями
        if (!$this->hasJoins) {
            $request = $this->table::getList($params);
            while ($item = $request->fetch()) {
                $item = isset($this->modelClass) ? $this->modelClass::makeInstance($item[$this->idKey], $item) : $item;

                // Присоединение UF_* полей, только если этот метод вызван напрямую
                if (isset($this->ufTableName)) {
                    $items = [$item[$this->idKey] => $item];
                    $this->attachUserFields($items);
                    $item = current($items);
                }

                yield $item;
            }

            return;
        }

        // Выборка одиночных элементов с join'ами, только если метод вызван напрямую
        $request = $this->table::getList($params);
        $itemsCount = $request->getSelectedRowsCount();
        $itemsSelected = 0;

        $items = [];
        while ($item = $request->fetch()) {
            $itemsSelected++;
            $isLastItem = $itemsSelected === $itemsCount;
            $item = isset($this->modelClass) ? $this->modelClass::makeInstance($item[$this->idKey], $item) : $item;
            $itemId = $item[$this->idKey];

            // Проверяем, если в обработке уже есть элемент, а новый уже имеет другой ID, заканчиваем итерацию
            if ((isset($currentItemId) && $itemId !== $currentItemId) || $isLastItem) {
                if ($isLastItem) {
                    $items[] = $item;
                }

                $resultItem = current($this->attachJoins($items));

                // Присоединяем UF_* поля
                if (isset($this->ufTableName)) {
                    $itemsForUf = [$resultItem[$this->idKey] => $resultItem];
                    $this->attachUserFields($itemsForUf);
                    $resultItem = current($itemsForUf);
                }

                yield $resultItem;

                $items = [$item];
                $currentItemId = $itemId;
            }

            // Для самой первой итерации
            if (!isset($currentItemId)) {
                $currentItemId = $itemId;
            }

            $items[] = $item;
        }
    }

    /**
     * Установка фильтра
     *
     * @param array $filter
     *
     * @return $this
     */
    public function filter(array $filter): self
    {
        $this->filterFields = $filter;

        return $this;
    }

    /**
     * Установка сортировки
     *
     * @param array $sort
     *
     * @return $this
     */
    public function sort(array $sort): self
    {
        $this->sortFields = $sort;

        return $this;
    }

    /**
     * Установка полей, которые нужно получить
     *
     * @param array $select
     *
     * @return $this
     */
    public function select(array $select): self
    {
        $this->selectFields = $select;

        return $this;
    }

    /**
     * Установка текущей страницы
     *
     * @param int $page
     *
     * @return $this
     */
    public function page(int $page): self
    {
        if ($page < 1) {
            throw new \ValueError('Page can\' be less than 1.');
        }

        $this->pageNumber = $page;

        return $this;
    }

    /**
     * Установка ограничения выборки
     *
     * @param int $limit
     *
     * @return $this
     */
    public function limit(int $limit): self
    {
        if ($limit < 1) {
            throw new \ValueError('Limit can\' be less than 1.');
        }

        $this->itemsPerPage = $limit;

        return $this;
    }

    /**
     * Кэширование запроса
     *
     * @param int $ttl
     *
     * @return $this
     */
    public function cache(int $ttl): self
    {
        $this->cacheTtl = $ttl;

        return $this;
    }

    /**
     * Установка модели, к которой должны приводиться элементы
     *
     * @param string $model
     *
     * @return $this
     */
    public function model(string $model): self
    {
        $this->modelClass = $model;

        return $this;
    }

    /**
     * Ключ, который используется в качестве id
     *
     * @param string $key
     *
     * @return $this
     */
    public function id(string $key): self
    {
        $this->idKey = $key;

        return $this;
    }

    /**
     * Сортировка по возрастанию
     *
     * @param string $key
     *
     * @return $this
     */
    public function sortAsc(string $key): self
    {
        $this->sortFields[$key] = 'ASC';

        return $this;
    }

    /**
     * Сортировка по убыванию
     *
     * @param string $key
     *
     * @return $this
     */
    public function sortDesc(string $key): self
    {
        $this->sortFields[$key] = 'DESC';

        return $this;
    }

    /**
     * Установка случайной сортировки
     * (очень медленная)
     *
     * @return $this
     */
    public function sortRand(): self
    {
        $this->sortFields['RAND'] = 'ASC';
        $this->runtime['RAND'] = [
            'data_type' => 'float',
            'expression' => ['RAND()']
        ];

        return $this;
    }

    /**
     * Фильтрация по полям аналогично EloquentOrm в Laravel
     *
     * @param ...$query
     *
     * @return $this
     */
    public function where(...$query): self
    {
        if (is_array($query[0])) {
            $this->filterFields = array_merge($this->filterFields, $query);

            return $this;
        }

        if (count($query) === 1) {
            $this->filterFields['!=' . $query[0]] = false;

            return $this;
        }

        if (count($query) === 2) {
            $this->filterFields['=' . $query[0]] = $query[1];

            return $this;
        }

        $replacements = [
            '=' => '=',
            '==' => '=',
            '===' => '=',
            '!' => '!=',
            '!=' => '!=',
            '<' => '<',
            '>' => '>',
            '<=' => '<=',
            '>=' => '>='
        ];

        if (count($query) === 3) {
            $operator = $query[1];
            if (empty($replacements[$operator])) {
                throw new \InvalidArgumentException('Second parameter must me one of "' . implode(', ', array_keys($replacements)) . '".');
            }

            $this->filterFields[$replacements[$operator] . $query[0]] = $query[2];

            return $this;
        }

        throw new \InvalidArgumentException('You can pass: 1. Field to filter not empty rows, 2. Array similar to "filter()" method, 3. Key and value, 4. Key, operator (' . implode(', ', $replacements) . '), value ');
    }

    /**
     * Таблица с пользовательскими полями (UF_*)
     *
     * @param string $table
     *
     * @return self<model_template>
     */
    public function ufTable(string $table): self
    {
        $this->ufTableName = $table;

        return $this;
    }

    /**
     * Присоединение UF_* полей к элементам
     *
     * @param array $items
     *
     * @return void
     */
    private function attachUserFields(array &$items): void
    {
        $ufMap = $this->getElementIdUfMap(array_keys($items));
        $ufColumnNameList = $this->getUfColumnNameList();
        $defaultUserFields = array_fill_keys($ufColumnNameList, null);
        foreach ($items as $key => $item) {
            $userFields = empty($ufMap[$key]) ? $defaultUserFields : $ufMap[$key];
            $items[$key] = isset($this->modelClass) ? $item->mergeFields($userFields) : array_merge($item, $userFields);
        }
    }

    /**
     * Словарь из id элемента и его UF_* полей
     *
     * @param array $elementIdList
     *
     * @return array
     */
    private function getElementIdUfMap(array $elementIdList): array
    {
        if (empty($this->ufTableName) || empty($elementIdList)) {
            return [];
        }

        $idString = implode(',', $elementIdList);
        $request = $this->connection->query("SELECT * FROM $this->ufTableName WHERE VALUE_ID IN ($idString)");
        $items = [];
        while ($fields = $request->fetch()) {
            $itemId = $fields['VALUE_ID'];
            unset($fields['VALUE_ID']);

            foreach ($fields as $key => $field) {
                $unserializedValue = is_string($field) ? unserialize($field, ['allowed_classes' => false]) : false;
                if ($unserializedValue !== false) {
                    $fields[$key] = $unserializedValue;
                }
            }

            $items[$itemId] = $fields;
        }

        if (empty($items)) {
            return [];
        }

        // Получение полей типа "Список"
        $keys = array_keys(current($items));
        $userFieldsRes = \Bitrix\Main\UserFieldTable::getList([
            'filter' => [
                '=FIELD_NAME' => $keys,
                '=USER_TYPE_ID' => 'enumeration'
            ],
            'select' => ['FIELD_NAME']
        ]);

        $listFieldCodes = [];

        // Сбор значений типа "Список"
        $listValueIdList = [];
        while ($userFieldData = $userFieldsRes->fetch()) {
            $listFieldCodes[] = $userFieldData['FIELD_NAME'];
            $code = $userFieldData['FIELD_NAME'];

            foreach ($items as $item) {
                $value = $item[$code];
                if (empty($value)) {
                    continue;
                }

                if (is_array($value)) {
                    foreach ($value as $val) {
                        $listValueIdList[] = $val;
                    }
                } else {
                    $listValueIdList[] = $value;
                }
            }
        }

        if (empty($listValueIdList)) {
            return $items;
        }

        // Получение значений типа "Список"
        $valueIdString = implode(',', $listValueIdList);
        $request = $this->connection->query("SELECT * FROM b_user_field_enum WHERE ID IN ($valueIdString)");
        $enumValues = [];
        while ($value = $request->fetch()) {
            $enumValues[$value['ID']] = $value;
        }

        // Подстановка найденных значений
        foreach ($items as &$item) {
            foreach ($listFieldCodes as $code) {
                if (empty($item[$code])) {
                    continue;
                }

                $values = [];
                if (is_array($item[$code])) {
                    foreach ($item[$code] as $valueId) {
                        $values[] = [
                            'ID' => $valueId,
                            'VALUE' => $enumValues[$valueId]['VALUE'],
                            'XML_ID' => $enumValues[$valueId]['XML_ID']
                        ];
                    }
                } else {
                    $valueId = $item[$code];
                    $values = [
                        'ID' => $valueId,
                        'VALUE' => $enumValues[$valueId]['VALUE'],
                        'XML_ID' => $enumValues[$valueId]['XML_ID']
                    ];
                }

                $item[$code] = $values;
            }
        }

        return $items;
    }

    /**
     * Таблица для INNER JOIN
     *
     * @param class-string<DataManager>|string $tableName Таблица D7, записи которой нужно присоединить
     * @param string $fieldName Название поля, в котором будут храниться join-ы
     * @param string $localKey Ключ из первой таблицы
     * @param string $foreignKey Ключ из второй таблицы
     *
     * @return $this
     */
    public function join(string $tableName, string $fieldName, string $localKey, string $foreignKey): self
    {
        $this->hasJoins = true;
        $this->joins[] = new Reference(
            $fieldName,
            $tableName,
            Join::on('this.' . $localKey, 'ref.' . $foreignKey),
            ['join_type' => 'inner']
        );

        return $this;
    }

    /**
     * Таблица для LEFT JOIN
     *
     * @param class-string<DataManager>|string $tableName Таблица D7, записи которой нужно присоединить
     * @param string $fieldName Название поля, в котором будут храниться join-ы
     * @param string $localKey Ключ из первой таблицы
     * @param string $foreignKey Ключ из второй таблицы
     *
     * @return $this
     */
    public function leftJoin(string $tableName, string $fieldName, string $localKey, string $foreignKey): self
    {
        $this->hasJoins = true;
        $this->joins[] = new Reference(
            $fieldName,
            $tableName,
            Join::on('this.' . $localKey, 'ref.' . $foreignKey),
            ['join_type' => 'left']
        );

        return $this;
    }

    /**
     * Группировка и присоединение полей со связями 1:1 и 1:М
     *
     * @param array $joinItems Все элементы выборки c join'ом
     *
     * @return array
     */
    private function attachJoins(array $joinItems): array
    {
        $items = [];
        foreach ($joinItems as $joinItem) {
            $items[$joinItem['ID']] = $joinItem;
        }

        if (isset($this->modelClass)) {
            $items = array_map(static fn($item) => $item->toArray(), $items);
            $joinItems = array_map(static fn($item) => $item->toArray(), $joinItems);
        }

        $itemProps = [];
        // Чистка полученных полей и присоединение связей 1:1
        foreach ($this->joins as $join) {
            $columnName = $join->getName();
            $joinType = $join->getJoinType();
            foreach ($joinItems as $item) {
                foreach ($item as $key => $value) {
                    /*
                     * TODO: Придумать как скипать дефолтные ключи из оригинальной таблицы, чтобы можно было спокойно
                     * использовать названия типа IBLOCK (сейчас нельзя т.к. попадёт, например IBLOCK_ID или IBLOCK_SECTION_ID)
                     */
                    if (strpos($key, $columnName) === false) {
                        continue;
                    }

                    unset($items[$item['ID']][$key]);
                    $pureKey = preg_replace('/.*' . $columnName . '_?/', '', $key);
                    if ($joinType === 'INNER') {
                        $items[$item['ID']][$columnName][$pureKey] = $value;
                    } else {
                        $itemProps[$item['ID']][$columnName][$pureKey][] = $value;
                    }
                }
            }
        }

        // Присоединение связей 1:M
        foreach ($itemProps as $itemId => $joinValues) {
            foreach ($joinValues as $columnName => $columnValues) {
                foreach (current($columnValues) as $index => $value) {
                    // Использование id в качестве ключей элементов join'a, чтобы исключить дубликаты при связи 1:M или M:M
                    if (isset($columnValues['ID'])) {
                        $joinItemId = $columnValues['ID'][$index];

                        // Костыль для случая, когда по одному из join'ов ничего нет (без этого туда дублируются пустые поля)
                        if (empty($joinItemId)) {
                            $items[$itemId][$columnName] = [];
                            break;
                        }
                    }

                    foreach ($columnValues as $fieldKey => $fieldValues) {
                        if (isset($joinItemId)) {
                            $items[$itemId][$columnName][$joinItemId][$fieldKey] = $fieldValues[$index];
                        } else {
                            $items[$itemId][$columnName][$index][$fieldKey] = $fieldValues[$index];
                        }
                    }
                }
            }
        }

        return isset($this->modelClass) ? $this->modelClass::makeInstanceList($items) : $items;
    }

    /**
     * Список всех UF_* полей
     *
     * @return array<string>
     */
    private function getUfColumnNameList(): array
    {
        if (isset($this->ufColumnNameList)) {
            return $this->ufColumnNameList;
        }

        $request = $this->connection->query("DESCRIBE $this->ufTableName");
        $rows = [];
        while ($row = $request->fetch()) {
            $fieldName = $row['Field'];
            if (strpos($fieldName, 'UF_') === 0) {
                $rows[] = $fieldName;
            }
        }

        return $this->ufColumnNameList = $rows;
    }
}
