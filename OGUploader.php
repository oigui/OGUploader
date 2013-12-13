<?php

/*
 * OGUploader
 * 
 */

class OGUploader {
    private $cfg;
    private $ext; //extension
    /*
     * Configuration
     */
    public function __construct($config) {
        $this->cfg = $config + array(
            'destination'   => '/uploads',
            'maxSize'       => 1024 * 1024 * 2, // 2 MB
            'extensions'    => array('pdf','doc','docx','txt','rtf','gif','jpg','jpeg','png'), //lowercase
            'mimes'         => false, //array of strings.
            'uniqueName'    => true,
            'imgMaxWidth'   => 400,
            'imgMaxHeight'  => 400,
            'jpegQuality'   => 80,
            'thumb'         => true,
            'thumbSize'     => 200, //Square
            'thumbFolder'   => 'thumbs',
        );
    }
    /*
     * Save a file.
     */
    public function save($file){
        if(isset($file)){
            $this->checkError($file['error']);
            $this->checkSize($file['size']);
            $this->checkExtension($file['name'], $file['type']);
            
            if($this->cfg['uniqueName']){
                $newFileName = sha1($this->cfg['destination'] . mt_rand(1000, 9999) . uniqid()) . time() . '.' . $this->ext;
            } else {
                $newFileName = $file['name'];
            }
            
            $imgInfo = getimagesize($file['tmp_name']);
            $imgResized = false;
            if($imgInfo){
                //file is a image.
                $imgWidth  = $imgInfo[0];
                $imgHeight = $imgInfo[1];
                $imgType   = $imgInfo[2];
                
                if (($this->cfg['imgMaxWidth'] && $this->cfg['imgMaxWidth'] < $imgWidth) ||
                    ($this->cfg['imgMaxHeight'] && $this->cfg['imgMaxHeight'] < $imgHeight)) {
                    //Resize it if is need.
                    
                    if ($this->cfg['imgMaxWidth'] && !$this->cfg['imgMaxHeight']) { 
                        //Only Width
                        $newWidth  = (int) $this->cfg['imgMaxWidth'];
                        $newHeight = (int) ($imgHeight * ($newWidth/$imgWidth));
                        
                    } else if ($this->cfg['imgMaxHeight'] && !$this->cfg['imgMaxWidth']) { 
                        //Only Height
                        $newHeight = (int) $this->cfg['imgMaxHeight'];
                        $newWidth  = (int) ($imgWidth * ($newHeight/$imgHeight));
                        
                    } else { 
                        //Both
                        if($imgWidth > $imgHeight){
                            $newWidth  = (int) $this->cfg['imgMaxWidth'];
                            $newHeight = (int) ($imgHeight * ($newWidth/$imgWidth));
                        } else {
                            $newHeight = (int) $this->cfg['imgMaxHeight'];
                            $newWidth  = (int) ($imgWidth * ($newHeight/$imgHeight));
                        }
                    }
                     
                    if( $imgType == IMAGETYPE_JPEG ) {   
                        $image = imagecreatefromjpeg($file['tmp_name']); 
                    } elseif( $imgType == IMAGETYPE_GIF ) {   
                        $image = imagecreatefromgif($file['tmp_name']);
                    } elseif( $imgType == IMAGETYPE_PNG ) {   
                        $image = imagecreatefrompng($file['tmp_name']);                     
                    }
                    
                    $newImg = imagecreatetruecolor($newWidth, $newHeight); 
                    if( $imgType == IMAGETYPE_GIF || $imgType == IMAGETYPE_PNG ) {
                        $transparent = imagecolortransparent($image); 
                        if($transparent != -1) {
                            $transparentColor = imagecolorsforindex($image, $transparent); 
                            $transparent = imagecolorallocate($newImg, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']); 
                            imagefill($newImg, 0, 0, $transparent); imagecolortransparent($newImg, $transparent);                             
                        } elseif( $imgType == IMAGETYPE_PNG) {
                            imagealphablending($newImg, false); 
                            $color = imagecolorallocatealpha($newImg, 0, 0, 0, 127); 
                            imagefill($newImg, 0, 0, $color); 
                            imagesavealpha($newImg, true); 
                        }
                    }                    
                    imagecopyresampled($newImg, $image, 0, 0, 0, 0, $newWidth, $newHeight, $imgWidth, $imgHeight);                     
                    if( $imgType == IMAGETYPE_JPEG ) { 
                       $moved = imagejpeg($newImg,$this->getDest().'/'.$newFileName, $this->cfg['jpegQuality']); 
                        
                    } elseif( $imgType == IMAGETYPE_GIF ) {
                       $moved = imagegif($newImg,$this->getDest().'/'.$newFileName); 
                        
                    } elseif( $imgType == IMAGETYPE_PNG ) {   
                       $moved = imagepng($newImg,$this->getDest().'/'.$newFileName);
                    }                    
                    $imgResized = true;
                    if(!$moved){
                        throw new Exception("Not saved");
                    }
                }                
                $this->createThumb($file, $imgInfo, $newFileName);
            }
            if(!$imgResized){
                $moved = move_uploaded_file($file['tmp_name'], $this->getDest().'/'.$newFileName);            
                if(!$moved){
                    throw new Exception("Not saved");
                }                
            }
        }
        unlink($file['tmp_name']);
        
        return $newFileName;
    }
    
    /*
     * Create Thumbnail if file is a image and thumb parameter is true.
     */
    private function createThumb($file, $imgInfo, $newFileName){
        if($this->cfg['thumb']){ 
            
            $tSize = $this->cfg['thumbSize'];
            $imgWidth  = $imgInfo[0];
            $imgHeight = $imgInfo[1];
            $imgType   = $imgInfo[2];
            
            if( $imgType == IMAGETYPE_JPEG ) {
                $image = imagecreatefromjpeg($file['tmp_name']); 
            } elseif( $imgType == IMAGETYPE_GIF ) {   
                $image = imagecreatefromgif($file['tmp_name']);
            } elseif( $imgType == IMAGETYPE_PNG ) {   
                $image = imagecreatefrompng($file['tmp_name']);                     
            }

            $newImg = imagecreatetruecolor($tSize, $tSize); 
            if( $imgType == IMAGETYPE_GIF || $imgType == IMAGETYPE_PNG ) {
                $transparent = imagecolortransparent($image); 
                if($transparent != -1) {
                    $transparentColor = imagecolorsforindex($image, $transparent); 
                    $transparent = imagecolorallocate($newImg, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']); 
                    imagefill($newImg, 0, 0, $transparent); imagecolortransparent($newImg, $transparent);                             
                } elseif( $imgType == IMAGETYPE_PNG) {
                    imagealphablending($newImg, false); 
                    $color = imagecolorallocatealpha($newImg, 0, 0, 0, 127); 
                    imagefill($newImg, 0, 0, $color); 
                    imagesavealpha($newImg, true); 
                }
            }
            
            //Create Thumbnails Proportional and Centralized
            if($imgWidth > $imgHeight){
                $partX = ($imgWidth - $imgHeight)/2;
                $partY = 0;                        
                $imgWidth = $imgHeight;
            } else {
                $partX = 0;
                $partY = ($imgHeight - $imgWidth)/2;
                $imgHeight = $imgWidth;
            }            
                        
            imagecopyresampled($newImg, $image, 0, 0, $partX, $partY, $tSize, $tSize, $imgWidth, $imgHeight);                     
            if( $imgType == IMAGETYPE_JPEG ) { 
               $moved = imagejpeg($newImg,$this->getThumbDest().'/'.$newFileName, $this->cfg['jpegQuality']); 

            } elseif( $imgType == IMAGETYPE_GIF ) {
               $moved = imagegif($newImg,$this->getThumbDest().'/'.$newFileName); 

            } elseif( $imgType == IMAGETYPE_PNG ) {   
               $moved = imagepng($newImg,$this->getThumbDest().'/'.$newFileName);
            }
            if(!$moved){
                throw new Exception("Thumbnail not saved");
            }   
        }
    }
    
    /*
     * Return the Destination.
     * Create if this doesn't exists.
     */
    private function getDest(){
        $dest = dirname(__FILE__).$this->cfg['destination'];
        if(!is_writable($dest)){
            mkdir($dest);
        }
        return $dest;
    }
    
    /*
     * Return the Thumbnails Destination.
     * Create if this doesn't exists.
     */
    private function getThumbDest(){
        $tfoder = $this->getDest().'/'.$this->cfg['thumbFolder'];
        if(!is_writable($tfoder)){
            mkdir($tfoder);
        }
        return $tfoder;
    }
    
    /*
     * Checks the file extension.
     */
    private function checkExtension($fileName, $fileMime){
        $fileParts = explode(".", $fileName);
        $this->ext = $extension = strtolower(end($fileParts));
        $errorMsg = 'The uploaded file extension is not allowed';
        
        if($this->cfg['extensions']){
            if(!in_array($extension, $this->cfg['extensions'])){
                throw new Exception($errorMsg);            
            }
        }
        
        if($this->cfg['mimes']){
            if (!in_array($fileMime, $this->cfg['mimes'])) {
                throw new Exception($errorMsg);
            }   
        }
    }
    /*
     * Checks the size.
     */
    private function checkSize($size){ 
        if($size > $this->cfg['maxSize']){
            throw new Exception('The uploaded file exceeds the maxSize parameter');
        }
    }
    
    /*
     * Checks the error code.
     */
    private function checkError($errorCode){
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE: 
                $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini"; 
                break; 
            case UPLOAD_ERR_FORM_SIZE: 
                $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form"; 
                break; 
            case UPLOAD_ERR_PARTIAL: 
                $message = "The uploaded file was only partially uploaded"; 
                break; 
            case UPLOAD_ERR_NO_FILE: 
                $message = "No file was uploaded"; 
                break; 
            case UPLOAD_ERR_NO_TMP_DIR: 
                $message = "Missing a temporary folder"; 
                break; 
            case UPLOAD_ERR_CANT_WRITE: 
                $message = "Failed to write file to disk"; 
                break; 
            case UPLOAD_ERR_EXTENSION: 
                $message = "File upload stopped by extension"; 
                break; 
            default: 
                $message = "Unknown upload error"; 
                break; 
        }
        if($errorCode != UPLOAD_ERR_OK){
            throw new Exception($message);
        }
    }
}
