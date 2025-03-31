<?php

use Shaman\IblockModels\BaseIblockModel;

class Helpers
{
    /**
     * Сбор всех торговых предложений товаров и применение к ним callback-функции, если она передана
     *
     * @param array<BaseIblockModel> $elementList
     * @param callable(array<BaseIblockModel>,int):void|null $callback $callback Функция, которая применяется к массиву
     *     всех собранных торговых предложений
     *
     * @return array
     */
    public function collectOffers(array &$elementList, ?callable $callback = null): array
    {
        if (empty($elementList)) {
            return [];
        }

        $offerList = [];
        foreach ($elementList as $element) {
            foreach ($element->getSku() as $offer) {
                $offerList[] = $offer;
            }
        }

        if (isset($callback)) {
            $callback($offerList);
        }

        return $offerList;
    }
}
