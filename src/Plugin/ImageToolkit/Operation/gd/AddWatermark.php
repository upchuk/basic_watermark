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
    $original_resource = $this->getToolkit()->getResource();
    $resource = $this->getToolkit()->getResource();

    $watermark_filepath = DRUPAL_ROOT . $arguments['watermark_path'];
    $watermark_img = imagecreatefrompng($watermark_filepath);

    $main_width = imagesx($original_resource);
    $main_height = imagesy($original_resource);
    $main_ration = $main_width / $main_height;

    $watermark_width = imagesx($watermark_img);
    $watermark_height = imagesy($watermark_img);
    $watermark_ration = $watermark_width / $watermark_height;

    $margin_x = $arguments['margin_x'];
    $margin_y = $arguments['margin_y'];

    // Scale Watermark to fit on image.
    $new_width = $watermark_width;
    $new_height = $watermark_height;
    if ($watermark_width + $margin_x + $margin_x > $main_width) {
      $new_width = $main_width - $margin_x - $margin_x;
      $watermark_img = imagescale($watermark_img, $new_width);
      $new_height = imagesy($watermark_img);
    }

    // Copy watermark on image.
    $resource = imagecopy($this->getToolkit()->getResource(), $watermark_img, $margin_x, $margin_y, 0, 0, $new_width, $new_height);
    if (!$resource) {
      $this->getToolkit()->setResource($original_resource);
      return FALSE;
    }

    $this->getToolkit()->setResource(TRUE);
    imagedestroy($original_resource);
    return TRUE;
  }

}
