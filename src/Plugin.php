<?php
namespace benf\neo;

use benf\neo\listeners\CraftQLGetFieldSchema;
use yii\base\Event;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\services\Fields;
use craft\events\RegisterComponentTypesEvent;
use craft\web\twig\variables\CraftVariable;

use benf\neo\services\Fields as FieldsService;
use benf\neo\services\BlockTypes as BlockTypesService;
use benf\neo\services\Blocks as BlocksService;
use benf\neo\controllers\Input as InputController;

/**
 * Class Plugin
 *
 * @package benf\neo
 * @author Spicy Web <craft@spicyweb.com.au>
 * @author Benjamin Fleming
 * @since 2.0.0
 */
class Plugin extends BasePlugin
{
	/**
	 * @var Plugin
	 */
	public static $plugin;

	/**
	 * @inheritdoc
	 */
	public $schemaVersion = '2.0.0';

	/**
	 * @inheritdoc
	 */
	public $controllerMap = [
		'input' => InputController::class,
	];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		self::$plugin = $this;

		$this->setComponents([
			'fields' => FieldsService::class,
			'blockTypes' => BlockTypesService::class,
			'blocks' => BlocksService::class,
		]);

		Craft::$app->view->registerTwigExtension(new TwigExtension());

		Event::on(
			Fields::class,
			Fields::EVENT_REGISTER_FIELD_TYPES,
			function(RegisterComponentTypesEvent $event)
			{
				$event->types[] = Field::class;
			}
		);

		Event::on(
			CraftVariable::class,
			CraftVariable::EVENT_INIT,
			function(Event $event)
			{
				$event->sender->set('neo', Variable::class);
			}
		);

		Event::on(
		    Field::class,
            'craftQlGetFieldSchema',
            [CraftQLGetFieldSchema::class, 'handle']
        );

		if (class_exists('\NerdsAndCompany\Schematic\Schematic')) {
			Event::on(
				\NerdsAndCompany\Schematic\Schematic::class, 
				\NerdsAndCompany\Schematic\Schematic::EVENT_RESOLVE_CONVERTER, 
				function(\NerdsAndCompany\Schematic\Events\ConverterEvent $event) {
					$modelClass = $event->modelClass;
					if (strpos($modelClass, __NAMESPACE__) !== false) {
						$converterClass = __NAMESPACE__.'\\converters\\'.str_replace(__NAMESPACE__.'\\', '', $modelClass);
						$event->converterClass = $converterClass;
					}
				}
			);
		}
	}
}
