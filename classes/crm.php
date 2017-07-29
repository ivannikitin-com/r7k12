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
	 * @param string	$name				Название контакта (НЕ ОБЯЗАТЕЛЬНО)	 
	 * @param string	$title 				Заголовок заявки (НЕ ОБЯЗАТЕЛЬНО)
	 * @param string	$comment			Комментарий к сделке (НЕ ОБЯЗАТЕЛЬНО)
	 * @param string	$create_new_lead	'0' - новая сделка создается только если нет сделки или предыдущая в статусе "успешно реализовано" или "возврат"; '1' - новая сделка создается в любом случае
	 */
	public function send( $type, $email, $phone, $name='', $title='', $comment='', $create_new_lead='0' )
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
				'r7k12id'			=> isset($_COOKIE['r7k12_si']) ? $_COOKIE['r7k12_si'] : null,
				'type' 				=> $type,
				'title'				=> $title,
				'comment'			=> $comment,
				'name'				=> $name,
				'email'				=> $email,
				'phone'				=> $phone,
				'create_new_lead'	=> $create_new_lead
			);
			$context = stream_context_create(array(
				'http' => array(
					'method' => 'POST',
					'content' => json_encode($CRM),
				),
			));

			// Передача
			return file_get_contents( 'https://r7k12.ru/' . $this->projectKey . '/crm/', false, $context );
			
		}
		catch ( Exception $e )
		{
			// Ошибка! Пишем в лог.
			$this->plugin->errorLog( $e->getMessage() );
		}		
	}	
}