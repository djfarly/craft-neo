<?php
namespace benf\neo;

use benf\neo\listeners\CraftQLGetFieldSchema;
use yii\base\Event;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use craft\web\twig\variables\CraftVariable;

use benf\neo\controllers\Conversion as ConversionController;
use benf\neo\controllers\Input as InputController;
use benf\neo\services\Blocks as BlocksService;
use benf\neo\services\BlockTypes as BlockTypesService;
use benf\neo\services\Conversion as ConversionService;
use benf\neo\services\Fields as FieldsService;

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
	public $schemaVersion = '2.2.0';

	/**
	 * @inheritdoc
	 */
	public $controllerMap = [
		'conversion' => ConversionController::class,
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
			'conversion' => ConversionService::class,
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

		Craft::$app->getProjectConfig()
			->onAdd('neoBlockTypes.{uid}', [$this->blockTypes, 'handleChangedBlockType'])
			->onUpdate('neoBlockTypes.{uid}', [$this->blockTypes, 'handleChangedBlockType'])
			->onRemove('neoBlockTypes.{uid}', [$this->blockTypes, 'handleDeletedBlockType'])
			->onAdd('neoBlockTypeGroups.{uid}', [$this->blockTypes, 'handleChangedBlockTypeGroup'])
			->onUpdate('neoBlockTypeGroups.{uid}', [$this->blockTypes, 'handleChangedBlockTypeGroup'])
			->onRemove('neoBlockTypeGroups.{uid}', [$this->blockTypes, 'handleDeletedBlockTypeGroup']);
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
