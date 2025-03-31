<?php

namespace Shaman\D7Models;

use Bitrix\Iblock\InheritedProperty\SectionValues;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Type\DateTime;

class Section extends BaseModel
{
    public static string $table = SectionTable::class;

    public function getId(): int
    {
        return (int)parent::getId();
    }

    /**
     * Символьный код
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->getField('CODE') ?? '';
    }

    /**
     * Внешний код раздела
     *
     * @return string
     */
    public function getXmlId(): string
    {
        return $this->getField('XML_ID') ?? '';
    }

    /**
     * ID инфоблока
     *
     * @return int
     */
    public function getIblockId(): int
    {
        return $this->getField('IBLOCK_ID');
    }

    /**
     * Родительский раздел
     *
     * @return int|null
     */
    public function getSectionId(): ?int
    {
        return (int)$this->getField('IBLOCK_SECTION_ID') ?: null;
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
     * Сортировка
     *
     * @return int
     */
    public function getSort(): int
    {
        return (int)$this->getField('SORT');
    }

    /**
     * Проверка: активен ли раздел
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->getField('ACTIVE') === 'Y';
    }

    /**
     * Проверка: активен ли раздел с учётом родительских разделов
     *
     * @return bool
     */
    public function isGlobalActive(): bool
    {
        return $this->getField('GLOBAL_ACTIVE') === 'Y';
    }

    /**
     * Дата последнего изменения записи
     *
     * @return DateTime
     */
    public function getDateModified(): DateTime
    {
        return $this->getField('TIMESTAMP_X');
    }

    /**
     * Дата создания записи
     *
     * @return DateTime
     */
    public function getDateCreated(): DateTime
    {
        return $this->getField('DATE_CREATE');
    }

    /**
     * ID пользователя, изменившего раздел
     *
     * @return int|null
     */
    public function getModifiedByUserId(): ?int
    {
        return (int)$this->getField('MODIFIED_BY') ?: null;
    }

    /**
     * ID пользователя, создавшего раздел
     *
     * @return int|null
     */
    public function getCreatedByUserId(): ?int
    {
        return (int)$this->getField('CREATED_BY') ?: null;
    }

    /**
     * ID превью изображения
     *
     * @return int|null
     */
    public function getPreviewPictureId(): ?int
    {
        return (int)$this->getField('PICTURE') ?: null;
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
     * Уровень вложенности
     *
     * @return int
     */
    public function getDepthLevel(): int
    {
        return (int)$this->getField('DEPTH_LEVEL');
    }

    /**
     * @return int
     */
    public function getLeftMargin(): int
    {
        return (int)$this->getField('LEFT_MARGIN');
    }

    /**
     * @return int
     */
    public function getRightMargin(): int
    {
        return (int)$this->getField('LEFT_MARGIN');
    }

    /**
     * Описание
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->getField('DESCRIPTION');
    }

    /**
     * Тип описания
     *
     * @return string|null 'text'|'html'
     */
    public function getDescriptionType(): ?string
    {
        return $this->getField('DESCRIPTION_TYPE');
    }

    /**
     * Строка с данными для поиска
     *
     * @return string|null
     */
    public function getSearchableContent(): ?string
    {
        return $this->getField('SEARCHABLE_CONTENT') ?: null;
    }

    /**
     * Получение SEO информации
     *
     * @return array
     */
    public function getSeoInfo(): array
    {
        $iblockId = $this->getIblockId();
        $seoInfo = new SectionValues($iblockId, $this->getId());

        return $seoInfo->getValues() ?? [];
    }

    /**
     * Получение элементов со всеми UF_* полями без возможности фильтрации по ним
     *
     * @param int $iblockId
     *
     * @return QueryBuilder<self>
     */
    public static function queryWithUf(): QueryBuilder
    {
        if (func_num_args() && $iblockId = (int)func_get_arg(0)) {
            return static::query()->ufTable("b_uts_iblock_{$iblockId}_section");
        }

        throw new \RuntimeException('Требуется передать параметр ID инфоблока в функцию: queryWithUf($iBlockId)');
    }

    /**
     * Можно фильтровать по UF_* полям и выбирать только необходимые,
     * но поля с типом "Список" не подгружаются автоматически как в методе self::queryWithUf().
     *
     * Для получения значений свойств типа "Список" нужно делать join либо запрос к \Bitrix\Main\UserFieldTable
     *
     * @param int $iblockId
     *
     * @return QueryBuilder<self>
     */
    public static function queryWithUfCompiled(int $iblockId): QueryBuilder
    {
        $table = \Bitrix\Iblock\Model\Section::compileEntityByIblock($iblockId);

        return QueryBuilder::query($table)->model(static::class);
    }
}
