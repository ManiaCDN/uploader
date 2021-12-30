<?php

namespace App\Uploader;

use Oneup\UploaderBundle\Uploader\Response\EmptyResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Why is this necessary?
 * On the production server, files are stored on a storage that is mounted over the network.
 * In that situation, rename() always returns false, even if the file was successfully moved (rename()d).
 * Fixing this properly would have probably required to transition from direct filesystem access to
 * an abstraction like Flysystem, which would then access the remote storage directly, circumventing the use of rename().
 *
 * This workaround just catches the Exception which happens due to the failing rename and ignores it.
 */
class DropzoneController extends \Oneup\UploaderBundle\Controller\DropzoneController {

    public function upload(): JsonResponse {
        try {
            return parent::upload();
        } catch (FileException $e) {

            if ( ! (
                    str_contains($e->getMessage(), 'Could not move the file') &&
                    str_contains($e->getMessage(), 'Permission denied')
                )
            ) {
                $response = new EmptyResponse();
                $this->errorHandler->addException($response, $e);

                $translator = $this->container->get('translator');
                $message = $translator->trans($e->getMessage(), [], 'OneupUploaderBundle');
                $response = $this->createSupportedJsonResponse(['error' => $message]);
                $response->setStatusCode(400);

                return $response;
            }
        }
        return new JsonResponse(['error' => 'An unknown error occurred.']);
    }
}
