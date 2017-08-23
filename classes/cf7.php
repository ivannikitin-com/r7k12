<?php
/**
 * Класс реализует обработку форм CF7
 */
namespace R7K12;
class ContactForm7
{
	/**
	 * Параметр настроек Name Fields
	 * @static
	 */
	const NAME_FIELDS_PARAM = 'name-fields'; 
    
	/**
	 * Параметр настроек Email Fields
	 * @static
	 */
	const EMAIL_FIELDS_PARAM = 'email-fields'; 
	
	/**
	 * Параметр настроек Tel Fields
	 * @static
	 */
	const TEL_FIELDS_PARAM = 'tel-fields'; 	
	
	/**
	 * Параметр настроек Comment Fields
	 * @static
	 */
	const COMMENT_FIELDS_PARAM = 'comment-fields'; 		
	
	
	/**
	 * Тип отправки для CRM
	 * @static
	 */
	const FORM_TYPE = 'Form'; 	
	
	
	/**
	 * Основной класс плагина
	 * @var Plugin
	 */
	private $plugin;
	
 	/**
	 * Поля формы с именем пользователя
	 * @var string
	 */
	private $nameField;
	
 	/**
	 * Поля формы с e-mail пользователя
	 * @var string
	 */
	private $emailField;
	
 	/**
	 * Поля формы с телефоном пользователя
	 * @var string
	 */
	private $telField;
	
 	/**
	 * Поля формы с комментариями
	 * @var string
	 */
	private $commentField;
	
       
    	
	/**
	 * Конструктор
	 * инициализирует параметры и загружает данные
	 * @param R7K12\Plugin	$plugin Ссылка на основной объект плагина
	 */
	public function __construct( $plugin )
	{
        // Инициализируем свойства
		$this->plugin = $plugin;
		$this->nameField = $this->plugin->settings->get( self::NAME_FIELDS_PARAM );
		$this->emailField = $this->plugin->settings->get( self::EMAIL_FIELDS_PARAM );
		$this->telField = $this->plugin->settings->get( self::TEL_FIELDS_PARAM );
		$this->commentField = $this->plugin->settings->get( self::COMMENT_FIELDS_PARAM );
		
		// Проверяем заполнение требуемых свойств в настройках плагина
        if ( empty ( $this->emailField ) && empty ( $this->telField ) )
		{
			// Предупреждение пользователю
			add_action( 'admin_notices', array( $this, 'showNoticeNoFields' ) );
		}
		else
		{
			// Обязательные поля есть, ставим хук на обработку
			add_action('wpcf7_mail_sent', array( $this, 'handle' ) );
		}
		    		
	}
    
	/**
	 * Предупреждение об отсуствии ключа
	 */
	public function showNoticeNoFields()
	{
        $class = 'notice notice-warning';
        $message = __( 'Contact form 7 fields not specified!', R7K12 );
        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}    
    
	/**
	 * Обработка формы
	 * @param mixed $cf7 Объект формы 
	 */	
	function handle( $cf7 ) 
	{	
		// Обработку ОБЯЗАТЕЛЬНО в блоке try catch чтобы не подвешивать форму при ошибках
		try
		{
			$this->plugin->activityLog( __( 'CF7 handle: ', R7K12 ) . $cf7->title );
			
			$name = '';
			$email = '';
			$tel = '';		
			$comment = '';		
			
			// Читаем данные и конвертируем объект формы
			// https://stackoverflow.com/questions/42807833/how-to-capture-post-data-with-contact-form7
			$submission = \WPCF7_Submission::get_instance();
			//$this->plugin->activityLog( __( 'WPCF7_Submission', R7K12 ) . ': ' . var_export( $submission, true ) );
			
			if ( ! $submission ) 
			{
				// Ошибка! Объект WPCF7_Submission не инициализирован!
				$this->plugin->errorLog( 
					__( 'No WPCF7_Submission object!', R7K12 ) . 
					'$cf7: ' . var_export( $cf7, true ) );
				return $cf7;
			}			
			
			// Данные формы
			$posted_data = $submission->get_posted_data();
			//$this->plugin->activityLog( __( 'CF7 form data', R7K12 ) . ': ' . var_export( $posted_data, true ) );
			
			// Ищем поля формы по именам, указанных в параметрах
			foreach ($posted_data as $key => $value)
			{
				// Текущее поле имя?
				if ( strpos( $this->nameField, $key ) !== false )
				{
					$name = $value;
					continue;                   
				}
				// Текущее поле e-mail?
				if ( strpos( $this->emailField, $key ) !== false )
				{
					$email = $value;
					continue;                   
				}
				// Текущее поле tel?
				if ( strpos( $this->telField, $key ) !== false )
				{
					$tel = $value;
					continue;                   
				}
				// Текущее поле comment?
				if ( strpos( $this->commentField, $key ) !== false )
				{
					$comment = strip_tags( $value );
					continue;                   
				}				
				
			} 			
			
			// Проверка заполнения полей
			if ( empty ( $email ) && empty ( $tel ) )
			{			
				// Ошибка! Поля не заполнены!
				$this->plugin->errorLog( 
					__( 'CF7 required fileds are empty!', R7K12 ) . 
					'$posted_data: ' . var_export( $posted_data, true ) );
				return $cf7;
			}
			
			// Передача
			//$this->plugin->activityLog( __CLASS__ . ': ' . __( 'Data prepared', R7K12 ) . ": $email, $tel, $name, $comment" );
			$this->plugin->crm->send( self::FORM_TYPE, $email, $tel, $name, $comment );
			
		}
		catch ( Exception $e )
		{
			// Ошибка! Пишем в лог.
			$this->plugin->errorLog( $e->getMessage() );
		}
		finally 
		{
			// Все! Возвращаем объект дальше!
			return $cf7;
		}				
	}
}