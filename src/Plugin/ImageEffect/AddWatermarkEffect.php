<?php

namespace Drupal\basic_watermark\Plugin\ImageEffect;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\image\ConfigurableImageEffectBase;

/**
 * Converts an image resource.
 *
 * @ImageEffect(
 *   id = "add_watermark",
 *   label = @Translation("Add Watermark"),
 *   description = @Translation("Adds watermark to the image")
 * )
 */
class AddWatermarkEffect extends ConfigurableImageEffectBase {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    $result = $image->apply('add_watermark', $this->configuration);
    if (!$result) {
      return FALSE;
    };

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'watermark_path' => NULL,
      'margin_x' => 0,
      'margin_y' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = [
      '#type' => 'item',
      '#markup' => t("Watermark path: @path", [
        '@path' => $this->configuration['watermark_path'],
      ]),
    ];
    $summary += parent::getSummary();

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['watermark_path'] = [
      '#type' => 'textfield',
      '#title' => t('Watermark path'),
      '#description' => t('Example: /sites/default/files/watermark.png, The image must be in png format and the path must be insite drupal root.'),
      '#default_value' => $this->configuration['watermark_path'],
      '#required' => TRUE,
    ];
    $form['margin_x'] = [
      '#title' => t('Margin x'),
      '#type' => 'textfield',
      '#description' => t('X Offset in pixels'),
      '#default_value' => $this->configuration['margin_x'],
      '#required' => TRUE,
    ];
    $form['margin_y'] = [
      '#type' => 'textfield',
      '#description' => t('Y Offset in pixels'),
      '#title' => t('Margin y'),
      '#default_value' => $this->configuration['margin_y'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $path = DRUPAL_ROOT . $form_state->getValue('watermark_path');

    if (!file_exists($path)) {
      $form_state->setError($form['watermark_path'], t('File does not exist.'));
      return;
    }

    $image_details = getimagesize($path);
    if (!$image_details || $image_details['mime'] != 'image/png') {
      $form_state->setError($form['watermark_path'], t('File not a png.'));
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['watermark_path'] = $form_state->getValue('watermark_path');
    $this->configuration['margin_x'] = $form_state->getValue('margin_x');
    $this->configuration['margin_y'] = $form_state->getValue('margin_y');
  }

}
