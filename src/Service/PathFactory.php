<?php

namespace App\Service;

use App\Entity\Path;

/**
 * Creates new Path instances
 *
 * Takes care of getting the app.upload_dir config parameter and injecting it into the Path.
 */
class PathFactory {

    private string $uploadDir;

    public function __construct(string $uploadDir) {
        $this->uploadDir = $uploadDir;
    }

    public function newInstance(): Path {
        return new Path($this->uploadDir);
    }
}
