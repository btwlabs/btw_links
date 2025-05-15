<?php

namespace Drupal\btw_links\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'flat file generator' link widget.
 *
 * @FieldWidget(
 *   id = "flat_file_links_widget",
 *   label = @Translation("Flat File Link"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class FlatFileLinksWidget extends BetterLinksFieldWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return parent::settingsSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Use custom autocomplete element type, to limit to referenced subpages.
/*    if ($element['uri']['#type'] == 'entity_autocomplete') {
      $element['uri']['#type'] = 'entity_autocomplete_subpages';
    }*/

    $item = $this->getLinkItem($items, $delta);
    $options = $item->get('options')->getValue();

    // Alter the classes field description.
    $element['options']['attributes']['class']['#description'] = 'Add classes to the link. The classes must be separated by a space.For default button display, add "btn btn-primary".';

    // Allow adding a fragment.
    $element['options']['fragment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fragment'),
      '#description' => $this->t('Add a string that will be appended as a fragment to the url (e.g. https://mysite.com#some-section). Do Not include the #!'),
      '#default_value' => !empty($options['fragment']) ? $options['fragment'] : '',
      '#placeholder' => $this->t('my-fragment')
    ];

    // Allow adding arbitrary attributes.
    $element['options']['other_attributes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other Attributes'),
      '#description' => $this->t('Provide a list of other attributes in the form \'attr1="value1" attr2="value2" ...\''),
      '#default_value' => !empty($options['other_attributes']) ? $options['other_attributes'] : ''
    ];

    // Remove fragment from uri field if it is there.
    if (!empty($element['uri']['#default_value'])) {
      $element['uri']['#default_value'] = preg_replace('/#[a-zA-Z\-_]+/', '', $element['uri']['#default_value']);
    }

    // Change the help for uri.
    $element['uri']['#description'] = $this->t("To link to the homepage for this site use :home.\n To link to the current page use :current.\n For no link, leave blank.",
      [
        ':home' => '/<home>',
        ':current' => '/<current_page>'
      ]);
    return $element;
  }

  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);
    foreach ($values as &$value) {
      $attributes = [];
      // A valid match will have 3 elements, full, grp1(labels), grp2(values).
      preg_match_all('/([^" ]*)="([^"]*)"/', $value['options']['other_attributes'], $matches);
      if (count($matches) == 3) {
        foreach($matches[1] as $key => $attr) {
          $attributes[$attr] = $matches[2][$key];
        }
      }
      $value['options']['attributes'] = array_merge($value['options']['attributes'], $attributes);
    }
    return $values;
  }

  /**
   * Getting link items.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Returning of field items.
   * @param string $delta
   *   Returning field delta with item.
   *
   * @return \Drupal\link\LinkItemInterface
   *   Returning link items inteface.
   */
  private function getLinkItem(FieldItemListInterface $items, $delta) {
    /** @var \Drupal\link\LinkItemInterface $item */
    return $items[$delta];
  }

}
