<?php

namespace Shaman\Repositories;

use Bitrix\Iblock\SectionElementTable;
use Shaman\Contracts\Makeable;
use Shaman\D7Models\Section;

class SectionRepository implements Makeable
{
    public static function make(): self
    {
        return new self();
    }

    /**
     * Разделы по их id
     *
     * @param array<int> $idList
     * @param int|null $iblockId Если указан, то будут подгружены UF_* поля
     * @param array<string> $select Поля разделов, которые нужно выбрать
     *
     * @return array<Section>
     */
    public function getByIdList(array $idList, ?int $iblockId = null, array $select = ['*']): array
    {
        if (empty($idList)) {
            return [];
        }

        $query = isset($iblockId) ? Section::queryWithUf($iblockId) : Section::query();

        return $query->filter(['=ID' => $idList])->select($select)->get();
    }

    /**
     * Привязанные разделы по id элементов
     *
     * @param array<int> $elementIdList Список id элементов инфоблока
     * @param int|null $iblockId Если указан, то будут подгружены UF_* поля
     * @param array<string> $select Поля разделов, которые нужно выбрать
     *
     * @return array<int,array<Section>> ID элемента => [раздел1, раздел2, ...]
     */
    public function getLinkedSectionsByElementIdList(array $elementIdList, ?int $iblockId = null, array $select = ['*']): array
    {
        if (empty($elementIdList)) {
            return [];
        }

        $elementIdToSectionIdsMap = $this->getLinkedSectionIdsByElementIdList($elementIdList);
        $sectionIdList = array_unique(array_merge(...$elementIdToSectionIdsMap));

        $query = isset($iblockId) ? Section::queryWithUf($iblockId) : Section::query();
        $sectionsMap = $sectionIdList ? $query->filter(['=ID' => $sectionIdList])->select($select)->get() : [];

        $elementIdToSectionsMap = [];
        foreach ($elementIdList as $elementId) {
            $sections = [];
            foreach ($elementIdToSectionIdsMap[$elementId] ?? [] as $sectionId) {
                if ($section = $sectionsMap[$sectionId] ?? null) {
                    $sections[$sectionId] = $section;
                }
            }

            $elementIdToSectionsMap[$elementId] = $sections;
        }

        return $elementIdToSectionsMap;
    }

    /**
     * Привязанные id разделов по id элементов
     *
     * @param array $elementIdList
     *
     * @return array<int<array<int> [element_id_1 => [section_id_1, section_id_2, ...], ...]
     */
    public function getLinkedSectionIdsByElementIdList(array $elementIdList = []): array
    {
        $request = SectionElementTable::getList([
            'filter' => ['=IBLOCK_ELEMENT_ID' => $elementIdList],
            'select' => ['IBLOCK_ELEMENT_ID', 'IBLOCK_SECTION_ID']
        ]);

        $elementIdToSectionIdsMap = [];
        while ($row = $request->fetch()) {
            $elementIdToSectionIdsMap[(int)$row['IBLOCK_ELEMENT_ID']][] = (int)$row['IBLOCK_SECTION_ID'];
        }

        return $elementIdToSectionIdsMap;
    }

    /**
     * Формирование словаря из id разделов и цепочкой (древом) разделов (с учетом самого раздела)
     *
     * @param array<int> $sectionIdList
     * @param int|null $iblockId Если указан, то будут подгружены UF_* поля
     * @param array<string> $select Поля разделов, которые нужно выбрать
     *
     * @return array<int,Section[]> [id переданного раздела => [раздел1, раздел2, ...], ...]
     */
    public function getSectionsIdToParentsMap(array $sectionIdList, ?int $iblockId = null, array $select = ['*']): array
    {
        if (empty($sectionIdList)) {
            return [];
        }

        $sectionIdParentIdsMap = $this->getSectionIdToParentIdListMap($sectionIdList);
        if (empty($sectionIdParentIdsMap)) {
            return [];
        }

        $allSectionsIdList = array_merge(array_keys($sectionIdParentIdsMap), ...$sectionIdParentIdsMap);
        $allSectionsIdList = array_values(array_unique($allSectionsIdList));

        $query = isset($iblockId) ? Section::queryWithUf($iblockId) : Section::query();
        $sections = $query->filter(['=ID' => $allSectionsIdList])->select($select)->get();
        if (empty($sections)) {
            return [];
        }

        $result = [];
        foreach ($sectionIdParentIdsMap as $sectionId => $parentSectionIds) {
            foreach ($parentSectionIds as $parentSectionId) {
                if (isset($sections[$parentSectionId])) {
                    $result[$sectionId][] = $sections[$parentSectionId];
                }
            }

            if (!empty($result[$sectionId]) && isset($sections[$sectionId])) {
                $result[$sectionId][] = $sections[$sectionId];
            }
        }

        return $result;
    }

    /**
     * Словарь из id раздела и списка id его родителей (в порядке от более глубокого к менее глубокому).
     * <blockquote>Максимальное количество запросов равно максимальному значению DEPTH_LEVEL</blockquote>
     *
     * @param array<int> $sectionIdList
     *
     * @return array<int,array<int>>
     */
    public function getSectionIdToParentIdListMap(array $sectionIdList): array
    {
        if (empty($sectionIdList)) {
            return [];
        }

        $childToParentMap = [];
        $tempSectionIdList = $sectionIdList;
        while ($tempSectionIdList = $this->getSectionIdToParentIdMap($tempSectionIdList)) {
            $childToParentMap += $tempSectionIdList;
        }

        $result = [];
        foreach ($sectionIdList as $sectionId) {
            $parents = [];
            $parentSectionId = $sectionId;
            while (isset($childToParentMap[$parentSectionId])) {
                $parentSectionId = $childToParentMap[$parentSectionId];
                $parents[] = $parentSectionId;
            }

            $result[$sectionId] = array_reverse($parents);
        }

        return $result;
    }

    /**
     * Словарь из id разделов и их родителей
     *
     * @param array<int> $sectionIdList
     *
     * @return array<int,int> id раздела из параметра => id родительского раздела
     */
    private function getSectionIdToParentIdMap(array $sectionIdList): array
    {
        if (empty($sectionIdList)) {
            return [];
        }

        $result = Section::query()
            ->select(['ID', 'IBLOCK_SECTION_ID'])
            ->filter([
                '=ID' => array_values($sectionIdList),
                '!=IBLOCK_SECTION_ID' => false
            ]);

        $map = [];
        foreach ($result->getIterator() as $section) {
            $map[$section->getId()] = $section->getSectionId();
        }

        return $map;
    }
}
