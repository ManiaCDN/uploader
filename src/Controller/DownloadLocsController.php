<?php

namespace App\Controller;

use App\Entity\Path;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

class DownloadLocsController extends AbstractController
{
    /**
     * Create a zip archive of .loc files used by Maniaplanet and Trackmania.
     * Requires GET parameter 'path' that gives the root of the zip that
     * should be created, relative to UPLOAD_DIR.
     *
     * @return StreamedResponse
     */
    public function download(Request $request): Response
    {
        $path = new Path();
        $path->fromString($request->query->get('path', '.'));

        // get a list of files we need to create .loc files for
        // todo
        $finder = new Finder();
        $list = $finder
            ->files() // look for files only, exclude directories
            ->in($path->getAbsolutePath());

        $response = new StreamedResponse(function() use ($list, $path) {
            $options = new Archive();

            $options->setContentType('application/octet-stream');
            $options->setZeroHeader(true); // this is needed to prevent issues with truncated zip files
            $options->setSendHttpHeaders(true); // let zipstream set the headers
            $options->setEnableZip64(false); // according to zipstream readme, zip64 can cause issues on MacOS and we don't need it

            $zip = new ZipStream('locators.zip', $options);

            foreach ($list as $file) {
                // to the path from the url (indicating the root of th archive)
                // we add the relative paths from there to each file that's
                // going into the archive
                $filepath = $path->append($file->getRelativePathname(), true);

                // for each file we find, we create a new .loc file with the same name.
                // the content is the file's public URL
                $zip->addFile($filepath->getString().'.loc', $filepath->getPublicURL());
            }

            $zip->finish();
        });
        return $response;
    }
}
