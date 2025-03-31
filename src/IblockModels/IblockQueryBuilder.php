<?php

namespace Shaman\IblockModels;

use Shaman\Repositories\IblockElementRepository;
use Shaman\Repositories\IblockRepository;
use Shaman\Repositories\PropertyRepository;

/**
 * Построитель запросов к элементам инфоблоков
 *
 * @template-covariant model_template of BaseIblockModel
 */
class IblockQueryBuilder
{
    private IblockRepository $iblockRepository;
    private IblockElementRepository $iblockElementRepository;
    private PropertyRepository $propertyRepository;

    /** @var int ID инфоблока, из которого делается выборка */
    private int $iblockId;

    /** @var array Поля для фильтра */
    private array $filterFields = [];

    /** @var array Поля для сортировки */
    private array $sortFields = [];

    /** @var array Поля, которые нужно выбрать */
    private array $selectFields = ['*'];

    /** @var int Ограничение выборки */
    private int $itemsPerPage;

    /** @var int Номер страницы с учётом ограничения $this->limit() */
    private int $pageNumber;

    /** @var class-string<model_template> Модель, к которой будут приводиться найденные элементы */
    private string $modelClass;

    /** @var bool Нужно ли доставать свойства (сильно замедляет выборку) */
    private bool $selectProperties = false;

    /** @var array Свойства, которые нужно выбрать */
    private array $propertiesToSelectCodeList = [];

    /** @var string[] Поля свойств, которые нужно выбирать */
    private array $propertyFieldsToSelect = [];

    /** @var bool Нужно ли применять функцию htmlspecialcharsEx к значениям свойств */
    private bool $useHtmlSpecialCharsExForValues = false;

    /** @var string Используемый метод для получения данных из CIBlockElement::GetList() */
    private string $fetchMethod = 'fetch';

    /* Торговые предложения */

    /** @var bool Нужно ли выбирать торговые предложения */
    private bool $selectOffers = false;

    /** @var int ID инфоблока с торговыми предложениями */
    private int $skuIblockId;

    /** @var array Фильтр для торговых предложений */
    private array $skuFilter = [];

    /** @var bool Нужно ли выбирать свойства торговых предложений */
    private bool $selectSkuProperties = false;

    /** @var array Свойства торговых предложений, которые нужно выбрать */
    private array $skuPropertiesToSelectCodeList = [];

    /** @var array Поля свойств торговых предложений, которые нужно выбрать */
    private array $skuPropertyFieldsToSelect = [];

    /** @var array Поля торговых предложений, которые нужно выбрать */
    private array $skuSelectFields = ['*'];

    /** @var class-string<BaseIblockModel> Модель, к которой нужно мапить торговые предложения */
    private string $skuModelClass;

    /** @var array Заглушка с информацией о свойствах для товаров */
    private array $emptyPropertiesArray;

    /**
     * Экземпляр построителя запросов для более удобного вызова
     * TODO: Удалить
     *
     * @return self<model_template>
     */
    public static function query(): self
    {
        return self::make();
    }

    /**
     * Экземпляр построителя запросов для более удобного вызова
     *
     * @return self<model_template>
     */
    public static function make(): self
    {
        return new self(
            IblockRepository::make(),
            IblockElementRepository::make(),
            PropertyRepository::make()
        );
    }

    public function __construct(
        IblockRepository $iblockRepository,
        IblockElementRepository $iblockElementRepository,
        PropertyRepository $propertyRepository
    ) {
        $this->iblockRepository = $iblockRepository;
        $this->iblockElementRepository = $iblockElementRepository;
        $this->propertyRepository = $propertyRepository;
    }

    /**
     * Первый элемент
     *
     * @return model_template|array|null
     */
    public function first()
    {
        if (isset($this->itemsPerPage)) {
            $limit = $this->itemsPerPage;
        }

        $item = $this->limit(1)->getIterator()->current();
        if (isset($limit)) {
            $this->limit($limit);
        }

        return $item;
    }

    /**
     * Получение количества элементов
     *
     * @return int
     */
    public function getCount(): int
    {
        return $this->performRequest()->selectedRowsCount();
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
            throw new \LogicException('Значение не может быть меньше 1.');
        }

        $items = [];
        $iterationsCount = 0;
        foreach ($this->getIterator($chunkSize) as $item) {
            $items[$item['ID']] = $item;
            $iterationsCount++;
            if ($iterationsCount === $chunkSize) {
                yield $items;
                $items = [];
                $iterationsCount = 0;
            }
        }

        if ($items) {
            yield $items;
            unset($items);
        }
    }

    /**
     * Поиск элементов
     *
     * @return array<int,model_template|array>
     */
    public function get(int $inMemoryPortion = 3000): array
    {
        $items = [];
        foreach ($this->getIterator($inMemoryPortion) as $item) {
            $items[$item['ID']] = $item;
        }

        return $items;
    }

    /**
     * Возвращает массив ID элементов
     *
     * @return array<int>
     */
    public function getIdList(): array
    {
        $this->select(['ID']);

        $ids = [];
        foreach ($this->getIterator() as $item) {
            $ids[] = (int)$item['ID'];
        }

        return $ids;
    }

    /**
     * Получение элементов из результата запроса \CIBlockResult
     *
     * @param int $inMemoryPortion Одновременно хранимая в памяти порция (Если поставить 0, то в память попадёт всё за раз)
     *
     * @return \Generator<model_template,int,model_template|array,null>
     */
    public function getIterator(int $inMemoryPortion = 3000): \Generator
    {
        $request = $this->performRequest();

        // 1. Не надо выбирать ни свойства, ни торговые предложения
        if (!$this->selectProperties && !$this->selectOffers) {
            if (isset($this->modelClass)) {
                while ($row = $request->{$this->fetchMethod}()) {
                    yield $this->modelClass::makeInstance($row);
                }
            } else {
                while ($row = $request->{$this->fetchMethod}()) {
                    yield $row;
                }
            }

            return;
        }

        // 2. Не надо выбирать свойства, но нужны торговые предложения
        if (!$this->selectProperties && $this->selectOffers) {
            $items = [];
            $i = 0;
            while ($row = $request->{$this->fetchMethod}()) {
                $items[(int)$row['ID']] = isset($this->modelClass) ? $this->modelClass::makeInstance($row) : $row;
                $i++;

                // Делаем запросы на торговые предложения порционно, чтобы не плодить запросы в цикле
                if ($i === $inMemoryPortion) {
                    $i = 0;
                    $items = $this->attachSku($items);
                    foreach ($items as $key => $item) {
                        yield $item;
                        unset($items[$key]);
                    }
                }
            }

            // Присоединяем торговые предложения к оставшимся элементам
            if ($items) {
                $items = $this->attachSku($items);
                foreach ($items as $key => $item) {
                    yield $item;
                    unset($items[$key]);
                }
                return;
            }

            return;
        }

        // 3. Надо выбрать свойства, но без торговых предложений
        $propertyFilter = $this->propertiesToSelectCodeList ? ['CODE' => $this->propertiesToSelectCodeList] : [];
        $propertySelect = $this->propertyFieldsToSelect
            ? array_unique(array_merge(['ID', 'PROPERTY_TYPE', 'USER_TYPE', 'MULTIPLE'], $this->propertyFieldsToSelect))
            : [];

        // Для запроса одного элемента используем CIBlockElement::GetList()->GetNextElement(), т. к. в данном случае он работает быстрее
        if (isset($this->itemsPerPage) && $this->itemsPerPage === 1) {
            $item = $request->GetNextElement(false, false);
            if (empty($item)) {
                return;
            }

            $fields = $item->GetFields();
            $props = $item->GetProperties([], $propertyFilter);
            $skuList = $this->selectOffers ? $this->getOfferList($fields['ID']) : [];

            if (isset($this->modelClass)) {
                $instance = $this->modelClass::makeInstance($fields, $props);
                $instance->setCustomField($this->modelClass::$skuFieldKey, $skuList);
                yield $instance;
            } else {
                $fields['PROPERTIES'] = $props;
                if ($this->selectOffers) {
                    $fields['SKU'] = $skuList;
                }

                yield $fields;
            }

            return;
        }

        // Для запроса более чем одного элемента используем \CIBlockElement::GetPropertyValuesArray
        $rows = [];
        $i = 0;
        while ($row = $request->{$this->fetchMethod}()) {
            $row['PROPERTIES'] = [];
            $elementId = (int)$row['ID'];
            $rows[$elementId] = &$row;
            unset($row);
            $i++;

            if ($i === $inMemoryPortion) {
                $i = 0;
                foreach ($this->fetchWithPropsAndSku($rows, $propertyFilter, $propertySelect) as $element) {
                    yield $element;
                }
            }
        }

        if ($rows) {
            foreach ($this->fetchWithPropsAndSku($rows, $propertyFilter, $propertySelect) as $element) {
                yield $element;
            }
        }
    }

    /**
     * Присоединение свойств и торговых предложений
     *
     * @param array[] $elementList
     * @param array $propertyFilter
     * @param array $propertySelect
     *
     * @return \Generator
     */
    private function fetchWithPropsAndSku(array &$elementList, array $propertyFilter, array $propertySelect): \Generator
    {
        // Получаем все свойства порции
        $this->iblockElementRepository->attachProperties(
            $this->filterFields['IBLOCK_ID'],
            $elementList,
            $propertyFilter,
            $propertySelect,
            $this->useHtmlSpecialCharsExForValues
        );

        // Если у свойств элемента нет значений, то массив PROPERTIES останется пустым (нужно это исправить для сохранения совместимости)
        foreach ($elementList as &$element) {
            if (empty($element['PROPERTIES'])) {
                $element['PROPERTIES'] = $this->getEmptyPropertiesArray($this->filterFields['IBLOCK_ID']);
            }
        }
        unset($element);

        // Маппим к модели, если нужно
        if (isset($this->modelClass)) {
            $modelClass = $this->modelClass;
            array_walk($elementList, static function (&$item) use ($modelClass) {
                $props = $item['PROPERTIES'];
                unset($item['PROPERTIES']);
                $item = $modelClass::makeInstance($item, $props);
            });
        }

        // Присоединяем торговые предложения, если нужно
        if ($this->selectOffers) {
            $elementList = $this->attachSku($elementList);
        }

        // Отдаём по-одному
        foreach ($elementList as $key => $element) {
            yield $element;
            unset($elementList[$key]);
        }
    }

    /**
     * Формирование запроса
     *
     * @return \CIBlockResult
     */
    private function performRequest(): \CIBlockResult
    {
        $this->checkParams();

        if (isset($this->iblockId)) {
            // Здесь нельзя ставить =IBLOCK_ID, потому что не будет работать фильтрация по свойствам через PROPERTY_*
            $this->filterFields['IBLOCK_ID'] = $this->iblockId;
        }

        if (isset($this->modelClass) && empty($this->filterFields['IBLOCK_ID'])) {
            // Здесь нельзя ставить =IBLOCK_ID, потому что не будет работать фильтрация по свойствам через PROPERTY_*
            $this->filterFields['IBLOCK_ID'] = $this->modelClass::getIblockId();
        }

        // Выборка обязательных полей
        if (!in_array('*', $this->selectFields, true)) {
            foreach (['ID', 'IBLOCK_ID'] as $field) {
                if (!in_array($field, $this->selectFields, true)) {
                    $this->selectFields[] = $field;
                }
            }
        }

        return $this->iblockElementRepository->performRequest(
            $this->sortFields,
            $this->filterFields,
            $this->selectFields,
            $this->itemsPerPage ?? 0,
            $this->pageNumber ?? 0,
        );
    }

    /**
     * TODO: Implement
     *
     * @return $this
     */
    public function where(): self
    {
        return $this;
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
        $this->filterFields = array_merge($this->filterFields, $filter);

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
        $this->sortFields = array_merge($this->sortFields, $sort);

        return $this;
    }

    /**
     * Сортировка по возрастанию
     *
     * @param string $field
     *
     * @return $this
     */
    public function sortAsc(string $field): self
    {
        return $this->sort([$field => 'ASC']);
    }

    /**
     * Сортировка по убыванию
     *
     * @param string $field
     *
     * @return $this
     */
    public function sortDesc(string $field): self
    {
        return $this->sort([$field => 'DESC']);
    }

    /**
     * Случайная сортировка
     *
     * @return $this
     */
    public function sortRand(): self
    {
        return $this->sort(['RAND' => 'ASC']);
    }

    /**
     * Выбрать только активные элементы
     *
     * @return $this
     */
    public function scopeActive(): self
    {
        $this->filterFields['=ACTIVE'] = 'Y';

        return $this;
    }

    /**
     * Выбрать только доступные элементы
     *
     * @return $this
     */
    public function scopeAvailable(): self
    {
        $this->filterFields['=CATALOG_AVAILABLE'] = 'Y';

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
            throw new \ValueError('Номер страницы не может быть меньше 1.');
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
            throw new \ValueError('Лимит не может быть меньше 1.');
        }

        $this->itemsPerPage = $limit;

        return $this;
    }

    /**
     * TODO: Implement
     *
     * @param int $offset
     *
     * @return $this
     */
    public function offset(int $offset): self
    {
        return $this;
    }

    /**
     * Выбрать свойства
     *
     * @param array $propertyCodeList Свойства, которые нужно получить (работает только с get/getIterator)
     * @param array $fieldsToSelect Поля свойств, которые нужно получить (работает только с get/getIterator)
     * @param bool $htmlspecialcharsForValues Применять ли функцию htmlspecialcharsEx к значениям свойств (применяется
     *     по умолчанию в GetNext() и в GetNextElement()->GetProperties())
     *
     * @return $this
     */
    public function loadProps(array $propertyCodeList = [], array $fieldsToSelect = ['ID'], bool $htmlspecialcharsForValues = false): self
    {
        $this->selectProperties = true;
        $this->propertiesToSelectCodeList = $propertyCodeList;
        $this->propertyFieldsToSelect = $fieldsToSelect;
        $this->useHtmlSpecialCharsExForValues = $htmlspecialcharsForValues;

        return $this;
    }

    /**
     * Установка модели, к которой должны приводиться элементы
     *
     * @param class-string $model
     *
     * @return $this
     */
    public function model(string $model): self
    {
        $this->modelClass = $model;

        return $this;
    }

    /**
     * Установка id инфоблока для фильтрации
     *
     * @param int $iblockId
     *
     * @return $this
     */
    public function setIblockId(int $iblockId): self
    {
        $this->iblockId = $iblockId;

        return $this;
    }

    /**
     * Установка символьного кода инфоблока для фильтрации
     *
     * @param string $code
     *
     * @return $this
     */
    public function setIblockCode(string $code): self
    {
        $id = $this->iblockRepository->getIdByCode($code);
        if ($id === null) {
            throw new \RuntimeException("Не найден инфоблок с кодом \"$code\"");
        }

        $this->iblockId = $id;

        return $this;
    }

    /**
     * Генерация ссылок для элементов
     * TODO: Вместо использования GetNext генерировать ссылку самостоятельно
     *
     * Использует метод CIBlockElement::GetNext() для генерации ссылок
     * <h3>Важно! Замедляет скорость выборки в ~5 раз</h1>
     *
     * @return $this
     */
    public function generateLinks(): self
    {
        $this->fetchMethod = 'getNext';

        return $this;
    }

    /**
     * Добавление торговых предложений к элементам
     *
     * @param int $iblockId
     *
     * @return self
     */
    public function skuSetIblockId(int $iblockId): self
    {
        $this->selectOffers = true;
        $this->skuIblockId = $iblockId;

        return $this;
    }

    /**
     * Установка модели, к которой нужно мапить торговые предложения
     *
     * @param model_template|string $model
     *
     * @return $this
     */
    public function skuModel(string $model): self
    {
        $this->selectOffers = true;
        $this->skuModelClass = $model;
        $this->skuIblockId = $model::getIblockId();

        return $this;
    }

    /**
     * Установка флага, означающего, что нужно доставать свойства торговых предложений
     *
     * @param array $propertyCodeList
     *
     * @return $this
     */
    public function skuLoadProps(array $propertyCodeList = [], array $fieldsToSelect = []): self
    {
        $this->selectOffers = true;
        $this->selectSkuProperties = true;
        $this->skuPropertiesToSelectCodeList = $propertyCodeList;
        $this->skuPropertyFieldsToSelect = $fieldsToSelect;

        return $this;
    }

    /**
     * Фильтр для торговых предложений
     *
     * @param array $filter
     *
     * @return $this|self
     */
    public function skuFilter(array $filter): self
    {
        $this->selectOffers = true;
        $this->skuFilter = $filter;

        return $this;
    }

    /**
     * Установка полей торговых предложений, которые нужно выбрать
     *
     * @param array<string> $select
     *
     * @return $this
     */
    public function skuSelectFields(array $select): self
    {
        $this->selectOffers = true;
        $this->skuSelectFields = $select;

        return $this;
    }

    /**
     * Установленная сортировка
     *
     * @return array
     */
    public function getSort(): array
    {
        return $this->sortFields;
    }

    /**
     * Установленный фильтр
     *
     * @return array
     */
    public function getFilter(): array
    {
        return $this->filterFields;
    }

    /**
     * Установленный фильтр для торговых предложений
     *
     * @return array
     */
    public function getSkuFilter(): array
    {
        return $this->skuFilter;
    }

    /**
     * Присоединение торговых предложений к элементам
     * (ключами обязательно должны быть ID родительских элементов)
     *
     * @param array<int,model_template|array> $itemList
     *
     * @return array
     */
    private function attachSku(array &$itemList): array
    {
        $itemsIdList = array_keys($itemList);
        $productIdOffersMap = $this->getProductIdOfferMap($itemsIdList);
        foreach ($itemList as &$item) {
            if (isset($this->modelClass)) {
                $item->setCustomField('SKU', $productIdOffersMap[$item['ID']] ?? []);
            } else {
                $item['SKU'] = $productIdOffersMap[$item['ID']] ?? [];
            }
        }

        return $itemList;
    }

    /**
     * Список торговых предложений
     *
     * @param int $elementId
     *
     * @return array
     */
    private function getOfferList(int $elementId): array
    {
        return $this->getProductIdOfferMap([$elementId])[$elementId] ?: [];
    }

    /**
     * Словарь с id элементов и их торговыми предложениями
     *
     * @param array $elementIdList
     *
     * @return array<int,array>
     */
    private function getProductIdOfferMap(array $elementIdList): array
    {
        if (!isset($this->skuIblockId) && !isset($this->skuModelClass)) {
            throw new \LogicException('Для выборки торговых предложений необходимо использовать skuSetIblockId() или skuModel()');
        }

        $this->skuFilter['=PROPERTY_CML2_LINK'] = $elementIdList;
        $query = self::make()->setIblockId($this->skuIblockId)->filter($this->skuFilter)->select($this->skuSelectFields);
        if (isset($this->skuModelClass)) {
            $query->model($this->skuModelClass);
        }

        if ($this->selectSkuProperties) {
            if (!empty($this->skuPropertiesToSelectCodeList)) {
                $this->skuPropertiesToSelectCodeList[] = 'CML2_LINK';
            }
        } else {
            $this->skuPropertiesToSelectCodeList = ['CML2_LINK'];
        }

        $query->loadProps($this->skuPropertiesToSelectCodeList, $this->skuPropertyFieldsToSelect);

        $items = [];
        foreach ($query->getIterator() as $offer) {
            $productId = isset($this->skuModelClass) ? $offer->getProperty('CML2_LINK') : $offer['PROPERTIES']['CML2_LINK']['VALUE'];
            $items[$productId][$offer['ID']] = $offer;
        }

        return $items;
    }

    /**
     * Заглушка с информацией о свойствах для товаров, у которых массив свойств оказался пустым из-за отсутствия значений
     *
     * @param int $iblockId
     *
     * @return array
     */
    private function getEmptyPropertiesArray(int $iblockId): array
    {
        if (isset($this->emptyPropertiesArray)) {
            return $this->emptyPropertiesArray;
        }

        $propertyList = $this->propertyRepository->getAll($iblockId);
        $props = [];
        foreach ($propertyList as $prop) {
            $prop['USER_TYPE_SETTINGS'] = [];
            $value = $prop['MULTIPLE'] === 'Y' ? [] : null;

            // Для соответствия значениям \CIBlockElement::GetList()->GetNextElement()->GetProperties()
            if ($prop['USER_TYPE'] === 'video') {
                $value = [];
            }

            $prop['PROPERTY_VALUE_ID'] = null;
            $prop['VALUE'] = $value;
            $prop['VALUE_ENUM'] = $value;
            $prop['VALUE_ENUM_ID'] = $value;
            $prop['VALUE_XML_ID'] = $value;
            $prop['VALUE_SORT'] = $value;
            $prop['DESCRIPTION'] = $value;

            $props[$prop['CODE']] = $prop;
        }

        return $this->emptyPropertiesArray = $props;
    }

    /**
     * Проверка валидности параметров
     *
     * @return void
     */
    private function checkParams(): void
    {
        if (empty($this->iblockId) && empty($this->modelClass)) {
            throw new \LogicException('Необходимо указать источник данных через setIblockId(), setIblockCode() или model()');
        }

        if ($this->selectSkuProperties && !isset($this->skuIblockId) && (!($this->modelClass instanceof BaseIblockModel) && empty($this->modelClass::$skuModel))) {
            throw new \LogicException('Для выборки торговых предложений необходимо использовать skuSetIblockId() или skuModel()');
        }
    }
}