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
      'apply_type' => 'once',
      'position' => 'custom',
      'margin_x' => 0,
      'margin_y' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary['watermark_path'] = [
      '#type' => 'item',
      '#markup' => t("Watermark path: @path", [
        '@path' => $this->configuration['watermark_path'],
      ]),
    ];
    $summary['apply_type'] = [
      '#type' => 'item',
      '#markup' => t("Apply type: @path", [
        '@path' => $this->getApplyTypeOptions()[$this->configuration['apply_type']],
      ]),
    ];
    $summary['position'] = [
      '#type' => 'item',
      '#markup' => t("Position: @path", [
        '@path' => $this->getPositionOptions()[$this->configuration['position']],
      ]),
    ];
    $summary += parent::getSummary();

    return $summary;
  }

  /**
   * The watermark apply types.
   *
   * @return array
   *   An array with the options.
   */
  public function getApplyTypeOptions() {
    return [
      'once' => $this->t('Once'),
      'repeat' => $this->t('Repeat'),
    ];
  }

  /**
   * The watermark position options.
   *
   * @return array
   *   An array with the options.
   */
  public function getPositionOptions() {
    return [
      'custom' => $this->t('Custom'),
      'center' => $this->t('Center'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['watermark_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Watermark path'),
      '#description' => $this->t('Example: /sites/default/files/watermark.png, The image must be in png format and the path must be insite drupal root.'),
      '#default_value' => $this->configuration['watermark_path'],
      '#required' => TRUE,
    ];

    $form['apply_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Apply type'),
      '#description' => $this->t('<ul>
        <li><label>Repeat:</label> Repeat the watermark from top left until it covers the the whole image.</li>
        <li><label>Once:</label> Add the watermark once.</li>
        </ul>
      '),
      '#options' => $this->getApplyTypeOptions(),
      '#default_value' => $this->configuration['apply_type'],
    ];

    $form['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Position'),
      '#options' => $this->getPositionOptions(),
      '#states' => [
        'visible' => [
          'select[name="data[apply_type]"' => ['value' => 'once'],
        ],
      ],
      '#default_value' => $this->configuration['position'],
    ];

    $form['margin_x'] = [
      '#title' => $this->t('Margin left'),
      '#type' => 'textfield',
      '#description' => $this->t('X Offset in pixels'),
      '#default_value' => $this->configuration['margin_x'],
      '#states' => [
        'visible' => [
          ['select[name="data[apply_type]"' => ['value' => 'repeat']],
          'or',
          ['select[name="data[position]"' => ['value' => 'custom']],
        ],
      ],
      '#required' => TRUE,
    ];
    $form['margin_y'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Y Offset in pixels'),
      '#title' => $this->t('Margin top'),
      '#default_value' => $this->configuration['margin_y'],
      '#states' => [
        'visible' => [
          ['select[name="data[apply_type]"' => ['value' => 'repeat']],
          'or',
          ['select[name="data[position]"' => ['value' => 'custom']],
        ],
      ],
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
      $form_state->setError($form['watermark_path'], $this->t('File does not exist.'));
    }
    else {
      $image_details = getimagesize($path);
      if (!$image_details || $image_details['mime'] != 'image/png') {
        $form_state->setError($form['watermark_path'], $this->t('File not a png.'));
      }
    }

    $margin_x = $form_state->getValue('margin_x');
    if ($margin_x !== '' && (!is_numeric($margin_x) || intval($margin_x) != $margin_x || $margin_x <= 0)) {
      $form_state->setError($form['margin_x'], $this->t('%name must be a positive integer.', [
        '%name' => $form['margin_x']['#title'],
      ]));
    }

    $margin_y = $form_state->getValue('margin_y');
    if ($margin_y !== '' && (!is_numeric($margin_y) || intval($margin_y) != $margin_y || $margin_y <= 0)) {
      $form_state->setError($form['margin_y'], $this->t('%name must be a positive integer.', [
        '%name' => $form['margin_y']['#title'],
      ]));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['watermark_path'] = $form_state->getValue('watermark_path');
    $this->configuration['apply_type'] = $form_state->getValue('apply_type');
    $this->configuration['position'] = $form_state->getValue('position');
    $this->configuration['margin_x'] = $form_state->getValue('margin_x');
    $this->configuration['margin_y'] = $form_state->getValue('margin_y');

    if ($this->configuration['apply_type'] == 'repeat') {
      $this->configuration['position'] = 'custom';
    }

  }

}
