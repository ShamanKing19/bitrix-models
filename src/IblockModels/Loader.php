<?php

namespace Shaman\IblockModels;

use Shaman\Contracts\Makeable;
use Shaman\IblockModels\Dto\HighloadPropertyDto;
use Shaman\Repositories\FileRepository;
use Shaman\Repositories\PriceRepository;
use Shaman\Repositories\PropertyRepository;
use Shaman\Repositories\SectionRepository;

class Loader implements Makeable
{
    protected SectionRepository $sectionRepository;
    protected PropertyRepository $propertyRepository;
    protected FileRepository $fileRepository;
    private PriceRepository $priceRepository;

    public static function make(): self
    {
        return new self(
            SectionRepository::make(),
            PropertyRepository::make(),
            FileRepository::make(),
            PriceRepository::make()
        );
    }

    public function __construct(
        SectionRepository $sectionRepository,
        PropertyRepository $propertyRepository,
        FileRepository $fileRepository,
        PriceRepository $priceRepository
    ) {
        $this->sectionRepository = $sectionRepository;
        $this->propertyRepository = $propertyRepository;
        $this->fileRepository = $fileRepository;
        $this->priceRepository = $priceRepository;
    }

    /**
     * Подгрузка файлов
     * <blockquote>Ключ будет иметь название соответствующего поля/свойства</blockquote>
     *
     * @param array<BaseIblockModel> $elementList
     * @param array<string> $fieldNames Поля и свойства с типом "Файл"
     */
    public function loadFiles(array &$elementList, array $fieldNames): void
    {
        if (empty($elementList) || empty($fieldNames)) {
            return;
        }

        $fileFieldsNameList = ['PREVIEW_PICTURE', 'DETAIL_PICTURE'];

        $fileIdList = [];
        foreach ($elementList as $element) {
            foreach ($fieldNames as $field) {
                if (in_array($field, $fileFieldsNameList, true)) {
                    if ($fileId = $element->getField($field)) {
                        $fileIdList[] = (int)$fileId;
                    }
                } elseif ($fileIds = $element->getProperty($field)) {
                    $fileIds = is_array($fileIds) ? $fileIds : [$fileIds];
                    foreach ($fileIds as $id) {
                        $fileIdList[] = $id;
                    }
                }
            }
        }

        $fileMap = $fileIdList ? $this->fileRepository->getByIdList($fileIdList) : [];
        foreach ($elementList as $element) {
            foreach ($fieldNames as $field) {
                if (in_array($field, $fileFieldsNameList, true)) {
                    if ($fileId = $element->getField($field)) {
                        $element->setCustomField($field, $fileMap[$fileId] ?? null);
                    }
                } elseif ($fileIds = $element->getProperty($field)) {
                    if (is_array($fileIds)) {
                        $files = [];
                        foreach ($fileIds as $id) {
                            if ($file = $fileMap[$id] ?? null) {
                                $files[$id] = $file;
                            }
                        }
                        $element->setCustomField($field, $files);
                    } else {
                        $element->setCustomField($field, $fileMap[$fileIds] ?? null);
                    }
                }
            }
        }
    }

    /**
     * Подгрузка только основных разделов
     *
     * @param array<BaseIblockModel> $elementList
     * @param string $key Ключ кастомного поля, в которое сохранится раздел
     * @param bool $loadUf Подгружать ли UF_* поля
     * @param array $select Поля раздела, которые нужно получить
     */
    public function loadSections(
        array &$elementList,
        string $key = 'IBLOCK_SECTION',
        bool $loadUf = false,
        array $select = ['*']
    ): void {
        if (empty($elementList)) {
            return;
        }

        $sectionIdMap = [];
        foreach ($elementList as $element) {
            if ($id = $element->getSectionId()) {
                $sectionIdMap[$id] = $id;
            }
        }

        $iblockId = $loadUf ? current($elementList)::getIblockId() : null;
        $sectionsMap = $sectionIdMap ? $this->sectionRepository->getByIdList($sectionIdMap, $iblockId, $select) : [];
        foreach ($elementList as $element) {
            $element->setCustomField($key, $sectionsMap[$element->getSectionId()] ?? null);
        }
    }

    /**
     * Подгрузка привязанных разделов
     *
     * @param array<BaseIblockModel> $elementList
     * @param string $key Ключ кастомного поля, в которое сохранится раздел
     * @param bool $loadUf Подгружать ли UF_* поля
     * @param array $select Поля раздела, которые нужно получить
     */
    public function loadLinkedSections(
        array &$elementList,
        string $key = 'LINKED_SECTIONS',
        bool $loadUf = false,
        array $select = ['*']
    ): void {
        if (empty($elementList)) {
            return;
        }

        $idList = [];
        foreach ($elementList as $element) {
            $idList[] = $element->getId();
        }

        $iblockId = $loadUf ? current($elementList)::getIblockId() : null;
        $elementIdToSectionsMap = $this->sectionRepository->getLinkedSectionsByElementIdList($idList, $iblockId, $select);

        foreach ($elementList as $element) {
            $element->setCustomField($key, $elementIdToSectionsMap[$element->getId()] ?? []);
        }
    }

    /**
     * Подгрузка всего дерева разделов.
     * В результате будет словарь вида:
     * <code>
     * [
     *     SECTION_ID_WITH_DEPTH_LEVEL_N => {
     *         SECTION_WITH_DEPTH_LEVEL_1,
     *         SECTION_WITH_DEPTH_LEVEL_2,
     *         SECTION_WITH_DEPTH_LEVEL_3,
     *         ...
     *     },
     *     SECTION_ID_WITH_DEPTH_LEVEL_N => {
     *         SECTION_WITH_DEPTH_LEVEL_1,
     *         SECTION_WITH_DEPTH_LEVEL_2,
     *         SECTION_WITH_DEPTH_LEVEL_3,
     *         ...
     *     },
     *     ...
     * ]
     * </code>
     *
     * @param array<BaseIblockModel> $elementList
     * @param string $key Ключ кастомного поля, в которое сохранится раздел
     * @param bool $loadUf Подгружать ли UF_* поля
     * @param array $select Поля раздела, которые нужно получить
     */
    public function loadSectionsTree(
        array &$elementList,
        string $key = 'LINKED_SECTIONS_TREE',
        bool $loadUf = false,
        array $select = ['*']): void
    {
        if (empty($elementList)) {
            return;
        }

        $elementIdList = [];
        foreach ($elementList as $element) {
            $elementIdList[] = $element->getId();
        }

        $elementIdToSectionIdListMap = $this->sectionRepository->getLinkedSectionIdsByElementIdList($elementIdList);
        $sectionIdList = array_unique(array_merge(...$elementIdToSectionIdListMap));

        $iblockId = $loadUf ? current($elementList)::getIblockId() : null;
        $linkedSectionIdToParentsMap = $sectionIdList
            ? $this->sectionRepository->getSectionsIdToParentsMap($sectionIdList, $iblockId, $select)
            : [];

        foreach ($elementList as $element) {
            $sections = [];
            foreach ($elementIdToSectionIdListMap[$element->getId()] ?? [] as $linkedSectionId) {
                if ($sectionsTree = $linkedSectionIdToParentsMap[$linkedSectionId] ?? null) {
                    $sections[$linkedSectionId] = $sectionsTree;
                }
            }

            $element->setCustomField($key, $sections);
        }
    }

    /**
     * Подгрузка свойств типа "Справочник"
     * <blockquote>Ключ будет иметь название соответствующего свойства</blockquote>
     *
     * @param array<BaseIblockModel> $elementList
     * @param array<string> $propertyCodeList
     */
    public function loadHighloadProps(array &$elementList, array $propertyCodeList): void
    {
        if (empty($elementList)) {
            return;
        }

        if (empty($propertyCodeList)) {
            return;
        }

        $propertyCodeToXmlIdListMap = [];
        foreach ($propertyCodeList as $propertyCode) {
            $propertyCodeToXmlIdListMap[$propertyCode] = [];
            foreach ($elementList as $item) {
                $value = $item->getProperty($propertyCode);
                if (empty($value)) {
                    continue;
                }

                if ($value instanceof HighloadPropertyDto) {
                    $propertyCodeToXmlIdListMap[$propertyCode][$value->xmlId] = $value->xmlId;
                } elseif (is_array($value)) {
                    foreach ($value as $val) {
                        $propertyCodeToXmlIdListMap[$propertyCode][$val->xmlId] = $val->xmlId;
                    }
                } elseif (is_string($value)) {
                    $propertyCodeToXmlIdListMap[$propertyCode][$value] = $value;
                }
            }
        }

        if (empty($propertyCodeToXmlIdListMap)) {
            return;
        }

        $iblockId = current($elementList)::getIblockId();
        foreach ($propertyCodeToXmlIdListMap as $propertyCode => $xmlIdList) {
            $xmlIdToItemMap = $xmlIdList ? $this->propertyRepository->getHighloadItemsMap($iblockId, $propertyCode, array_values($xmlIdList)) : [];
            foreach ($elementList as $item) {
                $value = $item->getProperty($propertyCode);
                if ($value instanceof HighloadPropertyDto) {
                    $item->setCustomField($propertyCode, $xmlIdToItemMap[$value->xmlId] ?? null);
                    continue;
                }

                if (is_array($value)) {
                    $values = [];
                    foreach ($value as $val) {
                        if ($val instanceof HighloadPropertyDto) {
                            if ($row = $xmlIdToItemMap[$val->xmlId] ?? null) {
                                $values[] = $row;
                            }
                        } elseif ($row = $xmlIdToItemMap[$val] ?? null) {
                            $values[] = $row;
                        }
                    }

                    $item->setCustomField($propertyCode, $values);
                    continue;
                }

                $item->setCustomField($propertyCode, $xmlIdToItemMap[$value] ?? null);
            }
        }
    }

    /**
     * Подгрузка значений типа "Привязка к элементу инфоблока"
     *
     * @param array<BaseIblockModel> $elementList
     * @param array<int|class-string<BaseIblockModel>,string> $idOrCodeOrModelToPropertyCodeMap Список свойств или
     *     словарь вида "класс модели" / "id инфоблока" / "символьный код инфоблока" => "свойство"
     * @param bool $loadProps Подгружать свойства
     * @param bool $generateLinks Генерировать ссылки (использовать GetNext вместо Fetch)
     * @param array $select Поля, которые нужно выбрать
     */
    public function loadLinkedElements(
        array &$elementList,
        array $idOrCodeOrModelToPropertyCodeMap,
        bool $loadProps = false,
        bool $generateLinks = false,
        array $select = ['*']
    ): void {
        if (empty($elementList)) {
            return;
        }

        if ($idOrCodeOrModelToPropertyCodeMap === array_values($idOrCodeOrModelToPropertyCodeMap)) {
            throw new \LogicException('В качестве ключей нужно указать id, символьный код или модель инфоблока');
        }

        foreach ($idOrCodeOrModelToPropertyCodeMap as $idOrCodeOrModel => $propertyCode) {
            $idList = [];
            foreach ($elementList as $element) {
                if ($value = $element->getProperty($propertyCode)) {
                    if (!is_array($value)) {
                        $value = is_array($value) ? $value : [$value];
                    }

                    foreach ($value as $id) {
                        $idList[$id] = $id;
                    }
                }
            }

            $query = IblockQueryBuilder::make()->select($select)->filter(['=ID' => $idList]);
            if (class_exists($idOrCodeOrModel)) {
                $query->model($idOrCodeOrModel);
            } elseif (is_numeric($idOrCodeOrModel)) {
                $query->setIblockId((int)$idOrCodeOrModel);
            } else {
                $query->setIblockCode($idOrCodeOrModel);
            }

            if ($loadProps) {
                $query->loadProps();
            }
            if ($generateLinks) {
                $query->generateLinks();
            }

            $elementsMap = $idList ? $query->get() : [];
            foreach ($elementList as $element) {
                $value = $element->getProperty($propertyCode);
                if (!is_array($value)) {
                    $value = is_array($value) ? $value : [$value];
                }
                foreach ($value as $id) {
                    $element->setCustomField($propertyCode, $elementsMap[$id] ?? null);
                }
            }
        }
    }

    /**
     * Подгрузка родительских элементов (по CML2_LINK)
     *
     * @param array<BaseIblockModel> $elementList
     * @param int|string|class-string<BaseIblockModel> $parentIblockIdOrCodeOrModel
     * @param string $parentPropertyCode
     * @param bool $loadProps
     * @param bool $generateLinks
     * @param array $select
     */
    public function loadParents(
        array &$elementList,
        $parentIblockIdOrCodeOrModel,
        string $parentPropertyCode = 'CML2_LINK',
        bool $loadProps = false,
        bool $generateLinks = false,
        array $select = ['*']
    ): void {
        if (empty($elementList)) {
            return;
        }

        $parentIdList = [];
        foreach ($elementList as $element) {
            if ($id = (int)$element->getProperty($parentPropertyCode)) {
                $parentIdList[$id] = $id;
            }
        }

        $parentsMap = [];
        if ($parentIdList) {
            $query = IblockQueryBuilder::make()->select($select)->filter(['=ID' => $parentIdList]);
            if (class_exists($parentIblockIdOrCodeOrModel)) {
                $query->model($parentIblockIdOrCodeOrModel);
            } elseif (is_numeric($parentIblockIdOrCodeOrModel)) {
                $query->setIblockId((int)$parentIblockIdOrCodeOrModel);
            } else {
                $query->setIblockCode($parentIblockIdOrCodeOrModel);
            }

            if ($loadProps) {
                $query->loadProps();
            }
            if ($generateLinks) {
                $query->generateLinks();
            }

            $parentsMap = $query->get();
        }

        foreach ($elementList as $element) {
            $parentId = (int)$element->getProperty($parentPropertyCode);
            $element->setCustomField($parentPropertyCode, $parentsMap[$parentId] ?? null);
        }
    }

    /**
     * Подгрузка цен торговых предложений
     *
     * @param array<BaseIblockModel> $elementList
     * @param array<string> $priceTypeList Список типов цен (Название типа)
     * @param string $key Ключ кастомного поля, в которое сохранятся цены
     */
    public function loadPrices(array &$elementList, array $priceTypeList = [], string $key = 'CATALOG_PRICES'): void
    {
        if (empty($elementList)) {
            return;
        }

        $elementIdList = [];
        foreach ($elementList as $element) {
            $elementIdList[] = $element->getId();
        }

        $offerIdToPricesMap = $this->priceRepository->getElementIdToPriceTypesMap($elementIdList, $priceTypeList);
        foreach ($elementList as $element) {
            $element->setCustomField($key, $offerIdToPricesMap[$element->getId()] ?? []);
        }
    }
}
