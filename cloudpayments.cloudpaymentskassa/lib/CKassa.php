<?php
namespace cloudpayments\CloudpaymentsKassa;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class KassaTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> LID string(2) mandatory
 * <li> ORDER_ID int mandatory
 * <li> PAY_ID int mandatory
 * <li> STATUS bool optional default 'N'
 * <li> SUMMA double mandatory default 0.0000
 * <li> URLCHECK string(100) optional
 * <li> DOCUMENT string(10) optional
 * <li> TRANSACTION string(50) optional
 * </ul>
 *
 * @package Bitrix\Cld
 **/

class CKassaTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'vbch_cld_kassa';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return array(
            'ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('KASSA_ENTITY_ID_FIELD'),
            ),
            'TIMESTAMP_X' => new Main\Entity\DatetimeField('TIMESTAMP_X', array(
                'default_value' => new Main\Type\DateTime(),
                'title' => Loc::getMessage('KASSA_ENTITY_TIMESTAMP_X_FIELD'),
            )),
            'LID' => array(
                'data_type' => 'string',
                'required' => true,
                'validation' => array(__CLASS__, 'validateLid'),
                'title' => Loc::getMessage('KASSA_ENTITY_LID_FIELD'),
            ),
            'ORDER_ID' => array(
                'data_type' => 'integer',
                'required' => true,
                'title' => Loc::getMessage('KASSA_ENTITY_ORDER_ID_FIELD'),
            ),
            'PAY_ID' => array(
                'data_type' => 'integer',
                'required' => true,
                'title' => Loc::getMessage('KASSA_ENTITY_PAY_ID_FIELD'),
            ),
            'STATUS' => array(
                'data_type' => 'boolean',
                'values' => array('N', 'Y'),
                'title' => Loc::getMessage('KASSA_ENTITY_STATUS_FIELD'),
            ),
            'SUMMA' => array(
                'data_type' => 'float',
                'required' => true,
                'title' => Loc::getMessage('KASSA_ENTITY_SUMMA_FIELD'),
            ),
            'URLCHECK' => array(
                'data_type' => 'string',
                'validation' => array(__CLASS__, 'validateUrlcheck'),
                'title' => Loc::getMessage('KASSA_ENTITY_URLCHECK_FIELD'),
            ),
            'URLQRCODE' => array(
                'data_type' => 'string',
                'validation' => array(__CLASS__, 'validateUrlqrcode'),
                'title' => Loc::getMessage('KASSA_ENTITY_URLQRCODE_FIELD'),
            ),
            'TYPE' => array(
                'data_type' => 'string',
                'validation' => array(__CLASS__, 'validateType'),
                'title' => Loc::getMessage('KASSA_ENTITY_TYPE_FIELD'),
            ),
            'DOCUMENT' => array(
                'data_type' => 'string',
                'validation' => array(__CLASS__, 'validateDocument'),
                'title' => Loc::getMessage('KASSA_ENTITY_DOCUMENT_FIELD'),
            ),
            'TRANSACTION' => array(
                'data_type' => 'string',
                'validation' => array(__CLASS__, 'validateTransaction'),
                'title' => Loc::getMessage('KASSA_ENTITY_TRANSACTION_FIELD'),
            ),
            'DATACHECK' => array(
                'data_type' => 'string',
                'validation' => array(__CLASS__, 'validateDatacheck'),
                'title' => Loc::getMessage('KASSA_ENTITY_DATACHECK_FIELD'),
            ),
            'ACCOUNTID' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('KASSA_ENTITY_ACCOUNTID_FIELD'),
            ),
            'INN' => array(
                'data_type' => 'string',
                'validation' => array(__CLASS__, 'validateInn'),
                'title' => Loc::getMessage('KASSA_ENTITY_INN_FIELD'),
            ),
            'OFD' => array(
                'data_type' => 'string',
                'validation' => array(__CLASS__, 'validateOfd'),
                'title' => Loc::getMessage('KASSA_ENTITY_OFD_FIELD'),
            ),
            'SESSIONNUMBER' => array(
                'data_type' => 'string',
                'validation' => array(__CLASS__, 'validateSessionnumber'),
                'title' => Loc::getMessage('KASSA_ENTITY_SESSIONNUMBER_FIELD'),
            ),
            'FISCALSIGH' => array(
                'data_type' => 'string',
                'validation' => array(__CLASS__, 'validateFiscalsigh'),
                'title' => Loc::getMessage('KASSA_ENTITY_FISCALSIGH_FIELD'),
            ),
            'DEVICENUMBER' => array(
                'data_type' => 'string',
                'validation' => array(__CLASS__, 'validateDevicenumber'),
                'title' => Loc::getMessage('KASSA_ENTITY_DEVICENUMBER_FIELD'),
            ),
            'REGNUMBER' => array(
                'data_type' => 'string',
                'validation' => array(__CLASS__, 'validateRegnumber'),
                'title' => Loc::getMessage('KASSA_ENTITY_REGNUMBER_FIELD'),
            ),
        );
    }
    /**
     * Returns validators for LID field.
     *
     * @return array
     */
    public static function validateLid()
    {
        return array(
            new Main\Entity\Validator\Length(null, 2),
        );
    }
    /**
     * Returns validators for URLCHECK field.
     *
     * @return array
     */
    public static function validateUrlcheck()
    {
        return array(
            new Main\Entity\Validator\Length(null, 100),
        );
    }
    /**
     * Returns validators for URLQRCODE field.
     *
     * @return array
     */
    public static function validateUrlqrcode()
    {
        return array(
            new Main\Entity\Validator\Length(null, 250),
        );
    }
    /**
     * Returns validators for TYPE field.
     *
     * @return array
     */
    public static function validateType()
    {
        return array(
            new Main\Entity\Validator\Length(null, 15),
        );
    }
    /**
     * Returns validators for DOCUMENT field.
     *
     * @return array
     */
    public static function validateDocument()
    {
        return array(
            new Main\Entity\Validator\Length(null, 10),
        );
    }
    /**
     * Returns validators for TRANSACTION field.
     *
     * @return array
     */
    public static function validateTransaction()
    {
        return array(
            new Main\Entity\Validator\Length(null, 50),
        );
    }
    /**
     * Returns validators for DATACHECK field.
     *
     * @return array
     */
    public static function validateDatacheck()
    {
        return array(
            new Main\Entity\Validator\Length(null, 20),
        );
    }
    /**
     * Returns validators for INN field.
     *
     * @return array
     */
    public static function validateInn()
    {
        return array(
            new Main\Entity\Validator\Length(null, 15),
        );
    }
    /**
     * Returns validators for OFD field.
     *
     * @return array
     */
    public static function validateOfd()
    {
        return array(
            new Main\Entity\Validator\Length(null, 50),
        );
    }
    /**
     * Returns validators for SESSIONNUMBER field.
     *
     * @return array
     */
    public static function validateSessionnumber()
    {
        return array(
            new Main\Entity\Validator\Length(null, 50),
        );
    }
    /**
     * Returns validators for FISCALSIGH field.
     *
     * @return array
     */
    public static function validateFiscalsigh()
    {
        return array(
            new Main\Entity\Validator\Length(null, 50),
        );
    }
    /**
     * Returns validators for DEVICENUMBER field.
     *
     * @return array
     */
    public static function validateDevicenumber()
    {
        return array(
            new Main\Entity\Validator\Length(null, 50),
        );
    }
    /**
     * Returns validators for REGNUMBER field.
     *
     * @return array
     */
    public static function validateRegnumber()
    {
        return array(
            new Main\Entity\Validator\Length(null, 50),
        );
    }
}