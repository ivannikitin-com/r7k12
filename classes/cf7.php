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
		
		// Проверяем заполнение требуемых свойств в настройках плагина
        if ( empty ( $this->emailField ) && empty ( $this->telField ) )
		{
			// Предупреждение пользователю
			add_action( 'admin_notices', array( $this, 'showNoticeNoFields' ) );
		}
		else
		{
			// Обязательные поля есть, ставим хук на обработку
			add_action('wpcf7_before_send_mail', array( $this, 'handle' ) );
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
			// Читаем данные и конвердируем объект формы
			// См. CFDBIntegrationContactForm7.php
			$formData = $cf7;
			if ( ! isset($cf7->posted_data ) && class_exists( 'WPCF7_Submission' ) ) 
			{
				// Contact Form 7 version 3.9 removed $cf7->posted_data and now
				// we have to retrieve it from an API
				$submission = WPCF7_Submission::get_instance();
				if ($submission) 
				{
					$data = array();
					$data['title'] = $cf7->title();
					$data['posted_data'] = $submission->get_posted_data();
					$data['uploaded_files'] = $submission->uploaded_files();
					$data['WPCF7_ContactForm'] = $cf7;
					$formData = (object) $data;
				}
			}
			
			// Ищем поля формы по именам, указанных в параметрах
			$name = '';
			$email = '';
			$tel = '';
			foreach ($formData->posted_data as $key => $value)
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
			} 			
			
			// Проверка заполнения полей
			if ( empty ( $email ) && empty ( $tel ) )
			{			
				// Ошибка! Поля не заполнены!
				$this->plugin->errorLog( 
					__( 'CF7 required fileds are empty!', R7K12 ) . 
					'$formData: ' . var_export( $formData, true ) );
				return $cf7;
			}
			
			// Передача
			$this->plugin->crm->send( self::FORM_TYPE, $email, $phone, $name );
			
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