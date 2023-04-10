<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use File;
use App\Validator\Product as Validator;

class ProductGallery extends Model
{
    use Validator;

    const IMAGE_PATH = '/public/uploads/products/web/';
    const IMAGE_MOBILE_PATH = '/public/uploads/products/mobile/';

    protected $primaryKey = "product_gallery_id";
    protected $table = "product_gallery";
    protected $fillable = ['product_id', 'image_path', 'status','web_path'];
    protected $attributes = ["status" => 1, 'is_default'=> 0 ];
    public $timestamps = true;

    /*function __construct($attributes = []){
        if(!iiset($this->attributes["updated_at"])){
            $this->attributes['updated_at'] = date("Y-m-d H:i:s");
        }
        parent::__construct($attributes);
    }*/

////////////////////////////////////////////////////////////////////////////////////

    function parent(){
        return $this->belongsTo('App\Model\Product', 'product_id');
    }

    function product(){
        return $this->belongsTo('App\Model\Product', 'product_id');
    }

    function getProductImages($product_id){
      $url = asset('');
      $url = rtrim($url,'/');

        $images = [];
        $productImages = static::where("product_id", $product_id)->get()->toArray();
        for($i=0, $count = count($productImages); $i < $count; $i++){
            $images[] = $url . $productImages[$i]['image_path'];
            // $images[] = url('/') . $productImages[$i]['image_path'];
        }
        return $images;
    }

    function getProductImagesWeb($product_id){
      $url = asset('');
      $url = rtrim($url,'/');

        $images = [];
        $productImages = static::where("product_id", $product_id)->get()->toArray();
        for($i=0, $count = count($productImages); $i < $count; $i++){
            $images[] = $url . $productImages[$i]['web_path'];
            // $images[] = url('/') . $productImages[$i]['image_path'];
        }
        return $images;
    }

    function upload($data, $file, $mobile,$updated_file){
        // dd($file->getClientOriginalName());

    // $imageName = time().'.'.$request->image->getClientOriginalExtension();
    // $image = $request->file('image');
    // $t = Storage::disk('s3')->put($imageName, file_get_contents($image), 'public');
    // $imageName = Storage::disk('s3')->url($imageName);

        $json = [];

        // $name = $file->getClientOriginalName();
        // $mob_name = $mobile->getClientOriginalName();

        // $name = str_replace('.' . $file->getClientOriginalExtension(), '', $file->getClientOriginalName());
        // $mobile_name = $name;
        //
        // $_name = preg_replace('#[^A-Za-z0-9_\-]#', '-', $name);
        // $_mobile_name = preg_replace('#[^A-Za-z0-9_\-]#', '-', $mobile_name);

        $counter = '';

        $img_path = str_replace('/public', '', self::IMAGE_PATH);
        $img_mobile_path = str_replace('/public', '', self::IMAGE_MOBILE_PATH);

        do{
            $name = 'product_'.date('Ymd').uniqid().'.'.$file->getClientOriginalExtension();
        //    $name = $_name . $counter . '.' . $file->getClientOriginalExtension();

           $counter = (int) $counter;

           $counter++;

        } while(file_exists(public_path() . $img_path . $name));

        $destinationPath = public_path() .  $img_path;
        $destinationMobilePath = public_path() .  $img_mobile_path;

        $isUploaded = $file->move($destinationPath, $name);
        File::copy($destinationPath."/".$name, $destinationMobilePath."/".$name);
        // $isUploadedMobile = $mobile->move($destinationMobilePath, $name);
        // $isUploadedMobile = $mobile->save(public_path().'/uploads/products/mobile/', 'file');
        // $isUploaded = $updated_file->save(public_path('uploads/products/web/' . $name));
        // $isUploadedMobile = $mobile->save(public_path('uploads/products/mobile/' . $name));
        // $isMobileUploaded = $mobile->save(public_path('uploads/products/mobile/', $name));
        // Image::make($image)->resize(200,200)->save(public_path('new-image/' . 'imageName'));
        //echo $isUploaded; exit;

        $defaultImage = static::where("is_default", "1")->where('product_id',$data["product_id"])->first();

        $isDefault = (int) (is_object($defaultImage)) ? 0 : 1;

        try{

            $productGallery = parent::add([
                "product_id" => $data["product_id"],
                "web_path" => self::IMAGE_PATH . $name,
                "image_path" => self::IMAGE_MOBILE_PATH . $name,
                "is_default" => $isDefault,
                "status" => 1
            ]);

            $json = [
                "code" => 200,
                "product_gallery_id" => $productGallery->product_gallery_id
            ];

        }
        catch(\PDOException $ex){
            Error::trigger('produst_gallery.upload', $ex->getMessage());
            $json = [
                "code" => 500,
                "message" => $ex->getMessage()
            ];
        }

        return $json;

    }

    function remove($product_gallery_id){

        $json = [];

        try{
            $image = static::find($product_gallery_id);
            // $default = static::where('is_default',1)->where('product_id',$image->product_id)->get();
            $isDeleted = static::where("product_gallery_id", $product_gallery_id)->delete();



            if($isDeleted){
                unlink( public_path() .  $image->image_path );
            }

            $json = [
                "code" => 200,
                "product_gallery_id" => $product_gallery_id
            ];
        }
        catch(\Exception $ex){
            $json = [
                "code" => 500,
                "message" => $ex->getMessage()
            ];
        }

    }

    function setDefault($product_gallery_id, $product_id = NULL){
        $json = [];

        if(!$product_id){
            $galleryImage = static::where("product_gallery_id", $product_gallery_id)->first();
            $product_id = $galleryImage->product_id;
        }

        try{
            static::where("product_id", $product_id)->update(["is_default" => 0]);
            static::where("product_gallery_id", $product_gallery_id)->update(["is_default" => 1]);
            $json = [
                "code" => 200
            ];
        }
        catch(\PDOException $ex){
            $json = [
                "code" => 500,
                "message" => $ex->getMessage()
            ];
        }

        return $json;

    }

    function showImage($image){

    }

}
