<?php

namespace Shaman\D7Models;

use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

class User extends BaseModel
{
    public static string $table = \Bitrix\Main\UserTable::class;

    public function getId(): int
    {
        return (int)parent::getId();
    }

    /**
     * Логин
     *
     * @return string
     */
    public function getLogin(): string
    {
        return $this->getField('LOGIN');
    }

    /**
     * Пароль
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->getField('PASSWORD');
    }

    /**
     * Почта
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->getField('EMAIL');
    }

    /**
     * Поле "Телефон"
     *
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->getField('PERSONAL_PHONE') ?: null;
    }

    /**
     * Имя
     *
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->getField('NAME') ?: null;
    }

    /**
     * Фамилия
     *
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->getField('LAST_NAME') ?: null;
    }

    /**
     * Отчество
     *
     * @return string|null
     */
    public function getPatronymic(): ?string
    {
        return $this->getField('SECOND_NAME') ?: null;
    }

    /**
     * ФИО
     *
     * @return string|null
     */
    public function getFullName(): ?string
    {
        $nameList = array_filter([$this->getLastName(), $this->getFirstName(), $this->getPatronymic()]);

        return implode(' ', $nameList) ?: null;
    }

    /**
     * Обращение
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->getField('TITLE') ?: null;
    }

    /**
     * Модуль регистрации
     *
     * @return string|null
     */
    public function getExternalAuthId(): ?string
    {
        return $this->getField('EXTERNAL_AUTH_ID') ?: null;
    }

    /**
     * Внешний код
     *
     * @return string|null
     */
    public function getXmlId(): ?string
    {
        return $this->getField('XML_ID') ?: null;
    }

    /**
     * BX_USER_ID
     *
     * @return string|null
     */
    public function getBxUserId(): ?string
    {
        return $this->getField('BX_USER_ID') ?: null;
    }

    /**
     * Код подтверждения аккаунта
     *
     * @return string|null
     */
    public function getConfirmCode(): ?string
    {
        return $this->getField('CONFIRM_CODE') ?: null;
    }

    /**
     * ID сайта
     *
     * @return string|null
     */
    public function getSiteId(): ?string
    {
        return $this->getField('LID') ?: null;
    }

    /**
     * Язык
     *
     * @return string|null
     */
    public function getLanguageId(): ?string
    {
        return $this->getField('LANGUAGE_ID');
    }

    /**
     * @return string|null
     */
    public function getTimezone(): ?string
    {
        return $this->getField('TIME_ZONE') ?: null;
    }

    /**
     * @return int|null
     */
    public function getTimezoneOffset(): ?int
    {
        return (int)$this->getField('TIME_ZONE_OFFSET') ?: null;
    }

    /**
     * Дата регистрации
     *
     * @return DateTime
     */
    public function getRegistrationDate(): DateTime
    {
        return $this->getField('DATE_REGISTER');
    }

    /**
     * Дата последней авторизации
     *
     * @return DateTime|null
     */
    public function getLastLoginDate(): ?DateTime
    {
        return $this->getField('LAST_LOGIN');
    }

    /**
     * Дата последней активности
     *
     * @return DateTime|null
     */
    public function getActivityDate(): ?DateTime
    {
        return $this->getField('LAST_ACTIVITY_DATE');
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
     * Дата рождения
     *
     * @return Date|null
     */
    public function getBirthday(): ?Date
    {
        return $this->getField('PERSONAL_BIRTHDAY');
    }

    /**
     * Пол
     *
     * @return string|null
     */
    public function getGender(): ?string
    {
        return $this->getField('PERSONAL_GENDER');
    }

    /**
     * Проверка: активирован ли аккаунт
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->getField('ACTIVE') === 'Y';
    }

    /**
     * Проверка: заблокирован ли аккаунт
     *
     * @return bool
     */
    public function isBlocked(): bool
    {
        return $this->getField('BLOCKED') === 'Y';
    }

    /**
     * Проверка: является ли владелец аккаунта мужчиной
     *
     * @return bool
     */
    public function isMale(): bool
    {
        return $this->getGender() === 'M';
    }

    /**
     * Проверка: является ли владелец аккаунта женщиной
     *
     * @return bool
     */
    public function isFemale(): bool
    {
        return $this->getGender() === 'F';
    }

    /**
     * Профессия
     *
     * @return string|null
     */
    public function getProfession(): ?string
    {
        return $this->getField('PERSONAL_PROFESSION') ?: null;
    }

    /**
     * Поле "Мобильный"
     *
     * @return string|null
     */
    public function getPersonalMobile(): ?string
    {
        return $this->getField('PERSONAL_MOBILE') ?: null;
    }

    /**
     * Персональный сайт
     *
     * @return string|null
     */
    public function getPersonalSite(): ?string
    {
        return $this->getField('PERSONAL_WWW') ?: null;
    }

    /**
     * ICQ
     *
     * @return string|null
     */
    public function getPersonalIcq(): ?string
    {
        return $this->getField('PERSONAL_ICQ') ?: null;
    }

    /**
     * Факс
     *
     * @return string|null
     */
    public function getPersonalFax(): ?string
    {
        return $this->getField('PERSONAL_FAX') ?: null;
    }

    /**
     * Пейджер
     *
     * @return string|null
     */
    public function getPersonalPager(): ?string
    {
        return $this->getField('PERSONAL_PAGER') ?: null;
    }

    /**
     * Страна
     *
     * @return string|null
     */
    public function getPersonalCountry(): ?string
    {
        return $this->getField('PERSONAL_COUNTRY') ?: null;
    }

    /**
     * Город
     *
     * @return string|null
     */
    public function getPersonalCity(): ?string
    {
        return $this->getField('PERSONAL_CITY') ?: null;
    }

    /**
     * Область / край
     *
     * @return string|null
     */
    public function getPersonalState(): ?string
    {
        return $this->getField('PERSONAL_STATE') ?: null;
    }

    /**
     * Улица
     *
     * @return string|null
     */
    public function getPersonalStreet(): ?string
    {
        return $this->getField('PERSONAL_STREET') ?: null;
    }

    /**
     * Почтовый индекс
     *
     * @return string|null
     */
    public function getPersonalIndex(): ?string
    {
        return $this->getField('PERSONAL_ZIP') ?: null;
    }

    /**
     * Почтовый ящик
     *
     * @return string|null
     */
    public function getPersonalMailbox(): ?string
    {
        return $this->getField('PERSONAL_MAILBOX') ?: null;
    }

    /**
     * Фотография
     *
     * @return int|null
     */
    public function getPersonalPhoto(): ?int
    {
        return $this->getField('PERSONAL_PHOTO') ?: null;
    }

    /**
     * Дополнительные заметки
     *
     * @return string|null
     */
    public function getPersonalNotes(): ?string
    {
        return $this->getField('PERSONAL_NOTES') ?: null;
    }

    /**
     * Наименование компании
     *
     * @return string|null
     */
    public function getWorkCompany(): ?string
    {
        return $this->getField('WORK_COMPANY') ?: null;
    }

    /**
     * Департамент / Отдел
     *
     * @return string|null
     */
    public function getWorkDepartment(): ?string
    {
        return $this->getField('WORK_DEPARTMENT') ?: null;
    }

    /**
     * Рабочий телефон
     *
     * @return string|null
     */
    public function getWorkPhone(): ?string
    {
        return $this->getField('WORK_PHONE') ?: null;
    }

    /**
     * Должность
     *
     * @return string|null
     */
    public function getWorkPosition(): ?string
    {
        return $this->getField('WORK_POSITION') ?: null;
    }

    /**
     * Рабочий сайт
     *
     * @return string|null
     */
    public function getWorkSite(): ?string
    {
        return $this->getField('WORK_WWW') ?: null;
    }

    /**
     * Рабочий факс
     *
     * @return string|null
     */
    public function getWorkFax(): ?string
    {
        return $this->getField('WORK_FAX') ?: null;
    }

    /**
     * Рабочий пейджер
     *
     * @return string|null
     */
    public function getWorkPager(): ?string
    {
        return $this->getField('WORK_PAGER') ?: null;
    }

    /**
     * Улица
     *
     * @return string|null
     */
    public function getWorkStreet(): ?string
    {
        return $this->getField('WORK_STREET') ?: null;
    }

    /**
     * Почтовый ящик
     *
     * @return string|null
     */
    public function getWorkMailbox(): ?string
    {
        return $this->getField('WORK_MAILBOX') ?: null;
    }

    /**
     * Страна
     *
     * @return string|null
     */
    public function getWorkCountry(): ?string
    {
        return $this->getField('WORK_COUNTRY') ?: null;
    }

    /**
     * Область / край
     *
     * @return string|null
     */
    public function getWorkState(): ?string
    {
        return $this->getField('WORK_STATE') ?: null;
    }

    /**
     * Город
     *
     * @return string|null
     */
    public function getWorkCity(): ?string
    {
        return $this->getField('WORK_CITY') ?: null;
    }

    /**
     * Рабочий почтовый индекс
     *
     * @return string|null
     */
    public function getWorkIndex(): ?string
    {
        return $this->getField('WORK_ZIP') ?: null;
    }

    /**
     * Направления деятельности
     *
     * @return string|null
     */
    public function getWorkProfile(): ?string
    {
        return $this->getField('WORK_PROFILE') ?: null;
    }

    /**
     * Логотип компании
     *
     * @return int|null
     */
    public function getWorkLogo(): ?int
    {
        return (int)$this->getField('WORK_LOGO') ?: null;
    }

    /**
     * Дополнительные заметки
     *
     * @return string|null
     */
    public function getWorkNotes(): ?string
    {
        return $this->getField('WORK_NOTES') ?: null;
    }

    /**
     * Заметки администратора
     *
     * @return string|null
     */
    public function getAdminNotes(): ?string
    {
        return $this->getField('ADMIN_NOTES') ?: null;
    }

    /**
     * @return QueryBuilder<static>
     */
    public static function queryWithUf(): QueryBuilder
    {
        return self::query()->ufTable('b_uts_user');
    }
}
