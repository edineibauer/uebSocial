<?php

use InstagramAPI\Constants;
use InstagramAPI\Instagram;
use InstagramAPI\Media\Photo\InstagramPhoto;
use InstagramAPI\Media\Photo\PhotoDetails;
use InstagramAPI\Media\Video\InstagramVideo;
use InstagramAPI\Media\Video\VideoDetails;
use WideImage\WideImage;

if (defined('INSTAGRAM_USER') && defined('INSTAGRAM_PASS') && !empty(INSTAGRAM_USER) && !empty(INSTAGRAM_PASS)) {
    set_time_limit(0);
    date_default_timezone_set('UTC');

    /////// CONFIG ///////
    $username = INSTAGRAM_USER;
    $password = INSTAGRAM_PASS;
    $debug = !0;
    $truncatedDebug = !0;
    Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;
    \InstagramAPI\Utils::$ffmpegBin = 'D:/wamp64/bin/ffmpeg';
    \InstagramAPI\Utils::$ffprobeBin = 'D:/wamp64/bin/ffprobe';

    //////// LOGIN INSTAGRAM //////////////
    $ig = new Instagram($debug, $truncatedDebug);
    try {
        $ig->login($username, $password);
    } catch (\Exception $e) {
        exit(0);
    }

    $midias = json_decode($dados['midias'], !0);

    if (count($midias) === 1) {

        /**
         * Upload Single
         */

        try {
            if (preg_match('/^image\//i', $midias[0]['midia'][0]['fileType'])) {

                /**
                 * Upload Single Photo
                 */
                $name = PATH_HOME . "uploads/tmp/{$midias[0]['midia'][0]['name']}.jpg";
                WideImage::load($midias[0]['midia'][0]['url'])->saveToFile($name);
                $photo = new InstagramPhoto($name);
                $ig->timeline->uploadPhoto($photo->getFile(), ['caption' => $midias[0]['texto']]);

            } elseif (preg_match('/^video\//i', $midias[0]['midia'][0]['fileType'])) {

                /**
                 * Upload Single Video
                 */
                $video = new InstagramVideo(str_replace(HOME, PATH_HOME, $midias[0]['midia'][0]['url']));
                $ig->timeline->uploadVideo($video->getFile(), ['caption' => $midias[0]['texto']]);
            }

        } catch (\Exception $e) {
            exit(0);
        }

    } elseif (count($midias) > 1) {

        /**
         * Upload Album
         */

        $mediaOptions = [
                'targetFeed' => Constants::FEED_TIMELINE_ALBUM,
            // Uncomment to expand media instead of cropping it.
            //'operation' => \InstagramAPI\Media\InstagramMedia::EXPAND,
        ];

        /////// MEDIAS ////////
        $media = [];
        foreach ($midias as $item) {

            $mid = [];

            /** @var \InstagramAPI\Media\InstagramMedia|null $validMedia */
            $validMedia = null;
            $mid['type'] = preg_match('/^image\//i', $item['midia'][0]['fileType']) ? "photo" : "video";

            if ($mid['type'] === 'photo') {
                // convert webp to jpg
                $name = PATH_HOME . "uploads/tmp/{$item['midia'][0]['name']}.jpg";
                WideImage::load($item['midia'][0]['url'])->saveToFile($name);
                $validMedia = new InstagramPhoto($name, $mediaOptions);
            } else {
                $validMedia = new InstagramVideo(str_replace(HOME, PATH_HOME, $item['midia'][0]['url']), $mediaOptions);
            }
            if ($validMedia === null)
                continue;

            try {
                $mid['file'] = $validMedia->getFile();
                // We must prevent the InstagramMedia object from destructing too early,
                // because the media class auto-deletes the processed file during their
                // destructor's cleanup (so we wouldn't be able to upload those files).
                $mid['__media'] = $validMedia; // Save object in an unused array key.
            } catch (\Exception $e) {
                continue;
            }

            if (!isset($mediaOptions['forceAspectRatio'])) {
                // Use the first media file's aspect ratio for all subsequent files.
                /** @var \InstagramAPI\Media\MediaDetails $mediaDetails */
                $mediaDetails = $validMedia instanceof InstagramPhoto ? new PhotoDetails($mid['file']) : new VideoDetails($mid['file']);
                $mediaOptions['forceAspectRatio'] = $mediaDetails->getAspectRatio();
            }

            $media[] = $mid;
        }

        /////////// UPLOAD ALBUM //////////////////
        try {
            $ig->timeline->uploadAlbum($media, ['caption' => $midias[0]['texto']]);
        } catch (\Exception $e) {
            exit(0);
        }
    }
}