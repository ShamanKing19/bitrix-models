<?php

namespace Shaman\Repositories;

use Shaman\D7Models\File;

class FileRepository
{
    public static function make(): self
    {
        return new self();
    }

    /**'
     * @param int $id
     *
     * @return File|null
     */
    public function getById(int $id): ?File
    {
        return File::query()->filter(['=ID' => $id])->limit(1)->first();
    }

    /**
     * @param array $fileIdList
     *
     * @return array<File>
     */
    public function getByIdList(array $fileIdList): array
    {
        return File::query()->filter(['=ID' => $fileIdList])->get();
    }

    /**
     * Получение ресайзнутых картинок и файлов из таблицы b_file
     *
     * @param array $fileIdList id файлов
     * @param int $width максимальная ширина картинки
     * @param int $height максимальная высота картинки
     * @param int $mode режим сжатия
     *
     * @return array
     */
    public static function getResizedImages(array $fileIdList, int $width, int $height, int $mode = BX_RESIZE_IMAGE_PROPORTIONAL): array
    {
        $images = [];
        foreach (File::query()->where('ID', $fileIdList)->getIterator() as $image) {
            $images[$image->getId()] = $image->canBeResized() ? $image->resize($width, $height, $mode) : $image;
        }

        return $images;
    }
}
