<?php

namespace Drupal\basic_watermark\Plugin\ImageToolkit\Operation\gd;

use Drupal\system\Plugin\ImageToolkit\Operation\gd\GDImageToolkitOperationBase;

/**
 * Defines GD2 Add Watermark operation.
 *
 * @ImageToolkitOperation(
 *   id = "gd_add_watermark",
 *   toolkit = "gd",
 *   operation = "add_watermark",
 *   label = @Translation("Add Watermark"),
 *   description = @Translation("Adds a watermark to the image.")
 * )
 */
class AddWatermark extends GDImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    return [
      'watermark_path' => [
        'description' => 'The path to the watermark image',
      ],
      'apply_type' => [
        'description' => 'How to apply the watermark, repeat until it covers the whole image or once',
      ],
      'position' => [
        'description' => 'Where to apply center or with x and y offsets.',
      ],
      'margin_x' => [
        'description' => 'The starting x offset at which to place the watermark, in pixels',
      ],
      'margin_y' => [
        'description' => 'The starting y offset at which to place the watermark, in pixels',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateArguments(array $arguments) {
    $path = DRUPAL_ROOT . $arguments['watermark_path'];
    if (!file_exists($path) || !getimagesize($path)) {
      throw new \InvalidArgumentException("Invalid image ('{$arguments['watermark_path']}')");
    }

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments) {
    $image_resource = $this->getToolkit()->getResource();
    $watermark_filepath = DRUPAL_ROOT . $arguments['watermark_path'];
    $watermark_img = imagecreatefrompng($watermark_filepath);

    $image['width'] = imagesx($image_resource);
    $image['height'] = imagesy($image_resource);
    $watermark['width'] = imagesx($watermark_img);
    $watermark['height'] = imagesy($watermark_img);

    $margin_x = $arguments['margin_x'];
    $margin_y = $arguments['margin_y'];

    // Scale Watermark to fit on image horizontaly.
    if ($watermark['width'] + $margin_x + $margin_x > $image['width']) {
      $watermark['width'] = $image['width'] - $margin_x - $margin_x;
      $watermark_img = imagescale($watermark_img, $watermark['width']);
      $watermark['height'] = imagesy($watermark_img);
    }
    // Scale Watermark to fit on image vertically.
    if ($watermark['height'] + $margin_y + $margin_y > $image['height']) {
      $watermark['height'] = $image['height'] - $margin_y - $margin_y;
      // New width = new height * (original width / original height)
      $watermark['width'] = $watermark['height'] * (imagesx($watermark_img) / imagesy($watermark_img));
      $watermark_img = imagescale($watermark_img, $watermark['width'], $watermark['height']);
    }

    if ($arguments['position'] == 'center') {
      $margin_x = ($image['width'] / 2) - ($watermark['width'] / 2);
      $margin_y = ($image['height'] / 2) - ($watermark['height'] / 2);
      // Reinforce that position center applies only with 'once' apply type.
    }

    $temp_resource = $this->getToolkit()->getResource();
    switch ($arguments['apply_type']) {
      case 'repeat':
        $start_x = $margin_x;
        for ($i = 0; $i < ($image['width'] % $watermark['width']) + 1; $i++) {
          $start_y = $margin_y;
          for ($j = 0; $j < ($image['height'] % $watermark['height']) + 1; $j++) {
            $resource = imagecopy($temp_resource, $watermark_img, $start_x, $start_y, 0, 0, min($watermark['width'], $image['width'] - $start_x), min($watermark['height'], $image['height'] - $start_y));

            // If at any point the image copy fails fail the operation.
            if (!$resource) {
              $this->getToolkit()->setResource($image_resource);
              return FALSE;
            }
            $start_y += $margin_y + $watermark['height'];
          }
          $start_x += $margin_x + $watermark['width'];
        }
        break;

      case 'once':
        $resource = imagecopy($temp_resource, $watermark_img, $margin_x, $margin_y, 0, 0, $watermark['width'], $watermark['height']);
        if (!$resource) {
          $this->getToolkit()->setResource($image_resource);
          return FALSE;
        }
        break;

      default:
        return FALSE;
    }

    $this->getToolkit()->setResource(TRUE);
    imagedestroy($image_resource);
    return TRUE;
  }

}
