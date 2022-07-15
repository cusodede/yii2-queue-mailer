<?php
declare(strict_types = 1);

use cusodede\QueueMailer\jobs\SendMessageJob;
use cusodede\QueueMailer\Mailer;
use cusodede\QueueMailer\Mailer as QueueMailer;
use PHPUnit\Framework\TestCase;
use pozitronik\helpers\PathHelper;
use pozitronik\helpers\Utils;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\mail\MessageInterface;
use yii\queue\sync\Queue;
use yii\swiftmailer\Mailer as SwiftMailer;
use yii\symfonymailer\Mailer as SymphonyMailer;

/**
 * Class SyncMailerTest
 * Тесты работы компонента с разными почтовыми модулями
 */
class SyncMailerTest extends TestCase
{

	public Mailer $mailer;

	public MessageInterface $message;

	private const TEST_EMAIL_FROM = 'admin@email.local';
	private const TEST_EMAIL_TO = 'test@email.local';

	private const QUEUE_EMAIL_DIR = '@runtime/mail/queue';

	/**
	 * Проверка работы очереди с yiisoft/yii2-swiftmailer
	 * @return void
	 * @throws InvalidConfigException
	 */
	public function testSwiftMailer(): void
	{
		PathHelper::CreateDirIfNotExisted(FileHelper::normalizePath(Yii::getAlias(static::QUEUE_EMAIL_DIR)));
		$filenames = FileHelper::findFiles(FileHelper::normalizePath(Yii::getAlias(static::QUEUE_EMAIL_DIR)));
		foreach ($filenames as $filename) FileHelper::unlink($filename);

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->mailer = Yii::createObject([
			'class' => QueueMailer::class,
			'queue' => [
				'class' => Queue::class,
				'handle' => false,
			],
			'syncMailer' => [
				'class' => SwiftMailer::class,
				'useFileTransport' => true,
				'fileTransportPath' => self::QUEUE_EMAIL_DIR,
				/*нельзя использовать коллбек, он не сериализуется*/
//				'fileTransportCallback' => fn(MailerInterface $mailer, MessageInterface $message):?string => $filename
			],
		]);

		$textBody = Utils::random_str(32);

		$message = $this->mailer->compose()
			->setSubject('test email')
			->setFrom(static::TEST_EMAIL_FROM)
			->setTextBody("body: $textBody")
			->setTo(static::TEST_EMAIL_TO);
		$this::assertTrue($this->mailer->send($message));

		$filenames = FileHelper::findFiles(FileHelper::normalizePath(Yii::getAlias(static::QUEUE_EMAIL_DIR)));
		/*До запуска очереди письмо не должно создаться*/
		$this::assertCount(0, $filenames);

		(new SendMessageJob([
			'mailer' => $this->mailer,
			'message' => $message
		]))->execute($this->mailer->queue);

		$filenames = FileHelper::findFiles(FileHelper::normalizePath(Yii::getAlias(static::QUEUE_EMAIL_DIR)));
		$this::assertCount(1, $filenames);
		$filename = $filenames[0];

		$this::assertFileExists($filename);
		$this::assertIsArray($mailContents = file($filename));
		$mailDataArray = [];
		foreach ($mailContents as $line) {
			$e = explode(':', $line);
			$mailDataArray[trim(ArrayHelper::getValue($e, 0, ''))] = trim(ArrayHelper::getValue($e, 1, ''));
		}
		$this::assertEquals($mailDataArray['From'], static::TEST_EMAIL_FROM);
		$this::assertEquals($mailDataArray['To'], static::TEST_EMAIL_TO);
		$this::assertEquals($mailDataArray['body'], $textBody);
	}

	/**
	 * Проверка работы очереди с yiisoft/yii2-symfonymailer
	 * @return void
	 * @throws InvalidConfigException
	 */
	public function testSymphonyMailer(): void
	{

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->mailer = Yii::createObject([
			'class' => QueueMailer::class,
			'queue' => [
				'class' => Queue::class,
				'handle' => false,
			],
			'syncMailer' => [
				'class' => SymphonyMailer::class,
				'deferTransportInitialization' => true,
				'transport' => [
					'dsn' => 'null://'
				]
			],
		]);

		$textBody = Utils::random_str(32);

		$message = $this->mailer->compose()
			->setSubject('test email')
			->setFrom(static::TEST_EMAIL_FROM)
			->setTextBody("body: $textBody")
			->setTo(static::TEST_EMAIL_TO);
		$this::assertTrue($this->mailer->send($message));

	}

}