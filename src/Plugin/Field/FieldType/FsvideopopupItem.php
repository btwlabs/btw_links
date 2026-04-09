<?php

namespace Drupal\btw_links\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'fsvideopopup' field type.
 *
 * @FieldType(
 *   id = "fsvideopopup",
 *   label = @Translation("FSVideoPopup"),
 *   category = "General",
 *   default_widget = "fs_story_block_video_embed_field_textfield",
 *   default_formatter = "fs_fs_popup_video"
 * )
 */
class FsvideopopupItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $video_url = $this->get('video_url')->getValue();
    return $video_url === NULL || $video_url === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['video_url'] = DataDefinition::create('string')
      ->setLabel(t('Video Url'))
      ->setRequired(TRUE);
		$properties['button_text'] = DataDefinition::create('string')
			->setLabel(t('Button Text'))
			->setRequired(TRUE);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    $columns = [
      'video_url' => [
        'type' => 'varchar',
        'not null' => FALSE,
        'description' => 'A video url from youtube or other.',
        'length' => 255,
      ],
			'button_text' => [
				'type' => 'varchar',
				'not null' => FALSE,
				'description' => 'The text of the button.',
				'length' => 255,
			],
    ];

    $schema = [
      'columns' => $columns
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['video_url'] = $random->word(mt_rand(1, 50));
		$values['button_text'] = $random->word(mt_rand(1, 50));
    return $values;
  }

}
