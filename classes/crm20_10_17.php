<?php
/**
 * Класс реализует передачу данных в r7k12
 */
namespace R7K12;
class CRM
{
	/**
	 * Параметр настроек Project Key
	 * @static
	 */
	const PROJECTKEY_PARAM = 'project-key';    
    
	/**
	 * Основной класс плагина
	 * @var Plugin
	 */
	private $plugin;	    
    
 	/**
	 * PROJECT KEY
	 * @var string
	 */
	private $projectKey;   
    	
	/**
	 * Конструктор
	 * инициализирует параметры и загружает данные
	 * @param R7K12\Plugin	$plugin Ссылка на основной объект плагина
	 */
	public function __construct( $plugin )
	{
        $this->plugin = $plugin;
        $this->projectKey = $this->plugin->settings->get( self::PROJECTKEY_PARAM );
        
        if ( empty ( $this->projectKey ) )
		    add_action( 'admin_notices', array( $this, 'showNoticeNoKey' ) );
		
		// Подключение скрипта и регистрация просмотра страницы
		add_action( 'wp_enqueue_scripts', array( $this, 'loadScript' ) );
		add_action( 'wp_footer', array( $this, 'addPageView' ) );
	}
	
	/**
	 * Загрузка скрипта
	 */
	public function loadScript()
	{
		wp_register_script( R7K12, 'https://r7k12.ru/scripts/' . $this->projectKey . '/counter.js');
		wp_enqueue_script( R7K12 );
	}
	
	/**
	 * Добавление PageView
	 */
	public function addPageView()
	{
		echo "<script>R7K12.send('pageview');</script>" . PHP_EOL;
	}	
    
	/**
	 * Предупреждение об отсуствии ключа
	 */
	public function showNoticeNoKey()
	{
        $class = 'notice notice-warning';
        $message = __( 'Project key for r7k12 not specified!', R7K12 );
        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}
	
	/**
	 * Отправка сообщения
	 * @param string	$type 				Тип заявки (ОБЯЗЯТЕЛЬНО)
	 * @param string	$email 				E-mail адрес контакта (ОБЯЗАТЕЛЕН ЕСЛИ НЕ УКАЗАН ТЕЛЕФОН КОНТАКТА)
	 * @param string	$phone 				Телефон контакта (ОБЯЗАТЕЛЕН ЕСЛИ НЕ УКАЗАН E-MAIL КОНТАКТА)
	 * @param string	$comment			Комментарий к сделке (НЕ ОБЯЗАТЕЛЬНО)
	 * @param string	$name				Название контакта (НЕ ОБЯЗАТЕЛЬНО)
	 * @param string	$title 				Заголовок заявки (НЕ ОБЯЗАТЕЛЬНО)
	 * @param string	$create_new_lead	'0' - новая сделка создается только если нет сделки или предыдущая в статусе "успешно реализовано" или "возврат"; '1' - новая сделка создается в любом случае
	 */
	public function send( $type, $email, $phone, $name='', $comment='', $title='', $create_new_lead='1', $orderMethod='website' )
	{
		try
		{
			// Без ключа не передаем!
			if ( empty( $this->projectKey ) )
			{
				$this->plugin->errorLog( __CLASS__ . ': ' . __( 'Project key is empty!', R7K12 ) );
				return false;
			}

			// Если пустые поля, не передаем!
			if ( empty( $email ) && empty ( $phone ) )
			{
				$this->plugin->errorLog( __CLASS__ . ': ' . __( 'E-mail and Phone fields are empty!', R7K12 ) );
				return false;			
			}
			
			// Подготовка массива
			$CRM = array(
				'r7k12id'			=> isset( $_COOKIE['r7k12_si'] ) ? $_COOKIE['r7k12_si'] : null,
				'type' 				=> $type,
				'title'				=> $title,
				'comment'			=> $comment,
				'name'				=> $name,
				'email'				=> $email,
				'phone'				=> $phone,
				'create_new_lead'	=> $create_new_lead,
				'fields' => array(
					'lead' => array(//Поля для сделок
						"orderType" => "",
						"orderMethod" => $orderMethod,
						"website" => ""
					)
				)

			);
			$context = stream_context_create(array(
				'http' => array(
					'method' => 'POST',
					'content' => json_encode( $CRM ),
				),
			));

			// Передача
			$result =  file_get_contents( 'https://r7k12.ru/' . $this->projectKey . '/crm/', false, $context );
			$this->plugin->activityLog( __CLASS__ . ': ' . __( 'Data sent', R7K12 ) . ': ' . var_export( $CRM, true ) );
			return $result;
			
		}
		catch ( Exception $e )
		{
			// Ошибка! Пишем в лог.
			$this->plugin->errorLog( $e->getMessage() );
		}		
	}	
}