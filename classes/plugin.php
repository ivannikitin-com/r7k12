<?php
/**
 * Основной класс плагина
 */
namespace R7K12;

class Plugin
{
	/**
	 * Логи
	 * @static
	 */
	const ACTIVITY_LOG = 'activity.log';	
	const ERROR_LOG = 'error.log';
	
	/**
	 * Путь к файлам плагина
	 * @var string
	 */
	public $path;
	
	/**
	 * URL к файлам плагина
	 * @var string
	 */
	public $url;
	
	/**
	 * Параметры
	 * @var R7K12\Settings
	 */
	public $settings;
	
	/** 
	 * CRM
	 * @var R7K12\CRM
	 */
	public $crm;
	
	/** 
	 * ContactForm7
	 * @var R7K12\ContactForm7
	 */
	public $cf7;
	
	/** 
	 * WooCommerce
	 * @var R7K12\WooCommerce
	 */
	public $wc;	
		
	/**
	 * Конструктор
	 */
	public function __construct( $pluginPath, $pluginURL )
	{
		// Инициализация свойств
		$this->path = $pluginPath;	// Путь к файлам плагина
		$this->url = $pluginURL;	// URL к файлам плагина
		
		// Инициализация плагина по хуку init
		add_action( 'init', array( $this, 'init' ) );
	}
	
	/**
	 * Инициализация плагина
	 */
	public function init()
	{
		$this->settings = new Settings( R7K12, $this );
		$this->crm = new CRM( $this );
		$this->cf7 = new ContactForm7( $this );
		$this->cf7 = new WooCommerce( $this );
		
	}
	
	/**
	 * Запись логов
	 * @param string	$log		Имя файла лога
	 * @param string	$message	Сообщение для вывода
	 */
	private function log( $log, $message )
	{
		// Выводим логи только в режиме отладки
		if ( ! WP_DEBUG ) return;

		// Добавляем в сообщение дату, время и разделитель записей
		$message = '[ ' . date( 'd.m.Y H:i:s' ) . ' ]' . PHP_EOL . $message . PHP_EOL . PHP_EOL;
		
		// Файл пишем в папку плагина
		$log = $this->path . $log;
		file_put_contents( $log, $message, FILE_APPEND );
	}
	
	/**
	 * Запись в лог активности
	 * @param string	$message	Сообщение для вывода
	 */
	public function activityLog( $message )
	{	
		$this->log( self::ACTIVITY_LOG, $message );
	}
	
	/**
	 * Запись в лог ошибок
	 * @param string	$message	Сообщение для вывода
	 */
	public function errorLog( $message )
	{	
		$this->log( self::ERROR_LOG, $message );
	}	
}