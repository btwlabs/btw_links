<?php

namespace Drupal\btw_links\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * A widget to input video URLs.
 *
 * @FieldWidget(
 *   id = "fs_story_block_video_embed_field_textfield",
 *   label = @Translation("FS Video Textfield"),
 *   field_types = {
 *     "video_embed_field",
 *     "fsvideopopup"
 *   },
 * )
 */
class VideoTextfield extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = [
      '#type' => 'container'
    ];
    $element['video_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Video Url'),
      '#default_value' => $items[$delta]->video_url,
      '#size' => 60,
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#attributes' => ['class' => ['js-text-full', 'text-full']],
      '#allowed_providers' => $this->getFieldSetting('allowed_providers'),
      '#theme' => 'input__video',
    ];
    $element['button_text'] = [
      '#type' => 'textfield',
      '#default_value' => $items[$delta]->button_text,
      '#title' => $this->t('Link Text')
    ];
    return $element;
  }

  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Note that this is always assuming cardinality === 1 for the video field.
    return [
			'video_url' => $values[0]['video_url'],
			'button_text' => $values[0]['button_text']
		];
  }

}
