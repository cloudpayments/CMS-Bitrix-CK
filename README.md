# CloudKassir модуль для Bitrix
Модуль позволяет интегрировать онлайн-кассу [CloudKassir](https://cloudkassir.ru) в интернет-магазин на платформе Bitrix. 
Для корректной работы модуля необходима регистрация в сервисе.

### Возможности:  
	
* Автоматическая отправка чеков прихода;
* Отправка чеков возврата прихода;
* Отправка чеков на email клиента;

### Совместимость:  

Подходящие редакции:«Малый бизнес», «Бизнес», «Корпоративный портал», «Энтерпрайз» версии 17 и выше.  

_Если вы используете платежный модуль CloudPayments совместно с модулем CloudKassir, то убедитесь, что в платежном модуле отключена отправка чеков через онлайн-кассу, во избежание дублирования кассовых чеков._

### Установка через bitrix marketplace

Зайдите на [страничку модуля](http://marketplace.1c-bitrix.ru/solutions/cloudpayments.cloudpaymentskassa/) в marketplace, нажмите "установить", укажите url сайта. После чего авторизуйтесь под админом, и скачайте и установите модуль.


### Ручная установка

1.	Скопируйте архив с github. На ftp создайте папку  
`/bitrix/modules/cloudpayments.cloudpaymentskassa/`
2.	В папку скопируйте все содержимое из архивной папки  
`\cloudpayments.cloudpaymentskassa\\.last_version\` 
3.	Далее, перейдите в раздел установки решений c marketplace в админке  
`/bitrix/admin/partner_modules.php?lang=ru`  
И нажмите напротив скопированного модуля - установить. 

### Настройка
  Модуль находится в меню "настройки" -> "модули" -> "Онлайн-касса CloudKassir"
    Чтобы модуль корректно работал, в настройках необходимо ввести PublicID, SecretAPI, ИНН вашей организации, выбрать методы отправки чека (SMS/Email), и указать платежные системы, с которыми модуль будет работать.
	Модуль устанавливается стандартным способом. 

После настройки модуля - перейти в личный кабинет﻿ сервиса CloudPayments, и в поле "Настройки уведомлений о кассовых чеках﻿":
вставить ссылку на Ваш сайт:

`ваш.сайт/bitrix/tools/cloudpayments.cloudpaymentskassa/Receipt.php`

Для работы модуля необходим curl.
