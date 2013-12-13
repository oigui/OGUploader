OGUploader - File/Image Uploader
=============================================

### **How to use**

```php
<?php
    include_once './OGUploader.php';
    if(isset($_POST['send']) && isset($_FILES['archive'])){
        $archive = $_FILES['archive'];
        
        //Default Config Array. 
        $config = array(
            'destination'   => '/uploads', //creates a folder in the same place of this file.
            'maxSize'       => 1024 * 1024 * 2, // 2 MB
            'extensions'    => array('pdf','doc','docx','txt','rtf','gif','jpg','jpeg','png'), //lowercase
            'mimes'         => false, //array of strings.
            'uniqueName'    => true,
            'imgMaxWidth'   => 400, //false to ignore
            'imgMaxHeight'  => 400, //false to ignore
            'jpegQuality'   => 80,
            'thumb'         => true,
            'thumbSize'     => 200, //Square
            'thumbFolder'   => 'thumbs',
        );
        
        $OGUp = new OGUploader($config);
        
        //throw exceptions on errors/validations
        try{            
            $fileName = $OGUp->save($archive);
            var_dump($fileName);            
        }catch(Exception $e){
            var_dump($e->getMessage());
        }
        
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <title>OGUploader</title>
    </head>
    <body>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="file" name="archive" />
            <input type="submit" name="send" value="Send It!" />
        </form>
    </body>
</html>
```

---
