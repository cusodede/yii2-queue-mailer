# Queue mailer decorator for Yii2 framework
Send your emails in the background using Yii2 queues.

[![Build Status](https://github.com/cusodede/yii2-queue-mailer/actions/workflows/run_tests.yml/badge.svg)](https://github.com/cusodede/yii2-queue-mailer/actions)

# Установка

Installation

```
{
	"type": "vcs",
	"url": "https://github.com/cusodede/yii2-queue-mailer"
}
```

В секцию `repositories` файла `composer.json`, затем запускаем

```
php composer.phar require cusodede/yii2-queue-mailer "^2.0.0"
```

или добавляем

```
"cusodede/yii2-queue-mailer": "^2.0.0"
```

в секцию `require`.

# Подключение

```php
$config = [
	...
	'components' => [
		...
		'mailer' => [
			'class' => Mailer::class, // Подключаемый Mailer
			'queue' => 'emailQueue', // Наша очередь
			'syncMailer' => [
				'class' => SwiftMailer::class, // Базовый Mailer
				'useFileTransport' => 'prod' !== YII_ENV,
				'transport' => [
					'class' => Swift_SmtpTransport::class,
					'host' => 'mailrelay.******.ru',
				],
			],
		],
		...
];
```
