<?php

if(! class_exists("Photo")) {
class Photo {

    private $image;
    private $ext;

    /**
     * supported mimetypes and corresponding file extensions
     */
    static function supportedTypes() {
        /**
         * Imagick::queryFormats won't help us a lot there...
         * At least, not yet, other parts of friendica uses this array
         */
        $t = array(
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif'
        );
        return $t;
    }

    public function __construct($data, $type=null) {
        $this->image = new Imagick();
        $this->image->readImageBlob($data);

        // If it is a gif, it may be animated, get it ready for any future operations
        if($this->image->getFormat() !== "GIF") $this->image = $this->image->coalesceImages();

        $this->ext = strtolower($this->image->getImageFormat());
    }

    public function __destruct() {
        if($this->image) {
            $this->image->clear();
            $this->image->destroy();
        }
    }

    public function is_valid() {
        return ($this->image !== FALSE);
    }

    public function getWidth() {
        if(!$this->is_valid())
            return FALSE;

        return $this->image->getImageWidth();
    }

    public function getHeight() {
        if(!$this->is_valid())
            return FALSE;

        return $this->image->getImageHeight();
    }

    public function getImage() {
        if(!$this->is_valid())
            return FALSE;

        /* Clean it */
        $this->image = $this->image->deconstructImages();
        return $this->image;
    }

    public function getType() {
        if(!$this->is_valid())
            return FALSE;

        // This should do the trick (see supportedTypes above)
        return 'image/'. $this->getExt();
    }

    public function getExt() {
        if(!$this->is_valid())
            return FALSE;

        return $this->ext;
    }

    public function scaleImage($max) {
        if(!$this->is_valid())
            return FALSE;

        /**
         * If it is not animated, there will be only one iteration here,
         * so don't bother checking
         */
        // Don't forget to go back to the first frame for any further operation
        $this->image->setFirstIterator();
        do {
            $this->image->resizeImage($max, $max, imagick::FILTER_LANCZOS, 1, true);
        } while ($this->image->nextImage());
    }

    public function rotate($degrees) {
        if(!$this->is_valid())
            return FALSE;

        $this->image->setFirstIterator();
        do {
            $this->image->rotateImage(new ImagickPixel(), $degrees);
        } while ($this->image->nextImage());
    }

    public function flip($horiz = true, $vert = false) {
        if(!$this->is_valid())
            return FALSE;

        $this->image->setFirstIterator();
        do {
            if($horiz) $this->image->flipImage();
            if($vert) $this->image->flopImage();
        } while ($this->image->nextImage());
    }

    public function orient($filename) {
        // based off comment on http://php.net/manual/en/function.imagerotate.php

        if(!$this->is_valid())
            return FALSE;

        if( (! function_exists('exif_read_data')) || ($this->getType() !== 'image/jpeg') )
            return;

        $exif = exif_read_data($filename);
        $ort = $exif['Orientation'];

        switch($ort)
        {
            case 1: // nothing
                break;

            case 2: // horizontal flip
                $this->flip();
                break;

            case 3: // 180 rotate left
                $this->rotate(180);
                break;

            case 4: // vertical flip
                $this->flip(false, true);
                break;

            case 5: // vertical flip + 90 rotate right
                $this->flip(false, true);
                $this->rotate(-90);
                break;

            case 6: // 90 rotate right
                $this->rotate(-90);
                break;

            case 7: // horizontal flip + 90 rotate right
                $this->flip();
                $this->rotate(-90);
                break;

            case 8:    // 90 rotate left
                $this->rotate(90);
                break;
        }
    }



    public function scaleImageUp($min) {
        if(!$this->is_valid())
            return FALSE;

        $this->scaleImage($min);
    }



    public function scaleImageSquare($dim) {
        if(!$this->is_valid())
            return FALSE;

        $this->image->setFirstIterator();
        do {
            $this->image->resizeImage($max, $max, imagick::FILTER_LANCZOS, 1, false);
        } while ($this->image->nextImage());
    }


    public function cropImage($max,$x,$y,$w,$h) {
        if(!$this->is_valid())
            return FALSE;

        $this->image->setFirstIterator();
        do {
            $this->image->cropImage($w, $h, $x, $y);
            /**
             * We need to remove the canva,
             * or the image is not resized to the crop:
             * http://php.net/manual/en/imagick.cropimage.php#97232
             */
            $this->image->setImagePage(0, 0, 0, 0);
        } while ($this->image->nextImage());
        $this->scaleImage($max);
    }

    public function saveImage($path) {
        if(!$this->is_valid())
            return FALSE;

        $string = $this->imageString();
        file_put_contents($path, $string);
    }

    public function imageString() {
        if(!$this->is_valid())
            return FALSE;

        $quality = FALSE;

        /**
         * Hmmm,
         * we should do the conversion/compression at the initialisation i think
         * This method may be called several times,
         * and there is no need to do that more than once
         */
        switch($this->image->getImageFormat()){
            case "PNG":
                $quality = get_config('system','png_quality');
                if((! $quality) || ($quality > 9))
                    $quality = PNG_QUALITY;
                /**
                 * From http://www.imagemagick.org/script/command-line-options.php#quality:
                 *
                 * 'For the MNG and PNG image formats, the quality value sets
                 * the zlib compression level (quality / 10) and filter-type (quality % 10).
                 * The default PNG "quality" is 75, which means compression level 7 with adaptive PNG filtering,
                 * unless the image has a color map, in which case it means compression level 7 with no PNG filtering'
                 */
                $quality = $quality * 10;
                break;
            case "GIF":
                // We change nothing here, do we?
                break;
            default:
                // Convert to jpeg by default
                $quality = get_config('system','jpeg_quality');
                if((! $quality) || ($quality > 100))
                    $quality = JPEG_QUALITY;
                $this->image->setImageFormat('jpeg');
        }

        if($quality !== FALSE) {
            // Do we need to iterate for animations?
            $this->image->setImageCompressionQuality($quality);
            $this->image->stripImage();
        }

        $string = $this->image->getImagesBlob();
        return $string;
    }



    public function store($uid, $cid, $rid, $filename, $album, $scale, $profile = 0, $allow_cid = '', $allow_gid = '', $deny_cid = '', $deny_gid = '') {

        $r = q("select `guid` from photo where `resource-id` = '%s' and `guid` != '' limit 1",
            dbesc($rid)
        );
        if(count($r))
            $guid = $r[0]['guid'];
        else
            $guid = get_guid();

        $x = q("select id from photo where `resource-id` = '%s' and uid = %d and `contact-id` = %d and `scale` = %d limit 1",
                dbesc($rid),
                intval($uid),
                intval($cid),
                intval($scale)
        );
        if(count($x)) {
            $r = q("UPDATE `photo`
                set `uid` = %d,
                `contact-id` = %d,
                `guid` = '%s',
                `resource-id` = '%s',
                `created` = '%s',
                `edited` = '%s',
                `filename` = '%s',
                `type` = '%s',
                `album` = '%s',
                `height` = %d,
                `width` = %d,
                `data` = '%s',
                `scale` = %d,
                `profile` = %d,
                `allow_cid` = '%s',
                `allow_gid` = '%s',
                `deny_cid` = '%s',
                `deny_gid` = '%s'
                where id = %d limit 1",

                intval($uid),
                intval($cid),
                dbesc($guid),
                dbesc($rid),
                dbesc(datetime_convert()),
                dbesc(datetime_convert()),
                dbesc(basename($filename)),
                dbesc($this->getType()),
                dbesc($album),
                intval($this->getHeight()),
                intval($this->getWidth()),
                dbesc($this->imageString()),
                intval($scale),
                intval($profile),
                dbesc($allow_cid),
                dbesc($allow_gid),
                dbesc($deny_cid),
                dbesc($deny_gid),
                intval($x[0]['id'])
            );
        }
        else {
            $r = q("INSERT INTO `photo`
                ( `uid`, `contact-id`, `guid`, `resource-id`, `created`, `edited`, `filename`, type, `album`, `height`, `width`, `data`, `scale`, `profile`, `allow_cid`, `allow_gid`, `deny_cid`, `deny_gid` )
                VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', %d, %d, '%s', '%s', '%s', '%s' )",
                intval($uid),
                intval($cid),
                dbesc($guid),
                dbesc($rid),
                dbesc(datetime_convert()),
                dbesc(datetime_convert()),
                dbesc(basename($filename)),
                dbesc($this->getType()),
                dbesc($album),
                intval($this->getHeight()),
                intval($this->getWidth()),
                dbesc($this->imageString()),
                intval($scale),
                intval($profile),
                dbesc($allow_cid),
                dbesc($allow_gid),
                dbesc($deny_cid),
                dbesc($deny_gid)
            );
        }
        return $r;
    }
}}


/**
 * Guess image mimetype from filename or from Content-Type header
 *
 * @arg $filename string Image filename
 * @arg $fromcurl boolean Check Content-Type header from curl request
 */
function guess_image_type($filename, $fromcurl=false) {
    logger('Photo: guess_image_type: '.$filename . ($fromcurl?' from curl headers':''), LOGGER_DEBUG);
    $type = null;
    if ($fromcurl) {
        $a = get_app();
        $headers=array();
        $h = explode("\n",$a->get_curl_headers());
        foreach ($h as $l) {
            list($k,$v) = array_map("trim", explode(":", trim($l), 2));
            $headers[$k] = $v;
        }
        if (array_key_exists('Content-Type', $headers))
            $type = $headers['Content-Type'];
    }
    if (is_null($type)){
        // Guessing from extension? Isn't that... dangerous?
        /*$ext = pathinfo($filename, PATHINFO_EXTENSION);
        $types = Photo::supportedTypes();
        $type = "image/jpeg";
        foreach ($types as $m=>$e){
            if ($ext==$e) $type = $m;
        }*/
        /**
         * Well, this not much better,
         * but at least it comes from the data inside the image,
         * we won't be tricked by a manipulated extension
         */
        $image = new Imagick($filename);
        $type = 'image/'. strtolower($image->getImageFormat());
    }
    logger('Photo: guess_image_type: type='.$type, LOGGER_DEBUG);
    return $type;

}

function import_profile_photo($photo,$uid,$cid) {

    $a = get_app();

    $r = q("select `resource-id` from photo where `uid` = %d and `contact-id` = %d and `scale` = 4 and `album` = 'Contact Photos' limit 1",
        intval($uid),
        intval($cid)
    );
    if(count($r)) {
        $hash = $r[0]['resource-id'];
    }
    else {
        $hash = photo_new_resource();
    }

    $photo_failure = false;

    $filename = basename($photo);
    $img_str = fetch_url($photo,true);

    $img = new Photo($img_str);
    if($img->is_valid()) {

        $img->scaleImageSquare(175);

        $r = $img->store($uid, $cid, $hash, $filename, 'Contact Photos', 4 );

        if($r === false)
            $photo_failure = true;

        $img->scaleImage(80);

        $r = $img->store($uid, $cid, $hash, $filename, 'Contact Photos', 5 );

        if($r === false)
            $photo_failure = true;

        $img->scaleImage(48);

        $r = $img->store($uid, $cid, $hash, $filename, 'Contact Photos', 6 );

        if($r === false)
            $photo_failure = true;

        $photo = $a->get_baseurl() . '/photo/' . $hash . '-4.' . $img->getExt();
        $thumb = $a->get_baseurl() . '/photo/' . $hash . '-5.' . $img->getExt();
        $micro = $a->get_baseurl() . '/photo/' . $hash . '-6.' . $img->getExt();
    }
    else
        $photo_failure = true;

    if($photo_failure) {
        $photo = $a->get_baseurl() . '/images/person-175.jpg';
        $thumb = $a->get_baseurl() . '/images/person-80.jpg';
        $micro = $a->get_baseurl() . '/images/person-48.jpg';
    }

    return(array($photo,$thumb,$micro));

}
