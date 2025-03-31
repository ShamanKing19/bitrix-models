<?php

namespace Shaman\D7Models;

use Bitrix\Main\Type\DateTime;

class File extends BaseModel
{
    public static string $table = \Bitrix\Main\FileTable::class;

    private static string $uploadDir = 'upload';

    public function getId(): int
    {
        return (int)parent::getId();
    }

    /**
     * Путь к файлу
     *
     * @return string
     */
    public function getPath(): string
    {
        if ($this->issetCustomField('PATH')) {
            return $this->getCustomField('PATH');
        }

        return '/' . implode('/', [
            static::$uploadDir,
            $this->getSubdir(),
            $this->getFileName()
        ]);
    }

    /**
     * Дата создания/изменения
     *
     * @return DateTime
     */
    public function getTimestamp(): DateTime
    {
        return $this->getField('TIMESTAMP_X');
    }

    /**
     * Модуль, которому принадлежит файл
     *
     * @return string|null
     */
    public function getModuleId(): ?string
    {
        return $this->getField('MODULE_ID');
    }

    /**
     * Ширина
     *
     * @return int
     */
    public function getWidth(): int
    {
        return (int)$this->getField('WIDTH');
    }

    /**
     * Высота
     *
     * @return int
     */
    public function getHeight(): int
    {
        return (int)$this->getField('HEIGHT');
    }

    /**
     * Размер
     *
     * @return int
     */
    public function getSize(): int
    {
        return (int)$this->getField('FILE_SIZE');
    }

    /**
     * Тип файла
     *
     * @return string
     */
    public function getContentType(): string
    {
        return $this->getField('CONTENT_TYPE');
    }

    /**
     * Папка, в которой лежит файл относительно static::$uploadDir
     *
     * @return string
     */
    public function getSubdir(): string
    {
        return $this->getField('SUBDIR');
    }

    /**
     * Название файла
     *
     * @return string
     */
    public function getFileName(): string
    {
        return $this->getField('FILE_NAME');
    }

    /**
     * Оригинальное название файла
     *
     * @return string
     */
    public function getOriginalFileName(bool $withExtension = true): string
    {
        if ($withExtension) {
            return $this->getField('ORIGINAL_NAME');
        }

        $nameList = explode('.', $this->getField('ORIGINAL_NAME'));
        array_pop($nameList);

        return implode('.', $nameList);
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
     * Получение масштабированной копии
     *
     * @param int $width
     * @param int $height
     * @param int $mode
     *
     * @return $this
     */
    public function getResizedCopy(int $width, int $height, int $mode = BX_RESIZE_IMAGE_PROPORTIONAL): self
    {
        return (clone $this)->resize($width, $height, $mode);
    }

    /**
     * Масштабирование изображения.
     * Изменяет внутреннее состояние объекта! Если нужно сохранить исходное изображение, лучше использовать
     * $this->getResizedCopy()
     *
     * @param int $width
     * @param int $height
     * @param int $mode
     *
     * @return $this
     */
    public function resize(int $width, int $height, int $mode = BX_RESIZE_IMAGE_PROPORTIONAL): self
    {
        if ($this->canBeResized()) {
            $data = \CFile::resizeImageGet($this->toArray(), ['width' => $width, 'height' => $height], $mode, true);
            $this->setCustomField('PATH', $data['src']);
            $this->fields['WIDTH'] = $data['width'];
            $this->fields['HEIGHT'] = $data['height'];
        }

        return $this;
    }

    /**
     * Проверка: можно ли масштабировать изображение
     *
     * @return bool
     */
    public function canBeResized(): bool
    {
        return in_array($this->getContentType(), ['image/png', 'image/jpeg', 'image/jpg']);
    }
}
