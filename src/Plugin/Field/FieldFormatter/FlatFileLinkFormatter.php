<?php

namespace Drupal\btw_links\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "flat_file_link",
 *   label = @Translation("Flat File Link"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class FlatFileLinkFormatter extends LinkFormatter {

  protected function parentProcess(FieldItemListInterface $items, $langcode) {
    $element = [];
    $entity = $items->getEntity();
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      // By default use the full URL as the link text.
      $url = $this->buildUrl($item);
      $link_title = $url->toString();

      // If the title field value is available, use it for the link text.
      if (empty($settings['url_only']) && !empty($item->title)) {
        // Unsanitized token replacement here because the entire link title
        // gets auto-escaped during link generation in
        // \Drupal\Core\Utility\LinkGenerator::generate().
        if (!is_array($item->title)) {
          $link_title = \Drupal::token()
            ->replace($item->title, [$entity->getEntityTypeId() => $entity], ['clear' => TRUE]);
        }
        else {
          $link_title = $item->title;
        }
      }

      // Trim the link text to the desired length.
      if (!empty($settings['trim_length']) && (!is_array($link_title))) {
        $link_title = Unicode::truncate($link_title, $settings['trim_length'], FALSE, TRUE);
      }

      if (!empty($settings['url_only']) && !empty($settings['url_plain'])) {
        $element[$delta] = [
          '#plain_text' => $link_title,
        ];

        if (!empty($item->_attributes)) {
          // Piggyback on the metadata attributes, which will be placed in the
          // field template wrapper, and set the URL value in a content
          // attribute.
          // @todo Does RDF need a URL rather than an internal URI here?
          // @see \Drupal\Tests\rdf\Kernel\Field\LinkFieldRdfaTest.
          $content = str_replace('internal:/', '', $item->uri);
          $item->_attributes += ['content' => $content];
        }
      }
      else {
        $element[$delta] = [
          '#type' => 'link',
          '#title' => $link_title,
          '#options' => $url->getOptions(),
        ];
        $element[$delta]['#url'] = $url;

        if (!empty($item->_attributes)) {
          $element[$delta]['#options'] += ['attributes' => []];
          $element[$delta]['#options']['attributes'] += $item->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and should not be rendered in the field template.
          unset($item->_attributes);
        }
        // Set any non _blank target attribute to _parent by default.
        if (isset($element[$delta]['#options']['attributes']['target']) && $element[$delta]['#options']['attributes']['target'] != '_blank') {
          $element[$delta]['#options']['attributes']['target'] = '_parent';
        }
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    foreach($items as $delta => &$item) {
      $url = $this->buildUrl($item);
      $options = $item->get('options')->getValue();

      // Add necessary attributes to main nav items.
      if ($this->fieldDefinition->getFieldStorageDefinition()->getName() == 'field_ff_main_navigation') {

        if (!isset($options['attributes'])) {
          $options['attributes']['class'] = '';
        }
        $class = (strpos($options['attributes']['class'], 'nav-link') === false) ? 'nav-link ' . $options['attributes']['class'] : $options['attributes']['class'];
        $options['attributes']['class'] = $class;
        $item->set('options', $options);
      }

      if ($url->isExternal()) {
        // If it is a social network link then check for an icon.
        if ($item->getFieldDefinition()->get('field_name') == 'field_social_network_link') {
          // Add a title.
          $title = $item->get('title')->getValue();
          if (!is_array($title)) {
            $options['attributes']['title'] = $title;
          }
          /**
           * @var \Drupal\paragraphs\Entity\Paragraph $parent
           */
          $parent = $item->getEntity();
          /** @var \Drupal\Core\TypedData\TypedDataInterface|null $icon */
          if (!empty($icon = $parent->get('field_text_icon_code')->first())) {
              $item->set('title', [
                '#type' => 'inline_template',
                '#template' => $icon->value
              ]);
          }
          $item->set('options', $options);
        }
        continue;
      }
      if (!$url->isRouted()) {
        continue;
      }
      // Remove items with <nolink> or <none>.
      if ($url->getRouteName() == '<no-link>' || $url->getRouteName() == '<none>') {
        $items->removeItem($delta);
      }
      /** @var Node|\Drupal\paragraphs\Entity\Paragraph $entity */

      if (!empty($page_node = $item->getEntity())) {
        // This entity could be a paragraph, so we have to check and get the parent.
        if ($page_node->getEntityTypeId() == 'paragraph') {
          // If this paragraph has a parent entity...
          if (!empty($parent = $page_node->getParentEntity())) {
            $page_node = $parent;
            // The parent could also be a paragraph so get its parent, which will always be the page node.
            if ($parent->getEntityTypeId() == 'paragraph') {
              if (!empty($grandparent = $parent->getParentEntity())) {
                $page_node = $grandparent;
              }
            }
          }
        }
      }
      // Special routes only apply to nodes (site pages).
      if ($page_node->getEntityTypeId() == 'node') {
        // Manage the <home> route.
        if ($url->getRouteName() == '<home>') {
          // Get the top level 'homepage' node.
          if ($page_node->getType() == 'site_v2') {
            $item->set('uri', "internal:/node/{$page_node->id()}");
          }
          elseif (in_array($page_node->getType(), FLAT_FILE_SUBPAGE_CTS)) {
            // Get the site page.
            if (!empty($res = $page_node->get('field_parent_site')->referencedEntities())) {
              $item->set('uri', "internal:/node/{$res[0]->id()}");
            }
          }
        }
        // Manage the <current> route.
        if ($url->getRouteName() == '<current_page>') {
          $item->set('uri', "internal:/node/{$page_node->id()}");
        }
      }
    }
    return $this->parentProcess($items, $langcode);
  }

}
