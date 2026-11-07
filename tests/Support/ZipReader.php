<?php

namespace App\Tests\Support;

/**
 * Minimal read-only ZIP reader for tests.
 *
 * The project does not require ext-zip, so this parses the central directory
 * and inflates entries with zlib's gzinflate() (raw deflate, as produced by
 * maennchen/zipstream-php). Only supports archives without Zip64.
 */
class ZipReader
{
    /**
     * Extracts all entries of a zip archive given as binary string.
     *
     * @return array<string, string> filename => uncompressed contents
     */
    public static function extract(string $zip): array
    {
        $eocdPosition = strrpos($zip, "PK\x05\x06");
        if (false === $eocdPosition) {
            throw new \RuntimeException('Not a zip archive: end of central directory record not found.');
        }

        $entryCount = unpack('v', substr($zip, $eocdPosition + 10, 2))[1];
        $cdOffset   = unpack('V', substr($zip, $eocdPosition + 16, 4))[1];

        $entries = [];
        $position = $cdOffset;

        for ($i = 0; $i < $entryCount; $i++) {
            $method     = unpack('v', substr($zip, $position + 10, 2))[1];
            $size       = unpack('V', substr($zip, $position + 20, 4))[1];
            $nameLength = unpack('v', substr($zip, $position + 28, 2))[1];
            $extraLength = unpack('v', substr($zip, $position + 30, 2))[1];
            $commentLength = unpack('v', substr($zip, $position + 32, 2))[1];
            $localOffset = unpack('V', substr($zip, $position + 42, 4))[1];
            $name = substr($zip, $position + 46, $nameLength);

            // local file header holds the data offset; sizes may be zero there
            // because zipstream writes a data descriptor instead
            $localNameLength = unpack('v', substr($zip, $localOffset + 26, 2))[1];
            $localExtraLength = unpack('v', substr($zip, $localOffset + 28, 2))[1];
            $data = substr($zip, $localOffset + 30 + $localNameLength + $localExtraLength, $size);

            $entries[$name] = 8 === $method ? gzinflate($data) : $data;

            $position += 46 + $nameLength + $extraLength + $commentLength;
        }

        return $entries;
    }
}
