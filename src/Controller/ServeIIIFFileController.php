<?php

namespace Drupal\serve_iiif_file\Controller;

use Drupal\node\NodeInterface;
use Drupal\media\Entity\Media;
use Drupal\taxonomy\Entity\Term;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 */
class ServeIIIFFileController {

  /**
   *
   */
  public function content(NodeInterface $book_node) {

    $file_contents = "{}";

    try {

      $media_info = $book_node->get('field_islandora_object_media')->getValue();

      // Find the media which has the manifest file.
      $manifest_media_id = "None";
      foreach ($media_info as $media_item) {
        $media_id = $media_item["target_id"];
        $media = Media::load($media_id);
        $media_use = $media->get('field_media_use')->getValue();

        foreach ($media_use as $media_user_term_id) {
          $term_id = $media_user_term_id["target_id"];
          $term = Term::load($term_id);
          $term_name = $term->label();

          if ($term_name == "IIIF Manifest") {
            $manifest_media_id = $media_id;
            break;
          }
        }

        if ($manifest_media_id != "None") {
          break;
        }
      }

      if ($manifest_media_id != "None") {
        $file_id = $media->get('field_media_file')->target_id;
        $file = File::load($file_id);

        $file_uri = $file->getFileUri();
        $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager')->getViaUri($file_uri);
        $file_path = $stream_wrapper_manager->realpath();

        $file_contents = file_get_contents($file_path);
      }

    }
    catch (Exception $e) {
      \Drupal::logger('serve_iiif_file')->notice("Unable to get IIIF Manifest file");
    }

    $response = new Response();
    $response->setContent($file_contents);
    $response->headers->set('Content-Type', "application/json");

    return $response;
  }

}
